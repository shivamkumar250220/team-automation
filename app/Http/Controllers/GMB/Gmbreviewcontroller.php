<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbApiCredential;
use App\Models\GmbLocation;
use App\Models\GmbReview;
use App\Models\GmbReviewDraft;
use App\Services\GMB\GmbGeminiService;
use App\Services\GMB\GmbGoogleClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmbReviewController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function __construct(
        private GmbGoogleClient  $google,
        private GmbGeminiService $gemini
    ) {}

    public function index(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $locations = GmbLocation::where('client_id', $client->id)
            ->active()
            ->with(['reviews' => fn($q) => $q->with('draft')->latest('review_time')])
            ->get();

        return view('GMB.reviews.index', compact('client', 'locations'));
    }

    public function pullAndGenerate(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $credential = GmbApiCredential::where('client_id', $client->id)->first();

        if (!$credential) {
            return back()->with('error', 'No API credentials found. Connect Google account first.');
        }

        try {
            $token = $this->google->getValidToken($credential);
        } catch (\Exception $e) {
            return back()->with('error', 'Token refresh failed: ' . $e->getMessage());
        }

        $locations = GmbLocation::where('client_id', $client->id)->active()->get();

        if ($locations->isEmpty()) {
            return back()->with('error', 'No active locations found for this client.');
        }

        $totalPulled    = 0;
        $totalGenerated = 0;

        foreach ($locations as $location) {
            try {
                [$pulled, $generated] = $this->processLocation($location, $token, $client->name);
                $totalPulled    += $pulled;
                $totalGenerated += $generated;
            } catch (\Exception $e) {
                Log::error("Review pull failed for location {$location->id}: " . $e->getMessage());
            }
        }

        return redirect()
            ->route('gmb.reviews.index', $client)
            ->with('success', "Pulled {$totalPulled} new reviews. Generated {$totalGenerated} AI drafts.");
    }

    public function selectDraft(Request $request, GmbReview $review)
    {
        $request->validate([
            'selected_draft' => 'required|integer|min:1|max:5',
            'final_response' => 'required|string|max:4096',
        ]);

        $review->draft()->updateOrCreate(
            ['gmb_review_id' => $review->id],
            [
                'selected_draft' => $request->selected_draft,
                'final_response' => $request->final_response,
            ]
        );

        return back()->with('success', 'Draft saved. You can now post it to GMB.');
    }

    public function markReplied(GmbReview $review)
    {
        $review->update(['reply_posted' => true]);

        return back()->with('success', 'Review marked as replied.');
    }

    public function create(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $locations = GmbLocation::where('client_id', $client->id)->active()->get();

        return view('GMB.reviews.create', compact('client', 'locations'));
    }

    public function store(Request $request, Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $request->validate([
            'gmb_location_id' => 'required|exists:gmb_locations,id',
            'reviewer_name'   => 'required|string|max:255',
            'rating'          => 'required|in:ONE,TWO,THREE,FOUR,FIVE',
            'comment'         => 'nullable|string|max:4096',
            'review_time'     => 'nullable|date',
        ]);

        $review = GmbReview::create([
            'gmb_location_id' => $request->gmb_location_id,
            'client_id'       => $client->id,
            'review_id'       => 'manual_' . uniqid(),
            'reviewer_name'   => $request->reviewer_name,
            'rating'          => $request->rating,
            'comment'         => $request->comment,
            'review_time'     => $request->review_time ?? now(),
        ]);

        if (!empty($request->comment)) {
            $drafts = $this->gemini->generateDrafts(
                reviewerName: $review->reviewer_name,
                reviewText:   $review->comment,
                starRating:   $review->starCount(),
                clinicName:   $client->name
            );

            if (!empty($drafts)) {
                GmbReviewDraft::create([
                    'gmb_review_id' => $review->id,
                    'draft_1'       => $drafts[1] ?? null,
                    'draft_2'       => $drafts[2] ?? null,
                    'draft_3'       => $drafts[3] ?? null,
                    'draft_4'       => $drafts[4] ?? null,
                    'draft_5'       => $drafts[5] ?? null,
                    'generated_at'  => now(),
                ]);
            }
        }

        return redirect()
            ->route('gmb.reviews.index', $client)
            ->with('success', 'Review added successfully. AI drafts generated!');
    }

    private function processLocation(GmbLocation $location, string $token, string $clientName): array
    {
        $reviews   = $this->fetchReviews($location, $token);
        $pulled    = 0;
        $generated = 0;

        foreach ($reviews as $raw) {
            $reviewId = last(explode('/', $raw['name']));

            if (GmbReview::where('review_id', $reviewId)->exists()) {
                continue;
            }

            $review = GmbReview::create([
                'gmb_location_id' => $location->id,
                'client_id'       => $location->client_id,
                'review_id'       => $reviewId,
                'reviewer_name'   => $raw['reviewer']['displayName'] ?? 'Anonymous',
                'reviewer_photo'  => $raw['reviewer']['profilePhotoUrl'] ?? null,
                'rating'          => $raw['starRating'] ?? null,
                'comment'         => $raw['comment'] ?? null,
                'review_time'     => isset($raw['createTime']) ? Carbon::parse($raw['createTime']) : null,
                'reply_text'      => $raw['reviewReply']['comment'] ?? null,
                'reply_time'      => isset($raw['reviewReply']['updateTime']) ? Carbon::parse($raw['reviewReply']['updateTime']) : null,
            ]);

            $pulled++;

            if (!empty($raw['comment'])) {
                $drafts = $this->gemini->generateDrafts(
                    reviewerName: $review->reviewer_name,
                    reviewText:   $review->comment,
                    starRating:   $review->starCount(),
                    clinicName:   $clientName
                );

                if (!empty($drafts)) {
                    GmbReviewDraft::create([
                        'gmb_review_id' => $review->id,
                        'draft_1'       => $drafts[1] ?? null,
                        'draft_2'       => $drafts[2] ?? null,
                        'draft_3'       => $drafts[3] ?? null,
                        'draft_4'       => $drafts[4] ?? null,
                        'draft_5'       => $drafts[5] ?? null,
                        'generated_at'  => now(),
                    ]);
                    $generated++;
                }
            }
        }

        return [$pulled, $generated];
    }

    private function fetchReviews(GmbLocation $location, string $token): array
    {
        $apiKey  = env('SERPAPI_KEY');
        $placeId = $location->google_place_id ?? null;

        if (empty($placeId)) {
            Log::error("GmbLocation {$location->id} has no google_place_id.");
            return [];
        }

        $response = Http::get('https://serpapi.com/search', [
            'engine'   => 'google_maps_reviews',
            'place_id' => $placeId,
            'api_key'  => $apiKey,
            'hl'       => 'en',
        ]);

        if ($response->failed()) {
            Log::error("SerpAPI error for location {$location->id}: " . $response->body());
            return [];
        }

        return array_map(fn($r) => [
            'name'        => 'reviews/' . md5(($r['user']['name'] ?? '') . ($r['date'] ?? '')),
            'reviewer'    => ['displayName' => $r['user']['name'] ?? 'Anonymous'],
            'starRating'  => match((int)($r['rating'] ?? 5)) {
                1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR', default => 'FIVE'
            },
            'comment'     => $r['snippet'] ?? null,
            'createTime'  => $r['iso_date'] ?? now()->toISOString(),
            'reviewReply' => isset($r['response']) ? ['comment' => $r['response']['snippet']] : null,
        ], $response->json('reviews') ?? []);
    }
}