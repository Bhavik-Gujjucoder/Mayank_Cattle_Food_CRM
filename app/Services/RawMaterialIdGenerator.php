<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Support\FinancialYear;

class RawMaterialIdGenerator
{
    public static function nextMaterialId(): string
    {
        $count = RawMaterial::withTrashed()->count() + 1;

        return 'Raw-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public static function nextCategoryId(): string
    {
        $count = RawMaterialCategory::withTrashed()->count() + 1;

        return 'RMC-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public static function financialYear(?\DateTimeInterface $date = null): string
    {
        return FinancialYear::label($date);
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
