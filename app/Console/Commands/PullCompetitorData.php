<?php
// app/Console/Commands/PullCompetitorData.php

namespace App\Console\Commands;

use App\Services\GMB\GmbCompetitorService;
use Illuminate\Console\Command;

class PullCompetitorData extends Command
{
    protected $signature   = 'gmb:pull-competitor-data';
    protected $description = 'Pull competitor ratings and reviews monthly';

    public function handle(GmbCompetitorService $service): void
    {
        $this->info('Pulling competitor data...');
        $service->runForAllClients();
        $this->info('Done.');
    }
}