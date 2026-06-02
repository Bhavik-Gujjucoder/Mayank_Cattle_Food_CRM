<?php

namespace App\Observers;

use App\Models\RawMaterialOrderItem;
use App\Services\RawMaterialCacheService;

class RawMaterialOrderItemObserver
{
    public function creating(RawMaterialOrderItem $item): void
    {
        RawMaterialCacheService::initializeOrderItem($item);
    }

    public function created(RawMaterialOrderItem $item): void
    {
        $item->load('order');
        if ($item->order) {
            RawMaterialCacheService::recalculateOrder($item->order);
        }
        RawMaterialCacheService::recalculateMaterialPrices($item->raw_material_id);
    }

    public function updated(RawMaterialOrderItem $item): void
    {
        $item->load('order');
        if ($item->order) {
            RawMaterialCacheService::recalculateOrder($item->order);
        }
        RawMaterialCacheService::recalculateMaterialPrices($item->raw_material_id);
    }

    public function deleted(RawMaterialOrderItem $item): void
    {
        if ($order = $item->order) {
            RawMaterialCacheService::recalculateOrder($order);
        }
        RawMaterialCacheService::recalculateMaterialPrices($item->raw_material_id);
    }
}
