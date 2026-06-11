<?php

namespace App\Support;

class ProductUnit
{
    public const UNITS = ['Bag', 'Ton', 'KG'];

    public static function genericLabel(): string
    {
        return 'Bag / Ton / KG';
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
