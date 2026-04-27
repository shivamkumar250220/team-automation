<?php

namespace App\Services\GMB;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CitationScanService
{
    private string $apiKey;

    private array $directories = [
        'Practo',
        'Lybrate',
        'Sulekha',
        'JustDial',
        'Facebook',
    ];

    public function __construct()
    {
        $this->apiKey = env('SERPAPI_KEY');
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    public function fetchGbpNap(string $businessName, string $city): array
    {
        try {
            $response = Http::timeout(15)
                ->retry(2, 2000)
                ->get('https://serpapi.com/search', [
                    'engine'  => 'google_maps',
                    'q'       => "{$businessName} {$city}",
                    'api_key' => $this->apiKey,
                    'hl'      => 'en',
                    'gl'      => 'in',
                ]);

            if ($response->failed()) {
                Log::error("SerpApi GBP fetch failed: " . $response->body());
                return [];
            }

            $place = $response->json('local_results.0') ?? $response->json('place_results') ?? [];

            if (empty($place)) {
                return [];
            }

            return [
                'name'    => $place['title'] ?? null,
                'address' => $place['address'] ?? null,
                'phone'   => $place['phone'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("SerpApi GBP fetch exception: " . $e->getMessage());
            return [];
        }
    }
    public function scanDirectory(string $directory, string $businessName, string $city): array
    {
        try {
            $query = "{$businessName} {$city} site:" . $this->getDirectoryDomain($directory);

            $response = Http::timeout(15)
                ->retry(2, 2000)
                ->get('https://serpapi.com/search', [
                    'engine'  => 'google',
                    'q'       => $query,
                    'api_key' => $this->apiKey,
                    'hl'      => 'en',
                    'gl'      => 'in',
                    'num'     => 3,
                ]);

            if ($response->failed()) {
                Log::error("SerpApi scan failed for {$directory}: " . $response->body());
                return [];
            }

            $results = $response->json('organic_results') ?? [];

            if (empty($results)) {
                return [];
            }

            return $this->extractNap($results[0]);
        } catch (\Exception $e) {
            Log::error("SerpApi scanDirectory exception [{$directory}]: " . $e->getMessage());
            return [];
        }
    }

    public function compareNap(array $gbp, array $found): string
    {
        $nameMatch = isset($gbp['name'], $found['name'])
            && strtolower(trim($gbp['name'])) === strtolower(trim($found['name']));

        $addressMatch = isset($gbp['address'], $found['address'])
            && strtolower(trim($gbp['address'])) === strtolower(trim($found['address']));

        $phoneMatch = isset($gbp['phone'], $found['phone'])
            && preg_replace('/\D/', '', $gbp['phone']) === preg_replace('/\D/', '', $found['phone']);

        if ($nameMatch && $addressMatch && $phoneMatch) {
            return 'match';
        }

        return 'mismatch';
    }

    private function extractNap(array $result): array
    {
        return [
            'name'    => $result['title'] ?? null,
            'address' => $result['snippet'] ?? null,
            'phone'   => $this->extractPhone($result['snippet'] ?? ''),
        ];
    }

    private function extractPhone(string $text): ?string
    {
        preg_match('/(\+91[\-\s]?)?[6-9]\d{9}/', $text, $matches);
        return $matches[0] ?? null;
    }

    private function getDirectoryDomain(string $directory): string
    {
        return match ($directory) {
            'Practo'   => 'practo.com',
            'Lybrate'  => 'lybrate.com',
            'Sulekha'  => 'sulekha.com',
            'JustDial' => 'justdial.com',
            'Facebook' => 'facebook.com',
            default    => strtolower($directory) . '.com',
        };
    }
}