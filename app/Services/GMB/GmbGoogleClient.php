<?php

namespace App\Services\GMB;

use App\Models\GmbApiCredential;
use App\Models\GmbLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmbGoogleClient
{
    private array $secrets;

    public function __construct()
    {
        $path = config('gmb.client_secrets_path');

        if (!file_exists($path)) {
            throw new \Exception("GMB client secrets file not found at: {$path}");
        }

        $this->secrets = json_decode(file_get_contents($path), true)['web'];
    }

    public function getAuthUrl(int $clientId): string
    {
        $params = http_build_query([
            'client_id'     => $this->secrets['client_id'],
            'redirect_uri'  => $this->secrets['redirect_uris'][0],
            'response_type' => 'code',
            'scope'         => implode(' ', config('gmb.scopes')),
            'access_type'   => 'offline',
            'prompt'        => 'select_account consent',
            'state'         => base64_encode(json_encode(['client_id' => $clientId])),
        ]);

        return $this->secrets['auth_uri'] . '?' . $params;
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::post($this->secrets['token_uri'], [
            'code'          => $code,
            'client_id'     => $this->secrets['client_id'],
            'client_secret' => $this->secrets['client_secret'],
            'redirect_uri'  => $this->secrets['redirect_uris'][0],
            'grant_type'    => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new \Exception('Token exchange failed: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = Http::post($this->secrets['token_uri'], [
            'client_id'     => $this->secrets['client_id'],
            'client_secret' => $this->secrets['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \Exception('Token refresh failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getAccounts(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

        if ($response->status() === 429) {
            throw new \RuntimeException('Rate limit hit. Please wait a minute and retry.');
        }

        if ($response->failed()) {
            throw new \RuntimeException('Accounts fetch failed: ' . $response->body());
        }

        return $response->json()['accounts'] ?? [];
    }

    public function getLocations(string $accessToken, string $accountName): array
    {
        $response = Http::withToken($accessToken)
            ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations");

        if ($response->status() === 429) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return $response->json()['locations'] ?? [];
    }

    public function getValidToken(GmbApiCredential $credential): string
    {
        if (now()->gte($credential->expires_at)) {
            $data = $this->refreshToken($credential->refresh_token);

            $credential->update([
                'access_token' => $data['access_token'],
                'expires_at'   => now()->addSeconds($data['expires_in']),
            ]);

            $credential->refresh();
        }

        return $credential->access_token;
    }

    public function postReply(GmbLocation $location, string $reviewId, string $replyText, string $token): bool
    {
        $accountId  = $this->resolveId($location->gbp_account_id, 'accounts');
        $locationId = $this->resolveId($location->gbp_location_id, 'locations');

        $url = "https://mybusiness.googleapis.com/v4/{$accountId}/{$locationId}/reviews/{$reviewId}/reply";

        $response = Http::withToken($token)->put($url, ['comment' => $replyText]);

        if ($response->failed()) {
            Log::error("GMB postReply failed for review {$reviewId}: " . $response->body());
            return false;
        }

        return true;
    }

    private function resolveId(string $raw, string $prefix): string
    {
        $raw = trim($raw);
        return str_starts_with($raw, $prefix . '/') ? $raw : $prefix . '/' . $raw;
    }
}