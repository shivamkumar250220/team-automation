<?php

namespace App\Http\Controllers\GMB;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\GmbReviewAlert;
use App\Services\GMB\GmbSentimentAlertService;

class GmbReviewAlertController extends Controller
{
    const GMB_TEAM_ID = 2;

    public function __construct(
        private GmbSentimentAlertService $service
    ) {}

    public function index(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $alerts = GmbReviewAlert::where('client_id', $client->id)
            ->latest()
            ->get();

        return view('GMB.review-alerts.index', compact('client', 'alerts'));
    }

    public function runNow(Client $client)
    {
        abort_if($client->team_id !== self::GMB_TEAM_ID, 403);

        $this->service->processClient($client);

        return redirect()->route('gmb.review-alerts.index', $client);
    }
}