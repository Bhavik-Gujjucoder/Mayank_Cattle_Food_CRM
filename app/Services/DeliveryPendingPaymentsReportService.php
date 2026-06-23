<?php

namespace App\Services;

use App\Models\DispatchManagement;
use App\Support\SalesScope;
use Illuminate\Support\Collection;

class DeliveryPendingPaymentsReportService
{
    public function __construct(
        protected PaymentReceivableService $receivableService
    ) {}

    /**
     * Build brand-grouped report sections for unpaid/partial dispatch payments.
     *
     * @return Collection<int, array{brand_id: int, brand_name: string, rows: array<int, array>}>
     */
    public function build(?string $brandFilter = 'all', ?int $minDays = null): Collection
    {
        $today = now()->startOfDay();

        $unpaidDispatches = SalesScope::scopeDispatches(DispatchManagement::query())
            ->whereIn('status', DispatchManagement::pendingPaymentStatuses())
            ->whereHas('order', fn ($q) => $q->whereNull('deleted_at'))
            ->with([
                'order:id,unique_order_id,brand_id,dealer_id,broker_id',
                'order.brand:id,name',
                'order.dealer:id,city_id,user_id,firm_shop_name',
                'order.dealer.city:id,city_name',
                'order.dealer.user:id,name',
                'orderItem:id,unit_price',
            ])
            ->get();

        $rows = $unpaidDispatches
            ->groupBy('order_id')
            ->map(function ($dispatches) use ($today) {
                $order = $dispatches->first()->order;
                if (! $order) {
                    return null;
                }

                $pendingDaysItems = $dispatches
                    ->filter(fn ($d) => $d->dispatch_date !== null)
                    ->map(function ($d) use ($today) {
                        $summary = $this->receivableService->summarizeDispatch($d, $today);

                        return [
                            'dispatch_id'      => (int) $d->id,
                            'days'             => $summary['days_since_dispatch'],
                            'dispatch_date'    => $d->dispatch_date->format('d M Y'),
                            'overdue_days'     => $summary['overdue_days'],
                            'base_amount'      => $summary['base_amount'],
                            'accrued_late_fee' => $summary['accrued_late_fee'],
                            'total_receivable' => $summary['total_receivable'],
                            'balance_due'      => $summary['balance_due'],
                        ];
                    })
                    ->sortByDesc('days')
                    ->values();

                if ($pendingDaysItems->isEmpty()) {
                    return null;
                }

                $dealer = $order->dealer;
                $maxDays = (int) $pendingDaysItems->first()['days'];

                return [
                    'brand_id'              => (int) $order->brand_id,
                    'brand_name'            => $order->brand?->name ?? '—',
                    'city_name'             => $dealer?->city?->city_name ?? '—',
                    'dealer_name'           => $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—',
                    'order_id'              => (int) $order->id,
                    'order_label'           => $order->unique_order_id,
                    'pending_days_items'    => $pendingDaysItems->all(),
                    'pending_days_display'  => $pendingDaysItems->pluck('days')->implode(' - '),
                    'pending_days_label'    => self::formatPendingDaysLabel($pendingDaysItems->all()),
                    'max_pending_days'      => $maxDays,
                    'days_emphasis_class'   => $this->daysEmphasisClass($maxDays),
                    'total_late_fee'        => round($pendingDaysItems->sum('accrued_late_fee'), 2),
                    'total_balance_due'     => round($pendingDaysItems->sum('balance_due'), 2),
                ];
            })
            ->filter()
            ->values();

        if ($minDays !== null) {
            $rows = $rows
                ->map(fn (array $row) => $this->applyMinDaysFilter($row, $minDays))
                ->filter()
                ->values();
        }

        if ($brandFilter && $brandFilter !== 'all') {
            $brandId = (int) $brandFilter;
            $rows = $rows->where('brand_id', $brandId)->values();
        }

        return $rows
            ->sortBy([
                ['brand_id', 'asc'],
                ['city_name', 'asc'],
                ['dealer_name', 'asc'],
                ['order_id', 'asc'],
            ])
            ->groupBy('brand_id')
            ->map(function ($brandRows, $brandId) {
                $first = $brandRows->first();

                return [
                    'brand_id'   => (int) $brandId,
                    'brand_name' => $first['brand_name'],
                    'rows'       => $brandRows->values()->all(),
                ];
            })
            ->sortBy('brand_id')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    protected function applyMinDaysFilter(array $row, int $minDays): ?array
    {
        $items = collect($row['pending_days_items'] ?? [])
            ->filter(fn (array $item) => (int) $item['days'] >= $minDays)
            ->sortByDesc('days')
            ->values()
            ->all();

        if ($items === []) {
            return null;
        }

        $maxDays = (int) $items[0]['days'];

        $row['pending_days_items']   = $items;
        $row['pending_days_display'] = collect($items)->pluck('days')->implode(' - ');
        $row['pending_days_label']   = self::formatPendingDaysLabel($items);
        $row['max_pending_days']     = $maxDays;
        $row['days_emphasis_class']  = $this->daysEmphasisClass($maxDays);
        $row['total_late_fee']       = round(collect($items)->sum('accrued_late_fee'), 2);
        $row['total_balance_due']    = round(collect($items)->sum('balance_due'), 2);

        return $row;
    }

    /**
     * Same format as Excel export: "15 (5 Jan 2026) - 13 (7 Jan 2026)".
     *
     * @param  array<int, array{days: int, dispatch_date: string}>  $items
     */
    public static function formatPendingDaysLabel(array $items): string
    {
        return collect($items)
            ->map(fn (array $item) => $item['days'] . ' (' . $item['dispatch_date'] . ')')
            ->implode(' - ');
    }

    /** Section title for UI / Excel (avoids "Mayank Brand Brand"). */
    public static function formatBrandSectionTitle(string $brandName): string
    {
        $name = trim($brandName);
        if ($name === '' || $name === '—') {
            return '—';
        }
        if (preg_match('/\bbrand\s*$/iu', $name)) {
            return $name;
        }

        return $name . ' Brand';
    }

    /** Bootstrap text class from max days (row summary / mobile badge). */
    protected function daysEmphasisClass(int $maxDays): string
    {
        return match ($this->dayAgingLevel($maxDays)) {
            'low'  => 'text-success',
            'mid'  => 'text-warning',
            default => 'text-danger',
        };
    }

    /**
     * Aging band for per-dispatch styling based on general settings payment_due_days.
     *
     * @return 'low'|'mid'|'high'
     */
    public function dayAgingLevel(int $days): string
    {
        return $this->receivableService->dayAgingLevel($days);
    }

    /**
     * Static helper for Blade views (resolves service from container).
     *
     * @return 'low'|'mid'|'high'
     */
    public static function dayAgingLevelFor(int $days): string
    {
        return app(self::class)->dayAgingLevel($days);
    }

    /**
     * Fill / border / font colors for Excel chip cells and exports.
     *
     * @return array{fill: string, border: string, num: string, date: string}
     */
    public static function dayAgingColors(string $level): array
    {
        return match ($level) {
            'low' => [
                'fill'   => 'F0FDF4',
                'border' => 'BBF7D0',
                'num'    => '15803D',
                'date'   => '64748B',
            ],
            'mid' => [
                'fill'   => 'FFFBEB',
                'border' => 'FDE68A',
                'num'    => 'B45309',
                'date'   => '64748B',
            ],
            default => [
                'fill'   => 'FEF2F2',
                'border' => 'FECACA',
                'num'    => 'B91C1C',
                'date'   => '64748B',
            ],
        };
    }

    public function paymentDueDays(): int
    {
        return $this->receivableService->paymentDueDays();
    }
}
