<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbApiCredential;
use App\Models\GmbInsight;
use App\Models\GmbLocation;
use App\Services\GMB\GmbGoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmbInsightController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function __construct(private GmbGoogleClient $google) {}

    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);

        $clients = Client::where('team_id', self::GMB_TEAM_ID)
            ->where('status', 'active')
            ->with(['gmbLocations' => fn($q) => $q->active()
                ->with(['insights' => fn($q) => $q->forMonth($month, $year)])
            ])
            ->get();

        return view('GMB.insights.index', compact('clients', 'month', 'year'));
    }

    public function show(Client $client)
    {
        if ($client->team_id !== self::GMB_TEAM_ID) {
            return redirect()->route('gmb.insights.index')->with('error', 'Client not found.');
        }

        $locations = GmbLocation::where('client_id', $client->id)
            ->active()
            ->with(['insights' => fn($q) => $q->orderByDesc('year')->orderByDesc('month')->limit(6)])
            ->get();

        return view('GMB.insights.show', compact('client', 'locations'));
    }

    public function pullNow(Client $client)
    {
        if ($client->team_id !== self::GMB_TEAM_ID) {
            return redirect()->route('gmb.insights.index')->with('error', 'Client not found.');
        }

        $pulled = $this->pullInsightsForClient($client);

        return back()->with(
            $pulled ? 'success' : 'error',
            $pulled ? "Insights pulled for {$client->name}." : "Failed. Check credentials or locations."
        );
    }

    public function pullAllClients(): void
    {
        Client::where('team_id', self::GMB_TEAM_ID)
            ->where('status', 'active')
            ->get()
            ->each(function ($client) {
                try {
                    $this->pullInsightsForClient($client);
                } catch (\Exception $e) {
                    Log::error("GMB pull failed for client {$client->id}: {$e->getMessage()}");
                }
            });
    }

    private function pullInsightsForClient(Client $client): bool
    {
        $locations = GmbLocation::where('client_id', $client->id)->active()->get();

        if ($locations->isEmpty()) {
            return false;
        }

        // ── Try to get OAuth token (optional — used for performance metrics) ──
        $token = null;

        $credential = GmbApiCredential::where('client_id', $client->id)->first();

        if ($credential) {
            if ($credential->isExpired()) {
                $credential = $this->refreshCredential($credential);
            }
            $token = $credential?->access_token;
        }

        $anySuccess = false;

        foreach ($locations as $location) {
            try {
                $this->fetchAndSave($location, $token);
                $anySuccess = true;
            } catch (\Exception $e) {
                Log::error("Fetch failed for location {$location->id}: {$e->getMessage()}");
            }
        }

        return $anySuccess;
    }

    private function fetchAndSave(GmbLocation $location, ?string $token): void
    {
        $month = now()->month;
        $year  = now()->year;

        // ── Step 1: Always fetch public data from SerpAPI ──────────────────
        $serpData = $this->fetchSerpApiData($location);

        // ── Step 2: Fetch performance metrics via OAuth (if token available) ─
        $views             = 0;
        $calls             = 0;
        $directionRequests = 0;
        $searchQueries     = [];

        if ($token && !empty($location->gbp_location_id)) {
            try {
                [$views, $calls, $directionRequests] = $this->fetchPerformanceMetrics($location, $token, $month, $year);
                $searchQueries = $this->fetchSearchQueries($location, $token, $month, $year);
            } catch (\Exception $e) {
                $isQuotaError = str_contains($e->getMessage(), '429')
                    || str_contains($e->getMessage(), 'RATE_LIMIT_EXCEEDED')
                    || str_contains($e->getMessage(), 'RESOURCE_EXHAUSTED')
                    || str_contains($e->getMessage(), 'Quota exceeded');

                if ($isQuotaError) {
                    Log::warning("GBP API quota exceeded for location {$location->id}. Performance metrics skipped. Fix: https://console.cloud.google.com/apis/api/businessprofileperformance.googleapis.com/quotas");
                } else {
                    Log::warning("OAuth performance fetch skipped for location {$location->id}: {$e->getMessage()}");
                }
                // SerpAPI data (rating, review_count) still saves below
            }
        }

        // ── Step 3: Save / update insight record ───────────────────────────
        GmbInsight::updateOrCreate(
            [
                'gmb_location_id' => $location->id,
                'month'           => $month,
                'year'            => $year,
            ],
            [
                // From SerpAPI (public data — always available)
                'rating'             => $serpData['rating'] ?? null,
                'review_count'       => $serpData['review_count'] ?? null,

                // From Google OAuth (private metrics — only if token exists)
                'views'              => $views,
                'calls'              => $calls,
                'direction_requests' => $directionRequests,
                'search_queries'     => $searchQueries,

                'pulled_at'          => now(),
            ]
        );
    }

    // ── SerpAPI: fetch public business data (rating, review count) ──────────
    private function fetchSerpApiData(GmbLocation $location): array
    {
        $apiKey  = env('SERPAPI_KEY');
        $placeId = $location->google_place_id ?? null;

        if (empty($placeId)) {
            Log::warning("GmbLocation {$location->id} has no google_place_id — skipping SerpAPI fetch.");
            return [];
        }

        $response = Http::get('https://serpapi.com/search', [
            'engine'   => 'google_maps',
            'place_id' => $placeId,
            'api_key'  => $apiKey,
            'hl'       => 'en',
        ]);

        if ($response->failed()) {
            Log::error("SerpAPI insight fetch failed for location {$location->id}: " . $response->body());
            return [];
        }

        $place = $response->json('place_results') ?? [];

        return [
            'rating'       => $place['rating'] ?? null,
            'review_count' => $place['reviews'] ?? null,
        ];
    }

    // ── Google OAuth: fetch views, calls, direction requests ────────────────
    private function fetchPerformanceMetrics(GmbLocation $location, string $token, int $month, int $year): array
    {
        $locationId = $this->resolveLocationId($location->gbp_location_id);

        $response = Http::withToken($token)
            ->get("https://businessprofileperformance.googleapis.com/v1/{$locationId}:fetchMultiDailyMetricsTimeSeries", [
                'dailyMetrics'               => [
                    'BUSINESS_IMPRESSIONS_DESKTOP_MAPS',
                    'BUSINESS_IMPRESSIONS_MOBILE_MAPS',
                    'CALL_CLICKS',
                    'BUSINESS_DIRECTION_REQUESTS',
                ],
                'dailyRange.startDate.year'  => $year,
                'dailyRange.startDate.month' => $month,
                'dailyRange.startDate.day'   => 1,
                'dailyRange.endDate.year'    => $year,
                'dailyRange.endDate.month'   => $month,
                'dailyRange.endDate.day'     => now()->daysInMonth,
            ]);

        if ($response->failed()) {
            throw new \Exception("Performance API failed: " . $response->body());
        }

        $data = $response->json();

        $views = $this->sumMetric($data, 'BUSINESS_IMPRESSIONS_DESKTOP_MAPS')
               + $this->sumMetric($data, 'BUSINESS_IMPRESSIONS_MOBILE_MAPS');

        $calls             = $this->sumMetric($data, 'CALL_CLICKS');
        $directionRequests = $this->sumMetric($data, 'BUSINESS_DIRECTION_REQUESTS');

        return [$views, $calls, $directionRequests];
    }

    private function resolveLocationId(string $raw): string
    {
        $raw = trim($raw);
        return str_starts_with($raw, 'locations/') ? $raw : 'locations/' . $raw;
    }

    private function sumMetric(array $data, string $metric): int
    {
        $series = collect($data['multiDailyMetricTimeSeries'] ?? [])
            ->first(fn($s) => $s['dailyMetric'] === $metric);

        if (!$series) return 0;

        return collect($series['timeSeries']['datedValues'] ?? [])
            ->sum(fn($p) => (int) ($p['value'] ?? 0));
    }

    private function fetchSearchQueries(GmbLocation $location, string $token, int $month, int $year): array
    {
        $locationId = $this->resolveLocationId($location->gbp_location_id);

        $response = Http::withToken($token)
            ->get("https://businessprofileperformance.googleapis.com/v1/{$locationId}/searchkeywords/impressions/monthly", [
                'monthlyRange.startMonth.year'  => $year,
                'monthlyRange.startMonth.month' => $month,
                'monthlyRange.endMonth.year'    => $year,
                'monthlyRange.endMonth.month'   => $month,
            ]);

        if ($response->failed()) return [];

        return collect($response->json()['searchKeywordsCounts'] ?? [])
            ->take(10)
            ->map(fn($k) => [
                'query'       => $k['searchKeyword'],
                'impressions' => $k['insightsValue']['value'] ?? 0,
            ])
            ->toArray();
    }

    private function refreshCredential(GmbApiCredential $credential): ?GmbApiCredential
    {
        if (!$credential->refresh_token) return null;

        try {
            $data = $this->google->refreshToken($credential->refresh_token);
        } catch (\Exception $e) {
            Log::error("Token refresh failed: " . $e->getMessage());
            return null;
        }

        $credential->update([
            'access_token' => $data['access_token'],
            'expires_at'   => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $credential->fresh();
    }
}