<?php

namespace App\Support;

use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Services\RawMaterial\RawMaterialFilterService;
use App\Services\RawMaterialCacheService;
use Illuminate\Support\Collection;

/** Shared rows for raw material order Excel and PDF, laid out like the soda/order export. */
class RawMaterialOrderExportRows
{
    private const COLUMNS = 12;

    /**
     * @param  Collection<int, RawMaterialOrder>  $orders
     * @return array{
     *     groups: list<array{meta: list<array{label: string, value: string}>, materials: list<array{line: list<int|float|string>, receives: list<list<int|float|string>>}>}>
     * }
     */
    public static function build(Collection $orders): array
    {
        $groups = [];

        foreach ($orders as $order) {
            $groups[] = self::group($order);
        }

        return [
            'groups' => $groups,
        ];
    }

    /**
     * @param  Collection<int, RawMaterialOrder>  $orders
     * @return array{rows: list<list<int|float|string>>, kinds: list<string>}
     */
    public static function excel(Collection $orders, string $title): array
    {
        $built = self::build($orders);
        $rows = [];
        $kinds = [];

        $push = function (array $row, string $kind) use (&$rows, &$kinds) {
            $rows[] = array_pad($row, self::COLUMNS, '');
            $kinds[] = $kind;
        };

        $push([$title], 'title');
        $push(['Exported ' . now()->format('d M Y') . ' · ' . count($built['groups']) . ' orders'], 'subtitle');
        $push([], 'blank');
        $push(['Orders'], 'section');

        foreach ($built['groups'] as $group) {
            $push(array_column($group['meta'], 'label'), 'order-head');
            $push(array_column($group['meta'], 'value'), 'order-values');

            if ($group['materials'] === []) {
                $push(['No materials'], 'empty');
                $push([], 'blank');
                continue;
            }

            foreach ($group['materials'] as $material) {
                $push(self::lineHeadings(), 'product-head');
                $push($material['line'], 'product');
                $push(self::receiveHeadings(), 'dispatch-head');

                if ($material['receives'] === []) {
                    $push(['No receive'], 'empty');
                    continue;
                }

                foreach ($material['receives'] as $receive) {
                    $push($receive, 'dispatch');
                }
            }

            $push([], 'blank');
        }

        return ['rows' => $rows, 'kinds' => $kinds];
    }

    /** @return list<string> */
    public static function lineHeadings(): array
    {
        return [
            'Category',
            'Material',
            'Total Qty (tons)',
            'Extra Qty',
            'Pending Qty',
            'Received Qty',
            'Price/kg',
            'Total Price',
            'Freight',
            'Status',
        ];
    }

    /** @return list<string> */
    public static function receiveHeadings(): array
    {
        return [
            'Received Date',
            'Qty (tons)',
            'Freight / ton',
            'Freight amount',
            'Status',
        ];
    }

    /**
     * @return array{meta: list<array{label: string, value: string}>, materials: list<array{line: list<int|float|string>, receives: list<list<int|float|string>>}>}
     */
    private static function group(RawMaterialOrder $order): array
    {
        $meta = [
            ['label' => 'Order ID', 'value' => (string) $order->order_unique_id],
            ['label' => 'Supplier Broker', 'value' => $order->supplierBroker?->name ?? '—'],
            ['label' => 'Supplier', 'value' => $order->supplier?->name ?? '—'],
            ['label' => 'Supplier Order ID', 'value' => $order->supplier_order_id ?: '—'],
            ['label' => 'Price Basis', 'value' => $order->price_basis ?: '—'],
            ['label' => 'Order Date', 'value' => $order->order_date?->format('d-m-Y') ?? '—'],
            ['label' => 'Total Qty (tons)', 'value' => rmQtyInput($order->total_qty)],
            ['label' => 'Extra Qty', 'value' => rmQtyInput($order->extraQtyTotal())],
            ['label' => 'Total Price (₹)', 'value' => number_format((float) $order->total_price, 2, '.', '')],
            ['label' => 'Advance Payment (₹)', 'value' => number_format((float) $order->advance_payment, 2, '.', '')],
            ['label' => 'Total Freight (₹)', 'value' => number_format((float) $order->total_freight, 2, '.', '')],
            ['label' => 'Status', 'value' => RawMaterialFilterService::orderStatusLabel((int) $order->status)],
        ];

        $receivesByItem = $order->receives->groupBy(
            fn (RawMaterialReceive $receive) => (int) $receive->raw_material_order_item_id
        );
        $materials = [];

        foreach ($order->items as $item) {
            $materials[] = [
                'line' => self::line($item),
                'receives' => self::receiveRows($receivesByItem->get($item->id, collect())),
            ];
        }

        $matchedItemIds = $order->items->pluck('id')->map(fn ($id) => (int) $id);
        $orphans = $order->receives->reject(
            fn (RawMaterialReceive $receive) => $matchedItemIds->contains((int) $receive->raw_material_order_item_id)
        );
        if ($orphans->isNotEmpty()) {
            $materials[] = [
                'line' => ['—', 'Other', 0, 0, 0, rmQty($orphans->sum('qty')), '—', '—', '—', '—'],
                'receives' => self::receiveRows($orphans),
            ];
        }

        return [
            'meta' => $meta,
            'materials' => $materials,
        ];
    }

    /** @return list<int|float|string> */
    private static function line(RawMaterialOrderItem $item): array
    {
        return [
            $item->rawMaterial?->category?->name ?? '—',
            $item->rawMaterial?->name ?? '—',
            rmQty($item->total_qty),
            rmQty($item->extra_qty),
            rmQty($item->pending_qty),
            rmQty($item->received_qty),
            number_format((float) $item->price, 2, '.', ''),
            number_format(RawMaterialCacheService::itemTotalAmount($item), 2, '.', ''),
            number_format((float) $item->total_freight, 2, '.', ''),
            RawMaterialFilterService::orderItemStatusLabel((int) $item->status),
        ];
    }

    /**
     * @param  Collection<int, RawMaterialReceive>  $receives
     * @return list<list<int|float|string>>
     */
    private static function receiveRows(Collection $receives): array
    {
        return $receives->map(function (RawMaterialReceive $receive) {
            return [
                $receive->received_date?->format('d-m-Y') ?? '—',
                rmQty($receive->qty),
                number_format((float) $receive->freight, 2, '.', ''),
                number_format(RawMaterialCacheService::receiveFreightAmount($receive), 2, '.', ''),
                RawMaterialFilterService::receiveStatusLabel((int) $receive->status),
            ];
        })->values()->all();
    }
}
