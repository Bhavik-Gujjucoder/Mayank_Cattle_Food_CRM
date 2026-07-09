<?php

namespace App\Support;

class ProductUnit
{
    public const UNITS = ['Bag', 'Ton', 'KG'];

    /** kg per bag — used for bag-equivalent totals (weekly report footer). */
    public const KG_PER_BAG = 60;

    /** kg per ton. */
    public const KG_PER_TON = 1000;

    public static function genericLabel(): string
    {
        return 'Bag / Ton / KG';
    }

    /**
     * Convert a quantity in the product's unit to bag-equivalents.
     * 1 Bag = 60 kg, 1 Ton = 1000 kg → 1 Ton = 1000/60 bags, 1 KG = 1/60 bag.
     */
    public static function toBags(float|int $qty, ?string $unit = null): float
    {
        $qty = (float) $qty;

        return match ($unit) {
            'Bag' => $qty,
            'Ton' => $qty * (self::KG_PER_TON / self::KG_PER_BAG),
            'KG'  => $qty / self::KG_PER_BAG,
            default => $qty,
        };
    }

    public static function quantityFieldLabel(?string $unit = null): string
    {
        if ($unit && in_array($unit, self::UNITS, true)) {
            return 'No of ' . $unit;
        }

        return 'No of ' . self::genericLabel();
    }

    public static function requiredMessage(): string
    {
        return 'No of bag / ton / kg is required.';
    }

    public static function minMessage(): string
    {
        return 'No of bag / ton / kg must be at least 1.';
    }

    public static function maxAllowedHint(int $qty, ?string $unit = null): string
    {
        if ($unit && in_array($unit, self::UNITS, true)) {
            return 'Maximum allowed: ' . $qty . ' ' . $unit;
        }

        return 'Maximum allowed: ' . $qty . ' bag / ton / kg';
    }

    public static function formatWithUnit(int|float $qty, ?string $unit = null): string
    {
        if ($unit && in_array($unit, self::UNITS, true)) {
            return trim($qty . ' ' . $unit);
        }

        return (string) $qty;
    }

    public static function dispatchedSuffix(?string $unit = null): string
    {
        if (! $unit) {
            return 'unit(s)';
        }

        return match ($unit) {
            'Bag' => 'bag(s)',
            'Ton' => 'ton(s)',
            'KG'  => 'kg',
            default => strtolower($unit) . '(s)',
        };
    }
}
