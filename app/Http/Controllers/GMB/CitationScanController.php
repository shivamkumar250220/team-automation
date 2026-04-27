<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbCitationAudit;
use App\Models\GmbLocation;
use App\Services\GMB\CitationScanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CitationScanController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function __construct(
        private CitationScanService $scanner
    ) {}

    public function index(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $gbpNap = null;

        $existing = GmbCitationAudit::where('client_id', $client->id)->first();

        if ($existing) {
            $gbpNap = [
                'name'    => $existing->gbp_name,
                'address' => $existing->gbp_address,
                'phone'   => $existing->gbp_phone,
            ];
        }

        $audits = GmbCitationAudit::where('client_id', $client->id)
            ->latest('scanned_at')
            ->get();

        return view('GMB.citation.index', compact('client', 'audits', 'gbpNap'));
    }

    public function run(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        set_time_limit(300);

        $location = GmbLocation::where('client_id', $client->id)->active()->first();

        if (!$location) {
            return back()->with('error', 'No active location found for this client.');
        }

        $businessName = $location->location_name ?? $client->name;
        $city         = $location->city ?? $client->city;
        $gbpNap       = $this->scanner->fetchGbpNap($businessName, $city);
        $scanned      = 0;

        foreach ($this->scanner->getDirectories() as $directory) {
            try {
                $found  = $this->scanner->scanDirectory($directory, $businessName, $city);
                $status = empty($found) ? 'pending' : $this->scanner->compareNap($gbpNap, $found);

                GmbCitationAudit::updateOrCreate(
                    [
                        'client_id'       => $client->id,
                        'gmb_location_id' => $location->id,
                        'directory'       => $directory,
                    ],
                    [
                        'gbp_name'      => $gbpNap['name'] ?? null,
                        'gbp_address'   => $gbpNap['address'] ?? null,
                        'gbp_phone'     => $gbpNap['phone'] ?? null,
                        'found_name'    => $found['name'] ?? null,
                        'found_address' => $found['address'] ?? null,
                        'found_phone'   => $found['phone'] ?? null,
                        'status'        => $status,
                        'scanned_at'    => now(),
                    ]
                );

                $scanned++;
            } catch (\Exception $e) {
                Log::error("Citation scan failed for {$directory}: " . $e->getMessage());
            }
        }

        return redirect()
            ->route('gmb.citation.index', $client)
            ->with('success', "Citation scan complete. {$scanned} directories checked.");
    }

    public function manualUpdate(Request $request, GmbCitationAudit $audit)
    {
        $request->validate([
            'found_name'    => 'nullable|string|max:255',
            'found_address' => 'nullable|string|max:500',
            'found_phone'   => 'nullable|string|max:50',
        ]);

        $status = $this->scanner->compareNap(
            [
                'name'    => $audit->gbp_name,
                'address' => $audit->gbp_address,
                'phone'   => $audit->gbp_phone,
            ],
            [
                'name'    => $request->found_name,
                'address' => $request->found_address,
                'phone'   => $request->found_phone,
            ]
        );

        $audit->update([
            'found_name'    => $request->found_name,
            'found_address' => $request->found_address,
            'found_phone'   => $request->found_phone,
            'status'        => $status,
            'scanned_at'    => now(),
        ]);

        return back()->with('success', "{$audit->directory} manually updated. Status: {$status}.");
    }

    public function markCorrected(GmbCitationAudit $audit)
    {
        $audit->update(['status' => 'corrected']);

        return back()->with('success', 'Marked as corrected.');
    }

    public function runAllClients(): void
    {
        set_time_limit(0);

        $clients = Client::where('team_id', self::GMB_TEAM_ID)
            ->where('status', 'active')
            ->get();

        foreach ($clients as $client) {
            try {
                $location = GmbLocation::where('client_id', $client->id)->active()->first();

                if (!$location) continue;

                $businessName = $location->location_name ?? $client->name;
                $city         = $location->city ?? $client->city;
                $gbpNap       = $this->scanner->fetchGbpNap($businessName, $city);

                foreach ($this->scanner->getDirectories() as $directory) {
                    $found  = $this->scanner->scanDirectory($directory, $businessName, $city);
                    $status = empty($found) ? 'pending' : $this->scanner->compareNap($gbpNap, $found);

                    GmbCitationAudit::updateOrCreate(
                        [
                            'client_id'       => $client->id,
                            'gmb_location_id' => $location->id,
                            'directory'       => $directory,
                        ],
                        [
                            'gbp_name'      => $gbpNap['name'] ?? null,
                            'gbp_address'   => $gbpNap['address'] ?? null,
                            'gbp_phone'     => $gbpNap['phone'] ?? null,
                            'found_name'    => $found['name'] ?? null,
                            'found_address' => $found['address'] ?? null,
                            'found_phone'   => $found['phone'] ?? null,
                            'status'        => $status,
                            'scanned_at'    => now(),
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::error("Monthly citation scan failed for client {$client->id}: " . $e->getMessage());
            }
        }
    }
}