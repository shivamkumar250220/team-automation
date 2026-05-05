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
            Log::warning("No active locations for client {$client->id}");
            return false;
        }

        $credential = GmbApiCredential::where('client_id', $client->id)->first();

        if (!$credential) {
            Log::warning("No credentials for client {$client->id}");
            return false;
        }

        if ($credential->isExpired()) {
            $credential = $this->refreshCredential($credential);
        }

        $token = $credential?->access_token;

        if (!$token) {
            Log::warning("No valid token for client {$client->id}");
            return false;
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

    private function fetchAndSave(GmbLocation $location, string $token): void
    {
        $month = now()->month;
        $year  = now()->year;

        $basicData         = $this->fetchLocationBasicInfo($location, $token);
        $views             = 0;
        $calls             = 0;
        $directionRequests = 0;
        $searchQueries     = [];

        try {
            [$views, $calls, $directionRequests] = $this->fetchPerformanceMetrics($location, $token, $month, $year);
            $searchQueries = $this->fetchSearchQueries($location, $token, $month, $year);
        } catch (\Exception $e) {
            Log::warning("Performance fetch skipped for location {$location->id}: {$e->getMessage()}");
        }

        GmbInsight::updateOrCreate(
            [
                'gmb_location_id' => $location->id,
                'month'           => $month,
                'year'            => $year,
            ],
            [
                'rating'             => $basicData['rating'] ?? null,
                'review_count'       => $basicData['review_count'] ?? null,
                'views'              => $views,
                'calls'              => $calls,
                'direction_requests' => $directionRequests,
                'search_queries'     => $searchQueries,
                'pulled_at'          => now(),
            ]
        );
    }

    private function fetchLocationBasicInfo(GmbLocation $location, string $token): array
    {
        $locationId = $this->resolveLocationId($location->gbp_location_id);

        $response = Http::withToken($token)
            ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$locationId}", [
                'readMask' => 'name,title,rating,userRatingCount',
            ]);

        if ($response->failed()) {
            Log::warning("Basic info fetch failed for location {$location->id}: " . $response->body());
            return [];
        }

        $data = $response->json();

        return [
            'rating'       => $data['rating'] ?? null,
            'review_count' => $data['userRatingCount'] ?? null,
        ];
    }

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

        $data              = $response->json();
        $views             = $this->sumMetric($data, 'BUSINESS_IMPRESSIONS_DESKTOP_MAPS')
                           + $this->sumMetric($data, 'BUSINESS_IMPRESSIONS_MOBILE_MAPS');
        $calls             = $this->sumMetric($data, 'CALL_CLICKS');
        $directionRequests = $this->sumMetric($data, 'BUSINESS_DIRECTION_REQUESTS');

        return [$views, $calls, $directionRequests];
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