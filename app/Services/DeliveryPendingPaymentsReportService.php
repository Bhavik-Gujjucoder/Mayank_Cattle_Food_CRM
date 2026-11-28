<?php

namespace App\Services;

use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Support\SalesScope;
use Illuminate\Support\Collection;

class DeliveryPendingPaymentsReportService
{
    public function __construct(
        protected PaymentReceivableService $receivableService
    ) {}

    /**
     * Build brand-grouped report sections for unpaid/partial dispatch payments.
     * Each row is one dealer (or one retail party) and includes every unpaid
     * dispatch across that party's orders.
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
                'order:id,unique_order_id,brand_id,dealer_id,broker_id,payment_type,order_type,party_name',
                'order.brand:id,name',
                'order.broker:id,name',
                'order.dealer:id,city_id,user_id,firm_shop_name',
                'order.dealer.city:id,city_name',
                'order.dealer.user:id,name',
                'orderItem:id,unit_price',
            ])
            ->get();

        $rows = $unpaidDispatches
            ->filter(fn ($dispatch) => $dispatch->order !== null)
            ->groupBy(fn ($dispatch) => $this->partyGroupKey($dispatch->order))
            ->map(function ($dispatches) use ($today) {
                return $this->buildPartyRow($dispatches, $today);
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
        $paymentType = $items[0]['payment_type'] ?? ($row['payment_type'] ?? 'cash');
        $orderIds = collect($items)->pluck('order_id')->unique()->all();
        $orders = collect($row['orders'] ?? [])
            ->filter(fn (array $order) => in_array($order['order_id'], $orderIds, true))
            ->values()
            ->all();

        $row['orders']               = $orders;
        $row['order_id']             = $orders[0]['order_id'] ?? ($row['order_id'] ?? null);
        $row['order_label']          = collect($orders)->pluck('order_label')->implode("\n");
        $row['broker_name']          = self::brokerLabel($orders);
        $row['pending_days_items']   = $items;
        $row['pending_days_display'] = collect($items)->pluck('days')->implode(' - ');
        $row['pending_days_label']   = self::formatPendingDaysLabel($items);
        $row['max_pending_days']     = $maxDays;
        $row['payment_type']         = $paymentType;
        $row['days_emphasis_class']  = $this->daysEmphasisClass($maxDays, $paymentType);
        $row['total_late_fee']       = round(collect($items)->sum('accrued_late_fee'), 2);
        $row['total_late_fee_source'] = $this->receivableService->combineLateFeeSources(
            collect($items)->filter(fn (array $item) => (float) ($item['accrued_late_fee'] ?? 0) > 0)->pluck('late_fee_source')
        );
        $row['total_balance_due']    = round(collect($items)->sum('balance_due'), 2);

        return $row;
    }

    /**
     * "15 (5 Jan 2026)" or, when an order code is present,
     * "15 (5 Jan 2026, ORD/2026-27/0039)".
     *
     * @param  array<int, array{days: int, dispatch_date: string, order_code?: string}>  $items
     */
    public static function formatPendingDaysLabel(array $items): string
    {
        return collect($items)
            ->map(function (array $item) {
                $when = $item['dispatch_date'];
                if (! empty($item['order_code'])) {
                    $when .= ', ' . $item['order_code'];
                }

                return $item['days'] . ' (' . $when . ')';
            })
            ->implode(' - ');
    }

    /**
     * Unique broker names for the orders still on this dealer row.
     *
     * @param  array<int, array{broker_name?: string}>  $orders
     */
    public static function brokerLabel(array $orders): string
    {
        $names = collect($orders)
            ->pluck('broker_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && $name !== '—')
            ->unique()
            ->values();

        return $names->isEmpty() ? '—' : $names->implode(', ');
    }

    /**
     * One row per dealer inside a brand. Retail orders with a party name share
     * a row; a soda order with no dealer stays on its own row.
     */
    protected function partyGroupKey(OrderManagement $order): string
    {
        $brandId = (int) $order->brand_id;

        if ($order->isRetail()) {
            $party = mb_strtolower(trim((string) ($order->party_name ?? '')));
            if ($party !== '') {
                return $brandId . '|retail|' . $party;
            }

            return $brandId . '|order|' . (int) $order->id;
        }

        if ($order->dealer_id) {
            return $brandId . '|dealer|' . (int) $order->dealer_id;
        }

        return $brandId . '|order|' . (int) $order->id;
    }

    /**
     * @param  Collection<int, DispatchManagement>  $dispatches
     * @return array<string, mixed>|null
     */
    protected function buildPartyRow(Collection $dispatches, \Illuminate\Support\Carbon $today): ?array
    {
        $orders = $dispatches
            ->map(fn ($dispatch) => $dispatch->order)
            ->filter()
            ->unique('id')
            ->sortBy('id')
            ->values();

        $order = $orders->first();
        if (! $order) {
            return null;
        }

        $pendingDaysItems = $dispatches
            ->filter(fn ($dispatch) => $dispatch->dispatch_date !== null && $dispatch->order)
            ->map(function ($dispatch) use ($today) {
                $dispatchOrder = $dispatch->order;
                $paymentType = $this->receivableService->paymentTypeFor($dispatchOrder);
                $summary = $this->receivableService->summarizeDispatch($dispatch, $today);

                return [
                    'dispatch_id'      => (int) $dispatch->id,
                    'order_id'         => (int) $dispatchOrder->id,
                    'order_code'       => (string) $dispatchOrder->unique_order_id,
                    'days'             => $summary['days_since_dispatch'],
                    'dispatch_date'    => $dispatch->dispatch_date->format('d M Y'),
                    'overdue_days'     => $summary['overdue_days'],
                    'base_amount'      => $summary['base_amount'],
                    'accrued_late_fee' => $summary['accrued_late_fee'],
                    'late_fee_source'  => $dispatch->late_fee_source,
                    'total_receivable' => $summary['total_receivable'],
                    'balance_due'      => $summary['balance_due'],
                    'payment_type'     => $paymentType,
                ];
            })
            ->sortByDesc('days')
            ->values();

        if ($pendingDaysItems->isEmpty()) {
            return null;
        }

        $orderRows = $orders->map(fn (OrderManagement $partyOrder) => [
            'order_id'    => (int) $partyOrder->id,
            'order_label' => $partyOrder->unique_order_id . ' · ' . $partyOrder->orderTypeLabel(),
            'broker_name' => trim((string) ($partyOrder->broker?->name ?? '')),
        ])->all();

        $paymentType = (string) $pendingDaysItems->first()['payment_type'];
        $maxDays = (int) $pendingDaysItems->first()['days'];
        $dealer = $order->dealer;

        return [
            'brand_id'              => (int) $order->brand_id,
            'brand_name'            => $order->brand?->name ?? '—',
            'city_name'             => $dealer?->city?->city_name ?? '—',
            'dealer_name'           => $order->partyLabel(),
            'order_id'              => $orderRows[0]['order_id'],
            'order_label'           => collect($orderRows)->pluck('order_label')->implode("\n"),
            'broker_name'           => self::brokerLabel($orderRows),
            'orders'                => $orderRows,
            'payment_type'          => $paymentType,
            'pending_days_items'    => $pendingDaysItems->all(),
            'pending_days_display'  => $pendingDaysItems->pluck('days')->implode(' - '),
            'pending_days_label'    => self::formatPendingDaysLabel($pendingDaysItems->all()),
            'max_pending_days'      => $maxDays,
            'days_emphasis_class'   => $this->daysEmphasisClass($maxDays, $paymentType),
            'total_late_fee'        => round($pendingDaysItems->sum('accrued_late_fee'), 2),
            'total_late_fee_source' => $this->receivableService->combineLateFeeSources(
                $pendingDaysItems->filter(fn (array $item) => (float) $item['accrued_late_fee'] > 0)->pluck('late_fee_source')
            ),
            'total_balance_due'     => round($pendingDaysItems->sum('balance_due'), 2),
        ];
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
    protected function daysEmphasisClass(int $maxDays, string $paymentType = 'cash'): string
    {
        return match ($this->dayAgingLevel($maxDays, $paymentType)) {
            'low'  => 'text-success',
            'mid'  => 'text-warning',
            default => 'text-danger',
        };
    }

    /**
     * Aging band for per-dispatch styling based on Cash/Credit due days settings.
     *
     * @return 'low'|'mid'|'high'
     */
    public function dayAgingLevel(int $days, string $paymentType = 'cash'): string
    {
        return $this->receivableService->dayAgingLevel($days, $paymentType);
    }

    /**
     * Static helper for Blade views (resolves service from container).
     *
     * @return 'low'|'mid'|'high'
     */
    public static function dayAgingLevelFor(int $days, string $paymentType = 'cash'): string
    {
        return app(self::class)->dayAgingLevel($days, $paymentType);
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

    public function paymentDueDays(string $paymentType = 'cash'): int
    {
        return $this->receivableService->paymentDueDays($paymentType);
    }

    /**
     * @return array{cash: int, credit: int}
     */
    public function paymentDueDaysByType(): array
    {
        return [
            'cash'   => $this->paymentDueDays('cash'),
            'credit' => $this->paymentDueDays('credit'),
        ];
    }
}
