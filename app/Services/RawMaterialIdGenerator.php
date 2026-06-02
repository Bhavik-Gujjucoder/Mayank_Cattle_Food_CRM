<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;

class RawMaterialIdGenerator
{
    public static function nextMaterialId(): string
    {
        $count = RawMaterial::withTrashed()->count() + 1;

        return 'Raw-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public static function financialYear(?\DateTimeInterface $date = null): string
    {
        $date      = $date ? \Carbon\Carbon::parse($date) : now();
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    public static function nextOrderId(?\DateTimeInterface $orderDate = null): string
    {
        $fy    = self::financialYear($orderDate);
        $count = RawMaterialOrder::withTrashed()
            ->where('order_unique_id', 'like', 'RMO/' . $fy . '/%')
            ->count() + 1;

        return 'RMO/' . $fy . '/' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
