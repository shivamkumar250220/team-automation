<?php

namespace App\Console\Commands;

use App\Http\Controllers\GMB\GmbInsightController;
use Illuminate\Console\Command;

class PullGmbInsights extends Command
{
    protected $signature   = 'gmb:pull-insights';
    protected $description = 'Pull GMB insights for all active clients';

    public function handle(GmbInsightController $controller): void
    {
        $this->info('Pulling GMB insights...');
        $controller->pullAllClients();
        $this->info('Done.');
    }
}