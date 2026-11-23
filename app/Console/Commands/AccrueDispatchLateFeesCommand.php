<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AccrueDispatchLateFeesCommand extends Command
{
    protected $signature = 'payment:accrue-late-fees';

    protected $description = 'Late fees are entered on each dispatch. This command no longer adds them.';

    public function handle(): int
    {
        $this->info('Automatic late fee is no longer used. Add a late fee on the dispatch instead.');

        return self::SUCCESS;
    }
}
