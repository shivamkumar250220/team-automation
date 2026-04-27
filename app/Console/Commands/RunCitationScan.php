<?php

namespace App\Console\Commands;

use App\Http\Controllers\GMB\CitationScanController;
use Illuminate\Console\Command;

class RunCitationScan extends Command
{
    protected $signature   = 'gmb:citation-scan';
    protected $description = 'Run monthly citation NAP scan for all active GMB clients';

    public function handle(CitationScanController $controller): void
    {
        $this->info('Running citation scan...');
        $controller->runAllClients();
        $this->info('Done.');
    }
}