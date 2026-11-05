<?php

namespace App\Support;

class LessFreight
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = round((float) $value, 2);

        if ($amount <= 0) {
            return null;
        }

        return number_format($amount, 2, '.', '');
    }

    public static function perQty(mixed $value): float
    {
        return round(max(0, (float) ($value ?? 0)), 2);
    }

    public static function gross(int $qty, float $unitPrice): float
    {
        return round(max(0, $qty) * max(0, $unitPrice), 2);
    }

    public static function deduction(int $qty, mixed $perQty): float
    {
        return round(max(0, $qty) * self::perQty($perQty), 2);
    }

    public static function finalAmount(int $qty, float $unitPrice, mixed $perQty): float
    {
        return round(max(0, self::gross($qty, $unitPrice) - self::deduction($qty, $perQty)), 2);
    }

    public static function validationMessage(mixed $rate, float $unitPrice): ?string
    {
        if ($rate === null || $rate === '') {
            return null;
        }

        if (! is_numeric($rate)) {
            return 'Please enter a valid less freight amount.';
        }

        if ((float) $rate < 0) {
            return 'Less freight cannot be negative.';
        }

        if (round((float) $rate, 2) > round(max(0, $unitPrice), 2)) {
            return 'Less freight per qty cannot be more than the unit price (₹ '
                . number_format($unitPrice, 2) . ').';
        }

        return null;
    }
}
