<?php

namespace App\Console\Commands;

use App\Services\PaymentReceivableService;
use Illuminate\Console\Command;

class AccrueDispatchLateFeesCommand extends Command
{
    protected $signature = 'payment:accrue-late-fees';

    protected $description = 'Accrue daily late payment fees for unpaid/partial dispatches past the configured due days';

    public function handle(PaymentReceivableService $service): int
    {
        if (! $service->isLateFeeEnabled()) {
            $this->info('Late fee accrual skipped (payment due days or amount is zero).');

            return self::SUCCESS;
        }

        $stats = $service->accrueAll();

        $this->info(sprintf(
            'Processed %d dispatch(es); accrued fees on %d dispatch(es); total added: %s',
            $stats['processed'],
            $stats['accrued'],
            PaymentReceivableService::formatMoney($stats['amount'])
        ));

        return self::SUCCESS;
    }
}
