<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbLocation;
use App\Models\GmbPostTemplate;
use App\Services\GMB\GmbGoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GmbPostTemplateController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function __construct(private GmbGoogleClient $google) {}

    public function index(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $posts     = GmbPostTemplate::where('client_id', $client->id)->latest()->get();
        $locations = $client->gmbLocations()->active()->get();

        return view('GMB.post-templates.index', compact('client', 'posts', 'locations'));
    }

    public function fetchCompetitors(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $serpKey  = config('gmb.serpapi_key');
        $industry = $client->industry ?? 'clinic';
        $city     = $client->city ?? '';

        Log::info('[PostTemplate] fetchCompetitors called', [
            'client_id' => $client->id,
            'industry'  => $industry,
            'city'      => $city,
        ]);

        if (!$serpKey) {
            Log::error('[PostTemplate] SerpAPI key not configured');
            return response()->json(['error' => 'SerpAPI key not configured.'], 500);
        }

        $location = $client->gmbLocations()->active()->first();

        $params = [
            'engine'   => 'google_local',
            'q'        => "{$industry} {$city}",
            'api_key'  => $serpKey,
            'num'      => 20,
            'hl'       => 'en',
            'location' => ($location?->city ?? $city) . ', India',
        ];

        Log::info('[PostTemplate] SerpAPI request params', $params);

        try {
            $serpResponse = Http::timeout(15)->get('https://serpapi.com/search.json', $params);

            Log::info('[PostTemplate] SerpAPI response status', ['status' => $serpResponse->status()]);
            Log::debug('[PostTemplate] SerpAPI raw response', ['body' => $serpResponse->body()]);

            if ($serpResponse->failed()) {
                Log::error('[PostTemplate] SerpAPI request failed', ['body' => $serpResponse->body()]);
                return response()->json(['error' => 'Competitor search request failed.'], 500);
            }

            $results = $serpResponse->json('local_results') ?? [];

            Log::info('[PostTemplate] SerpAPI local_results count', ['count' => count($results)]);

            if (empty($results)) {
                return response()->json(['competitors' => [], 'message' => 'No local results found for this area.']);
            }

            usort($results, fn($a, $b) => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0));

            $competitors = collect(array_slice($results, 0, 5))->map(function ($p) use ($serpKey) {
                $placeId   = $p['place_id'] ?? ($p['data_id'] ?? '');
                $placeData = $placeId ? $this->fetchPlaceDetails($placeId, $serpKey) : [];

                return [
                    'place_id'      => $placeId,
                    'name'          => $p['title']   ?? ($p['name']    ?? 'Unknown'),
                    'type'          => $p['type']    ?? '',
                    'rating'        => $p['rating']  ?? 0,
                    'total_reviews' => $p['reviews'] ?? ($p['user_ratings_total'] ?? 0),
                    'photos'        => isset($p['thumbnail']) ? '✓' : '—',
                    'address'       => $p['address'] ?? '',
                    'description'   => $p['description'] ?? ($p['snippet'] ?? ''),
                    'recent_posts'  => $placeData['reviews'] ?? [],
                ];
            })->toArray();

            Log::info('[PostTemplate] Competitors built', [
                'competitors' => collect($competitors)->map(fn($c) => [
                    'name'         => $c['name'],
                    'rating'       => $c['rating'],
                    'reviews_found' => count($c['recent_posts']),
                ])->toArray(),
            ]);

            return response()->json([
                'competitors' => $competitors,
                'pulled_at'   => now()->format('d M Y, h:i A'),
            ]);

        } catch (\Exception $e) {
            Log::error('[PostTemplate] fetchCompetitors exception', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function fetchPlaceDetails(string $placeId, string $serpKey): array
    {
        Log::info('[PostTemplate] Fetching place details via SerpAPI', ['place_id' => $placeId]);

        $response = Http::timeout(10)->get('https://serpapi.com/search.json', [
            'engine'   => 'google_maps',
            'place_id' => $placeId,
            'api_key'  => $serpKey,
            'type'     => 'place',
        ]);

        Log::info('[PostTemplate] SerpAPI place details response', [
            'place_id' => $placeId,
            'status'   => $response->status(),
        ]);
        Log::debug('[PostTemplate] SerpAPI place details body', ['body' => $response->body()]);

        if ($response->failed()) {
            Log::warning('[PostTemplate] SerpAPI place details failed', ['place_id' => $placeId]);
            return [];
        }

        $data    = $response->json();
        $reviews = collect($data['reviews'] ?? [])->take(3)->map(fn($r) => [
            'summary'    => $r['snippet'] ?? ($r['text'] ?? ''),
            'topic_type' => 'REVIEW',
        ])->filter(fn($r) => !empty($r['summary']))->values()->toArray();

        Log::info('[PostTemplate] SerpAPI place reviews found', [
            'place_id' => $placeId,
            'count'    => count($reviews),
        ]);

        return ['reviews' => $reviews];
    }

    public function generatePost(Request $request, Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $request->validate([
            'competitors' => 'required|array|min:1',
            'post_type'   => 'nullable|string|in:whats_new,offer,event',
            'topic'       => 'nullable|string|max:255',
            'emotion'     => 'nullable|string|max:100',
            'cta'         => 'nullable|string|max:255',
            'usp'         => 'nullable|string|max:255',
            'offer'       => 'nullable|string|max:255',
        ]);

        $competitorContext = collect($request->competitors)->map(function ($c, $i) {
            $reviewsText = collect($c['recent_posts'] ?? [])->map(fn($r) => "  - \"{$r['summary']}\"")->implode("\n");

            return ($i + 1) . ". {$c['name']}\n"
                . "   Rating: {$c['rating']}/5 | Reviews: {$c['total_reviews']}\n"
                . ($c['description'] ? "   Description: {$c['description']}\n" : '')
                . "   Customer Review Snippets:\n" . ($reviewsText ?: '  - None available');
        })->implode("\n\n");

        $myRecentPosts = GmbPostTemplate::where('client_id', $client->id)
            ->where('status', 'published')
            ->latest()
            ->take(3)
            ->pluck('post_content')
            ->map(fn($p, $i) => "Post " . ($i + 1) . ": " . Str::limit($p, 200))
            ->implode("\n");

       
        $prompt = $this->buildPrompt(
            clientName:        $client->name,
            industry:          $client->industry ?? 'healthcare',
            city:              $client->city ?? '',
            competitorContext: $competitorContext,
            myRecentPosts:     $myRecentPosts,
            postType:          $request->post_type ?? 'whats_new',
            topic:             $request->topic     ?? 'Our specialty services',
            emotion:           $request->emotion   ?? 'trust and care',
            cta:               $request->cta       ?? 'Book your appointment today',
            usp:               $request->usp       ?? 'Expert team with years of experience',
            offer:             $request->offer     ?? '',
        );

    $geminiService = app(\App\Services\GMB\GmbGeminiService::class);

        $text = $geminiService->generatePost($prompt);

        if (empty($text)) {
            return response()->json(['error' => 'AI returned empty response'], 500);
        }

        return response()->json([
            'post' => trim($text)
        ]);
    }

    public function store(Request $request, Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $request->validate([
            'post_content'    => 'required|string|max:1500',
            'post_type'       => 'nullable|string|max:50',
            'topic'           => 'nullable|string|max:255',
            'emotion'         => 'nullable|string|max:100',
            'cta'             => 'nullable|string|max:255',
            'usp'             => 'nullable|string|max:255',
            'offer'           => 'nullable|string|max:255',
            'competitors'     => 'nullable|string',
            'status'          => 'required|in:draft,published',
            'gmb_location_id' => 'nullable|exists:gmb_locations,id',
        ]);

        Log::info('[PostTemplate] store called', [
            'client_id' => $client->id,
            'status'    => $request->status,
            'location'  => $request->gmb_location_id,
        ]);

        $post = GmbPostTemplate::create([
            'client_id'       => $client->id,
            'gmb_location_id' => $request->gmb_location_id,
            'post_content'    => $request->post_content,
            'topic'           => $request->topic,
            'emotion'         => $request->emotion,
            'cta'             => $request->cta,
            'usp'             => $request->usp,
            'offer'           => $request->offer,
            'competitors'     => $request->competitors,
            'status'          => 'draft',
        ]);

        if ($request->status === 'published' && $request->gmb_location_id) {
            return $this->attemptPublish($post, $request->gmb_location_id, $client, $request->post_type ?? 'STANDARD');
        }

        return redirect()->route('gmb.post-templates.index', $client)->with('success', 'Draft saved!');
    }

    public function publish(Request $request, Client $client, GmbPostTemplate $post)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $request->validate(['gmb_location_id' => 'required|exists:gmb_locations,id']);

        Log::info('[PostTemplate] publish called', [
            'post_id'     => $post->id,
            'location_id' => $request->gmb_location_id,
        ]);

        return $this->attemptPublish($post, $request->gmb_location_id, $client);
    }

    public function destroy(Client $client, GmbPostTemplate $post)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        Log::info('[PostTemplate] destroy called', ['post_id' => $post->id]);

        $post->delete();

        return back()->with('success', 'Post deleted.');
    }

    private function attemptPublish(GmbPostTemplate $post, string $locationId, Client $client, string $type = 'STANDARD')
    {
        $location   = GmbLocation::findOrFail($locationId);
        $credential = $location->client->gmbCredential;

        if (!$credential) {
            Log::warning('[PostTemplate] No GMB credential for client', ['client_id' => $client->id]);
            return redirect()->route('gmb.post-templates.index', $client)
                ->with('error', 'No API credentials found for this client.');
        }

        try {
            $token  = $this->google->getValidToken($credential);

            Log::info('[PostTemplate] Publishing to GMB', [
                'location_id' => $location->id,
                'post_id'     => $post->id,
                'type'        => $type,
            ]);

            $pushed = $this->publishToGmb($location, $post->post_content, $token, $type);

            if ($pushed) {
                $post->update([
                    'status'          => 'published',
                    'published_at'    => now(),
                    'gmb_location_id' => $location->id,
                ]);

                Log::info('[PostTemplate] Post published successfully', ['post_id' => $post->id]);

                return redirect()->route('gmb.post-templates.index', $client)
                    ->with('success', 'Post published to Google My Business!');
            }

        } catch (\Exception $e) {
            Log::error('[PostTemplate] Publish exception', ['message' => $e->getMessage()]);
            return redirect()->route('gmb.post-templates.index', $client)
                ->with('warning', 'Post saved but could not publish: ' . $e->getMessage());
        }

        return redirect()->route('gmb.post-templates.index', $client)
            ->with('error', 'Failed to publish post to GMB.');
    }

    private function publishToGmb(GmbLocation $location, string $content, string $token, string $type = 'STANDARD'): bool
    {
        $accountId  = $this->resolveId($location->gbp_account_id,  'accounts');
        $locationId = $this->resolveId($location->gbp_location_id, 'locations');

        $topicType = match (strtolower($type)) {
            'offer' => 'OFFER',
            'event' => 'EVENT',
            default => 'STANDARD',
        };

        $url = "https://mybusiness.googleapis.com/v4/{$accountId}/{$locationId}/localPosts";

        Log::info('[PostTemplate] GMB localPosts API call', [
            'url'            => $url,
            'topic_type'     => $topicType,
            'content_length' => strlen($content),
        ]);

        $response = Http::withToken($token)->post($url, [
            'languageCode' => 'en',
            'summary'      => $content,
            'topicType'    => $topicType,
        ]);

        Log::info('[PostTemplate] GMB localPosts response', ['status' => $response->status()]);
        Log::debug('[PostTemplate] GMB localPosts body', ['body' => $response->body()]);

        if ($response->failed()) {
            Log::error('[PostTemplate] GMB localPost creation failed', ['body' => $response->body()]);
            return false;
        }

        return true;
    }

    private function buildPrompt(
        string $clientName,
        string $industry,
        string $city,
        string $competitorContext,
        string $myRecentPosts,
        string $postType,
        string $topic,
        string $emotion,
        string $cta,
        string $usp,
        string $offer
    ): string {
        $offerLine     = $offer ? "Special Offer: {$offer}" : 'No specific offer';
        $postTypeLabel = match ($postType) {
            'offer'  => 'OFFER (promotional)',
            'event'  => 'EVENT',
            default  => "WHAT'S NEW (standard update)",
        };
        $myPostsContext = $myRecentPosts
            ? "OUR RECENT PUBLISHED POSTS (maintain consistent style, avoid repetition):\n{$myRecentPosts}"
            : 'No prior published posts found.';

        return <<<PROMPT
You are a Google Business Profile SEO expert and healthcare content strategist.

TASK: Write a GBP post for "{$clientName}" ({$industry} in {$city}) that outperforms competitor posts in local search.

{$myPostsContext}

TOP COMPETITOR PROFILES & THEIR CUSTOMER FEEDBACK:
{$competitorContext}

ANALYSIS INSTRUCTIONS:
- Study competitor descriptions and customer review snippets carefully
- Identify what keywords they rank for, what services customers mention, and what emotional angles they miss
- Write a post that fills those gaps and is MORE specific, MORE local, and MORE engaging than theirs

POST TYPE: {$postTypeLabel}

PARAMETERS:
- Topic: {$topic}
- Emotional Tone: {$emotion}
- Unique Selling Point: {$usp}
- Call To Action: {$cta}
- {$offerLine}

WRITING RULES:
1. 150–300 words
2. Open with a local hook mentioning {$city} or the specific condition
3. Include 3–5 natural long-tail keywords (not stuffed)
4. Use 2–4 relevant emojis
5. Highlight one USP competitors do NOT mention
6. Strong CTA with specificity (e.g. "Call now — limited slots this week")
7. No false medical promises
8. End with 6–8 hashtags mixing broad + hyper-local

Write ONLY the post content. No labels, preamble, explanations, or markdown.
PROMPT;
    }

    private function resolveId(string $raw, string $prefix): string
    {
        $raw = trim($raw);
        return str_starts_with($raw, $prefix . '/') ? $raw : $prefix . '/' . $raw;
    }
}