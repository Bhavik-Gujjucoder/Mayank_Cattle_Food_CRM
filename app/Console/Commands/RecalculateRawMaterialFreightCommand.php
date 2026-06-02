<?php

namespace App\Console\Commands;

use App\Models\RawMaterialOrderItem;
use App\Services\RawMaterialCacheService;
use Illuminate\Console\Command;

class RecalculateRawMaterialFreightCommand extends Command
{
    protected $signature = 'raw-material:recalculate-freight';

    protected $description = 'Recalculate order item total_freight from receives (freight × qty) and refresh price_avg, order totals, and material average_price';

    public function handle(): int
    {
        $count = 0;
        RawMaterialOrderItem::query()->each(function (RawMaterialOrderItem $item) use (&$count) {
            RawMaterialCacheService::recalculateItemFreightFromReceives($item);
            $count++;
        });

        $this->info("Recalculated freight for {$count} order item(s).");

        return self::SUCCESS;
    }
}
