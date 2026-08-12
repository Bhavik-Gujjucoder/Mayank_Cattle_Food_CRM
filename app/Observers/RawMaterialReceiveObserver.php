<?php

namespace App\Observers;

use App\Models\RawMaterialReceive;
use App\Services\RawMaterialCacheService;

class RawMaterialReceiveObserver
{
    public function created(RawMaterialReceive $receive): void
    {
        if ((int) $receive->status === 1) {
            RawMaterialCacheService::applyReceive($receive);
        }

        RawMaterialCacheService::refreshItemExtraAndOrder((int) $receive->raw_material_order_item_id);
    }

    public function updated(RawMaterialReceive $receive): void
    {
        if ($receive->wasChanged('status')) {
            $original = (int) $receive->getOriginal('status');
            $current  = (int) $receive->status;

            if ($original !== 1 && $current === 1) {
                RawMaterialCacheService::applyReceive($receive);
            }

            if ($original === 1 && $current !== 1) {
                RawMaterialCacheService::reverseReceive($receive);
            }
        }

        $itemIds = collect([
            $receive->raw_material_order_item_id,
            $receive->wasChanged('raw_material_order_item_id')
                ? $receive->getOriginal('raw_material_order_item_id')
                : null,
        ])->filter()->unique()->values();

        foreach ($itemIds as $itemId) {
            RawMaterialCacheService::refreshItemExtraAndOrder((int) $itemId);
        }
    }

    public function deleted(RawMaterialReceive $receive): void
    {
        RawMaterialCacheService::refreshItemExtraAndOrder((int) $receive->raw_material_order_item_id);
    }
}
