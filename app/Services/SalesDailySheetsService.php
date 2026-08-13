<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\User;
use App\Support\SalesScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalesDailySheetsService
{
    /**
     * Open sales-order pipeline grouped for Brand / Broker / Product sheets.
     *
     * @return array{
     *     as_of: Carbon,
     *     brand: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     broker: array{title: string, heading: string, groups: list<array<string, mixed>>},
     *     product: array{title: string, heading: string, groups: list<array<string, mixed>>}
     * }
     */
    public function build(?User $user = null): array
    {
        $rows = $this->pendingRows($user);

        return [
            'as_of'   => now()->startOfDay(),
            'brand'   => $this->section('Brand', 'Brand Wise', $rows, 'brand_name'),
            'broker'  => $this->section('Broker', 'Broker Wise', $rows, 'broker_name'),
            'product' => $this->section('Product', 'Product Wise', $rows, 'product_name'),
        ];
    }

    public function isEmpty(array $payload): bool
    {
        return empty($payload['brand']['groups'])
            && empty($payload['broker']['groups'])
            && empty($payload['product']['groups']);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function pendingRows(?User $user): Collection
    {
        $items = OrderItem::query()
            ->select('order_items.*')
            ->withSum('dispatches as dispatched_qty_sum', 'no_of_bags')
            ->withMax('dispatches as last_dispatch_date', 'dispatch_date')
            ->whereHas('order', function ($q) use ($user) {
                SalesScope::scopeOrders($q, $user);
                $q->where('status', 1);
            })
            ->with([
                'order:id,unique_order_id,broker_id,brand_id,dealer_id,order_date,status',
                'order.brand:id,name',
                'order.broker:id,name',
                'order.dealer:id,user_id,firm_shop_name,city_id',
                'order.dealer.user:id,name',
                'order.dealer.city:id,city_name',
                'product:id,name',
            ])
            ->get();

        return $items
            ->map(fn (OrderItem $item) => $this->mapRow($item))
            ->filter(fn (array $row) => $row['pending'] > 0)
            ->sortBy([
                ['order_date_sort', 'desc'],
                ['party_name', 'asc'],
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapRow(OrderItem $item): array
    {
        $ordered = (int) $item->qty;
        $dispatched = (int) ($item->dispatched_qty_sum ?? 0);
        $pending = max(0, $ordered - $dispatched);
        $orderDate = $item->order?->order_date;

        return [
            'order_id'        => (int) ($item->order?->id ?? 0),
            'order_date'      => $orderDate?->format('d.m.Y') ?? '—',
            'order_date_sort' => $orderDate?->format('Y-m-d') ?? '',
            'party_name'      => $this->formatPartyName($item),
            'total'           => $ordered,
            'rate'            => round((float) $item->unit_price, 2),
            'pending'         => $pending,
            'last_loading'    => $this->formatLastLoading($item->last_dispatch_date ?? null),
            'brand_name'      => $item->order?->brand?->name ?: '—',
            'broker_name'     => $item->order?->broker?->name ?: '—',
            'product_name'    => $item->product?->name ?: '—',
        ];
    }

    protected function formatPartyName(OrderItem $item): string
    {
        $dealer = $item->order?->dealer;
        $name = $dealer?->firm_shop_name
            ?: $dealer?->user?->name
            ?: '—';
        $city = $dealer?->city?->city_name;

        if ($city) {
            return $name . ' - ' . $city;
        }

        return $name;
    }

    protected function formatLastLoading(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{title: string, heading: string, groups: list<array<string, mixed>>}
     */
    protected function section(string $title, string $heading, Collection $rows, string $groupKey): array
    {
        $groups = $rows
            ->groupBy(fn (array $row) => $row[$groupKey] ?: '—')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(function (Collection $groupRows, string $name) {
                return [
                    'name'   => $name,
                    'rows'   => $groupRows->values()->all(),
                    'totals' => [
                        'total'   => (int) $groupRows->sum('total'),
                        'pending' => (int) $groupRows->sum('pending'),
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'title'   => $title,
            'heading' => $heading,
            'groups'  => $groups,
        ];
    }
}
