<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class FinancialYear
{
    public static function startYear(?\DateTimeInterface $date = null): int
    {
        $date = $date ? Carbon::parse($date) : now();

        return $date->month >= 4 ? $date->year : $date->year - 1;
    }

    public static function label(?\DateTimeInterface $date = null): string
    {
        $startYear = self::startYear($date);

        return $startYear . '-' . substr((string) ($startYear + 1), -2);
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function range(?\DateTimeInterface $date = null): array
    {
        $startYear = self::startYear($date);

        return [
            'start' => Carbon::create($startYear, 4, 1)->startOfDay(),
            'end'   => Carbon::create($startYear + 1, 3, 31)->endOfDay(),
        ];
    }

    public static function contains(\DateTimeInterface $instant, ?\DateTimeInterface $reference = null): bool
    {
        $range = self::range($reference);

        return Carbon::parse($instant)->betweenIncluded($range['start'], $range['end']);
    }

    /**
     * Default listing scope: current financial year for the given date column,
     * plus any older rows that still have a pending payment status.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<int|string>  $pendingStatuses
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public static function applyDefaultListingFilter(
        Builder $query,
        array $pendingStatuses,
        string $statusColumn = 'status',
        string $dateColumn = 'created_at',
        ?\DateTimeInterface $reference = null,
    ): Builder {
        $range = self::range($reference);

        $query->where(function (Builder $outer) use ($range, $pendingStatuses, $statusColumn, $dateColumn) {
            $outer->whereBetween($dateColumn, [$range['start'], $range['end']])
                ->orWhere(function (Builder $pending) use ($range, $pendingStatuses, $statusColumn, $dateColumn) {
                    $pending->whereIn($statusColumn, $pendingStatuses)
                        ->where(function (Builder $outsideFy) use ($range, $dateColumn) {
                            $outsideFy->where($dateColumn, '<', $range['start'])
                                ->orWhere($dateColumn, '>', $range['end']);
                        });
                });
        });

        return $query;
    }
}
