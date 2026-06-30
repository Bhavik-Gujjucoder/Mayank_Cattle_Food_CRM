<?php

namespace Database\Seeders\Concerns;

use Carbon\Carbon;

trait GeneratesBulkIds
{
    protected function financialYear(\DateTimeInterface $date): string
    {
        $date = Carbon::parse($date);
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;

        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    protected function nextRmoId(int $seq, \DateTimeInterface $date): string
    {
        return 'RMO/' . $this->financialYear($date) . '/' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    protected function nextSalesOrderId(int $seq, \DateTimeInterface $date): string
    {
        return 'ORD/' . $this->financialYear($date) . '/' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    protected function bulkSeedTarget(): int
    {
        return max(1, (int) env('BULK_SEED_ORDERS', 10000));
    }

    protected function bulkSeedChunk(): int
    {
        return max(50, (int) env('BULK_SEED_CHUNK', 250));
    }

    protected function bulkSeedForce(): bool
    {
        return filter_var(env('BULK_SEED_FORCE', false), FILTER_VALIDATE_BOOL);
    }

    protected function randomPastDate(int $minDaysAgo = 30, int $maxDaysAgo = 540): Carbon
    {
        return now()->subDays(fake()->numberBetween($minDaysAgo, $maxDaysAgo))->startOfDay();
    }
}
