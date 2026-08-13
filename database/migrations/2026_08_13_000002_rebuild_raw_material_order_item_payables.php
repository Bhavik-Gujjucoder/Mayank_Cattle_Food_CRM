<?php

use App\Models\RawMaterialOrderItem;
use App\Services\RawMaterialCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('raw_material_order_items', 'tax_percent')) {
            return;
        }

        RawMaterialOrderItem::query()->each(function (RawMaterialOrderItem $item) {
            RawMaterialCacheService::rebuildItemPayables($item);
        });
    }

    public function down(): void
    {
        // Payables are derived; no reverse snapshot is stored.
    }
};
