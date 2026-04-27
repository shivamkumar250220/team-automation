<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbApiCredential;
use App\Models\GmbLocation;
use App\Services\GMB\GmbGoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GmbAuthController extends Controller
{
    public function __construct(private GmbGoogleClient $google) {}

    public function redirectToGoogle(Client $client)
    {
        Session::put('gmb_client_id', $client->id);
        return redirect($this->google->getAuthUrl($client->id));
    }

    public function handleCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('clients.index')
                ->with('error', 'Google auth failed: ' . $request->error);
        }

        $code     = $request->get('code');
        $clientId = Session::get('gmb_client_id');

        if (!$clientId && $request->get('state')) {
            $clientId = json_decode(base64_decode($request->get('state')), true)['client_id'] ?? null;
        }

        if (!$code || !$clientId) {
            return redirect()->route('clients.index')
                ->with('error', 'Invalid callback — missing code or client.');
        }

        try {
            $tokens = $this->google->exchangeCode($code);
        } catch (\Exception $e) {
            return redirect()->route('clients.index')
                ->with('error', $e->getMessage());
        }

        GmbApiCredential::updateOrCreate(
            ['client_id' => $clientId],
            [
                'access_token'  => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'expires_at'    => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ]
        );

        try {
            $accounts = $this->google->getAccounts($tokens['access_token']);

            foreach ($accounts as $account) {
                $accountId = last(explode('/', $account['name']));
                $locations = $this->google->getLocations($tokens['access_token'], $account['name']);

                foreach ($locations as $loc) {
                    GmbLocation::updateOrCreate(
                        [
                            'client_id'       => $clientId,
                            'gbp_location_id' => last(explode('/', $loc['name'])),
                        ],
                        [
                            'location_name'  => $loc['title'],
                            'gbp_account_id' => $accountId,
                            'city'           => $loc['storefrontAddress']['locality'] ?? null,
                            'status'         => 'active',
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning("Locations sync failed for client {$clientId}: " . $e->getMessage());
        }

        Session::forget('gmb_client_id');

        return redirect()->route('gmb.clients.show', $clientId)
            ->with('success', 'Google Account connected successfully.');
    }

    public function disconnect(Client $client)
    {
        GmbApiCredential::where('client_id', $client->id)->delete();

        return redirect()->route('gmb.clients.show', $client->id)
            ->with('success', 'Google Account disconnected.');
    }
}