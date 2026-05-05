<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbCompetitor;
use App\Services\GMB\GmbCompetitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GmbCompetitorController extends Controller
{
    public function __construct(
        private GmbCompetitorService $service
    ) {}

    public function index(Client $client)
    {
        $competitors = GmbCompetitor::where('client_id', $client->id)
            ->with('latestStat')
            ->get();

        $clientStats = $this->service->getClientRating($client);

        return view('GMB.competitors.index', compact('client', 'competitors', 'clientStats'));
    }

    public function store(Request $request, Client $client)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'place_id' => 'required|string|max:255',
        ]);

        GmbCompetitor::create([
            'client_id' => $client->id,
            'name'      => $request->name,
            'place_id'  => $request->place_id,
            'is_manual' => true,
        ]);

        return redirect()->route('gmb.competitors.index', $client);
    }

    public function destroy(Client $client, GmbCompetitor $competitor)
    {
        $competitor->delete();
        return redirect()->route('gmb.competitors.index', $client);
    }

    public function pullNow(Client $client)
    {
        $this->service->processClient($client);

        Log::info('GMB pullNow', [
            'client_id'   => $client->id,
            'client_name' => $client->name,
        ]);

        return redirect()->route('gmb.competitors.index', $client);
    }
}