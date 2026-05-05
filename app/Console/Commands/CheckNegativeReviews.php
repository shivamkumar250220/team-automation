<?php

namespace App\Console\Commands;

use App\Services\GMB\GmbSentimentAlertService;
use Illuminate\Console\Command;

class CheckNegativeReviews extends Command
{
    protected $signature   = 'gmb:check-negative-reviews';
    protected $description = 'Check for new negative reviews and send alerts';

    public function handle(GmbSentimentAlertService $service): void
    {
        $this->info('Checking negative reviews...');
        $service->runForAllClients();
        $this->info('Done.');
    }
}