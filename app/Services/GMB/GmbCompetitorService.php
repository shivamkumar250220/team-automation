<?php

namespace App\Services\GMB;

use App\Models\Client;
use App\Models\GmbCompetitor;
use App\Models\GmbCompetitorStat;
use App\Models\GmbLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmbCompetitorService
{
    public function runForAllClients(): void
    {
        $clients = Client::where('team_id', 2)
            ->where('status', 'active')
            ->get();

        foreach ($clients as $client) {
            try {
                $this->processClient($client);
            } catch (\Exception $e) {
                Log::error("Competitor pull failed for client {$client->id}: " . $e->getMessage());
            }
        }
    }

    public function processClient(Client $client): void
    {
        $location = GmbLocation::where('client_id', $client->id)->active()->first();
        $city     = $location?->city ?? $client->city ?? null;
        $industry = $client->industry ?? null;

        if (!$city || !$industry) {
            Log::warning('City or industry missing', ['client_id' => $client->id]);
            return;
        }

        // Auto fetch top 5 competitors
        $autoCompetitors = $this->fetchTopCompetitors($client->name, $industry, $city);

        foreach ($autoCompetitors as $comp) {
            // Upsert — same client ke liye same naam wala dobara na aaye
            $competitor = GmbCompetitor::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'name'      => $comp['name'],
                ],
                [
                    'place_id'   => $comp['place_id'] ?? 'auto',
                    'is_manual'  => false,
                ]
            );

            GmbCompetitorStat::create([
                'client_id'         => $client->id,
                'gmb_competitor_id' => $competitor->id,
                'rating'            => $comp['rating'] ?? null,
                'review_count'      => $comp['reviews'] ?? null,
                'photo_count'       => 0,
                'pulled_at'         => now(),
            ]);
        }

        // Manual competitors bhi pull karo agar hain
        $manualCompetitors = GmbCompetitor::where('client_id', $client->id)
            ->where('is_manual', true)
            ->get();

        foreach ($manualCompetitors as $competitor) {
            $data = $this->fetchPlaceData($competitor->place_id, $competitor->name);
            if (!$data) continue;

            GmbCompetitorStat::create([
                'client_id'         => $client->id,
                'gmb_competitor_id' => $competitor->id,
                'rating'            => $data['rating'] ?? null,
                'review_count'      => $data['user_ratings_total'] ?? null,
                'photo_count'       => $data['photos_count'] ?? null,
                'pulled_at'         => now(),
            ]);
        }
    }

    private function fetchTopCompetitors(string $clientName, string $industry, string $city): array
    {
        $query = "{$industry} in {$city}";

        $response = Http::withoutVerifying()
            ->get('https://serpapi.com/search', [
                'engine'  => 'google_local',   // google_maps → google_local
                'q'       => $query,
                'api_key' => config('gmb.serpapi_key'),
            ]);

        Log::info('SerpAPI Top Competitors', [
            'query'  => $query,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if ($response->failed()) return [];

        $results = $response->json('local_results') ?? [];

        $competitors = [];

        foreach ($results as $result) {
            if (str_contains(strtolower($result['title'] ?? ''), strtolower($clientName))) {
                continue;
            }

            $competitors[] = [
                'name'     => $result['title'] ?? 'Unknown',
                'place_id' => $result['place_id'] ?? 'auto',
                'rating'   => $result['rating'] ?? null,
                'reviews'  => $result['reviews'] ?? null,
            ];

            if (count($competitors) >= 5) break;
        }

        return $competitors;
    }

    private function fetchPlaceData(string $placeId, string $name): ?array
    {
        $response = Http::withoutVerifying()
            ->get('https://serpapi.com/search', [
                'engine'  => 'google_maps',
                'q'       => $name,
                'type'    => 'search',
                'api_key' => config('gmb.serpapi_key'),
            ]);

        if ($response->failed()) return null;

        $localResult = $response->json('local_results.0');
        if (!$localResult) return null;

        return [
            'rating'             => $localResult['rating'] ?? null,
            'user_ratings_total' => $localResult['reviews'] ?? null,
            'photos_count'       => 0,
        ];
    }

    public function getClientRating(Client $client): ?array
    {
        $location = GmbLocation::where('client_id', $client->id)->active()->first();
        if (!$location) return null;

        $insight = $location->latestInsight;
        if (!$insight) return null;

        return [
            'rating'       => $insight->average_rating ?? null,
            'review_count' => $insight->total_reviews ?? null,
        ];
    }
}