<?php

namespace App\Observers;

use App\Models\RawMaterialReceive;
use App\Services\RawMaterialCacheService;

class RawMaterialReceiveObserver
{
    public function saved(RawMaterialReceive $receive): void
    {
        if (! $receive->wasChanged('status') && ! $receive->wasRecentlyCreated) {
            return;
        }

        $original = (int) $receive->getOriginal('status');
        $current  = (int) $receive->status;

        if ($original !== 1 && $current === 1) {
            RawMaterialCacheService::applyReceive($receive);
        }

        if ($original === 1 && $current !== 1) {
            RawMaterialCacheService::reverseReceive($receive);
        }
    }
}
