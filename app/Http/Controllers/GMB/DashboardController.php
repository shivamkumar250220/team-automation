<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbLocation;
use App\Models\GmbApiCredential;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function index()
    {
        $clients = Client::with('team:id,name')
            ->where('team_id', 2)
            ->get();

        return view('GMB.dashboard', compact('clients'));
    }

    public function show(Client $client)
    {
        $client->load(['creator:id,name', 'team:id,name', 'user:id,name,email']);

        $gmbLocations  = GmbLocation::where('client_id', $client->id)->get();
        $gmbCredential = GmbApiCredential::where('client_id', $client->id)->first();

        return view('clients.show', compact('client', 'gmbLocations', 'gmbCredential'));
    }

    public function storeLocation(Request $request, Client $client)
    {
        if ($client->team_id !== self::GMB_TEAM_ID) {
            return back()->with('error', 'This client is not assigned to GMB team.');
        }

        $validated = $request->validate([
            'location_name'   => 'required|string|max:191',
            'gbp_account_id'  => 'required|string|max:191',
            'gbp_location_id' => 'required|string|max:191',
            'city'            => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
        ]);

        GmbLocation::create(array_merge($validated, ['client_id' => $client->id]));

        return back()->with('success', 'Location added successfully.');
    }

    public function updateLocation(Request $request, Client $client, GmbLocation $location)
    {
        if ($location->client_id !== $client->id) {
            return back()->with('error', 'Location not found.');
        }

        $validated = $request->validate([
            'location_name'   => 'required|string|max:191',
            'gbp_account_id'  => 'required|string|max:191',
            'gbp_location_id' => 'required|string|max:191',
            'city'            => 'nullable|string|max:100',
            'status'          => 'required|in:active,inactive',
        ]);

        $location->update($validated);

        return back()->with('success', 'Location updated successfully.');
    }

    public function destroyLocation(Client $client, GmbLocation $location)
    {
        if ($location->client_id !== $client->id) {
            return back()->with('error', 'Location not found.');
        }

        $location->delete();

        return back()->with('success', 'Location deleted.');
    }

    public function saveCredential(Request $request, Client $client)
    {
        if ($client->team_id !== self::GMB_TEAM_ID) {
            return back()->with('error', 'This client is not assigned to GMB team.');
        }

        $validated = $request->validate([
            'access_token'  => 'required|string',
            'refresh_token' => 'required|string',
            'expires_at'    => 'nullable|date',
        ]);

        GmbApiCredential::updateOrCreate(
            ['client_id' => $client->id],
            $validated
        );

        return back()->with('success', 'Credentials saved successfully.');
    }
}