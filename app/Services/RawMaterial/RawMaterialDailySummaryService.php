<?php

namespace App\Services\RawMaterial;

use App\Models\RawMaterial;
use App\Models\RawMaterialOrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RawMaterialDailySummaryService
{
    /**
     * Build open-pipeline daily summary for dashboard and export.
     *
     * @return array{
     *     summary_date: Carbon,
     *     date_from: ?string,
     *     date_to: ?string,
     *     rows: Collection<int, array<string, mixed>>,
     *     totals: array<string, mixed>,
     *     materials: Collection<int, RawMaterial>
     * }
     */
    public function build(
        ?int $rawMaterialId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        $items = $this->openOrderItems($rawMaterialId, $dateFrom, $dateTo);

        $rows = $items
            ->map(fn (RawMaterialOrderItem $item) => $this->mapRow($item))
            ->sortBy([
                ['order_date_sort', 'desc'],
                ['party_name', 'asc'],
                ['material_name', 'asc'],
            ])
            ->values();

        return [
            'summary_date' => now()->startOfDay(),
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'rows'         => $rows,
            'totals'       => $this->buildTotals($rows),
            'materials'    => $this->activeMaterials(),
        ];
    }

    /**
     * @return Collection<int, RawMaterialOrderItem>
     */
    protected function openOrderItems(
        ?int $rawMaterialId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): Collection {
        return RawMaterialOrderItem::query()
            ->whereIn('status', [0, 1])
            ->whereHas('order', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', '!=', 3);

                if ($dateFrom) {
                    $q->whereDate('order_date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $q->whereDate('order_date', '<=', $dateTo);
                }
            })
            ->when($rawMaterialId, fn ($q) => $q->where('raw_material_id', $rawMaterialId))
            ->with([
                'order:id,order_unique_id,supplier_id,supplier_broker_id,order_date,supplier_order_id',
                'order.supplier:id,name,city_id',
                'order.supplier.city:id,city_name',
                'order.supplierBroker:id,name',
                'rawMaterial:id,name',
                'receives' => fn ($q) => $q
                    ->where('status', 0)
                    ->select('id', 'raw_material_order_item_id', 'qty', 'status'),
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapRow(RawMaterialOrderItem $item): array
    {
        $onRoadQty = (int) $item->receives->sum('qty');
        $receivedQty = (int) $item->received_qty;
        $pendingQty = (int) $item->pending_qty;
        $displayPendingQty = max(0, $pendingQty - $onRoadQty);
        $totalQty = (int) $item->total_qty;
        $rate = round((float) $item->price, 2);
        $freight = round((float) $item->total_freight, 2);

        return [
            'order_id'              => (int) ($item->order?->id ?? 0),
            'order_item_id'         => (int) $item->id,
            'order_unique_id'       => $item->order?->order_unique_id ?? '—',
            'order_date'            => $item->order?->order_date?->format('d.m.Y') ?? '—',
            'order_date_sort'       => $item->order?->order_date?->format('Y-m-d') ?? '',
            'supplier_broker_name'  => $item->order?->supplierBroker?->name ?? '—',
            'party_name'            => $this->formatPartyName($item),
            'material_id'           => (int) ($item->raw_material_id ?? 0),
            'material_name'         => $item->rawMaterial?->name ?? '—',
            'total_qty'             => $totalQty,
            'on_road_qty'           => $onRoadQty,
            'unloading_qty'         => $receivedQty,
            'pending_qty'           => $displayPendingQty,
            'pipeline_pending_qty'  => $pendingQty,
            'rate'                  => $rate,
            'average'               => $this->displayAverage($rate, (float) $item->price_avg, $freight),
            'pending_amount'        => round((float) $item->pending_price, 2),
            'received_amount'       => round((float) $item->received_price, 2),
            'freight'               => $freight,
        ];
    }

    protected function formatPartyName(RawMaterialOrderItem $item): string
    {
        $supplier = $item->order?->supplier;
        $name = $supplier?->name ?? '—';
        $city = $supplier?->city?->city_name;

        if ($city) {
            return $name . ' - ' . $city;
        }

        return $name;
    }

    protected function displayAverage(float $rate, float $priceAvg, float $freight): float
    {
        if ($freight <= 0) {
            return round($rate, 2);
        }

        return round($priceAvg > 0 ? $priceAvg : $rate, 2);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function buildTotals(Collection $rows): array
    {
        $pendingNotOnRoadQty = (int) $rows->sum('pending_qty');
        $onRoadQty = (int) $rows->sum('on_road_qty');
        $receivedQty = (int) $rows->sum('unloading_qty');
        $totalQty = (int) $rows->sum('total_qty');
        $pendingAmount = round((float) $rows->sum('pending_amount'), 2);
        $receivedAmount = round((float) $rows->sum('received_amount'), 2);
        $grandAmount = round($pendingAmount + $receivedAmount, 2);
        $pipelinePendingQty = (int) $rows->sum('pipeline_pending_qty');

        return [
            'ordered_qty'           => $totalQty,
            'on_road_qty'           => $onRoadQty,
            'unloading_qty'         => $receivedQty,
            'pending_not_on_road'   => $pendingNotOnRoadQty,
            'pending'               => [
                'qty'     => $pipelinePendingQty,
                'amount'  => $pendingAmount,
                'average' => $this->weightedAverage($pendingAmount, $pipelinePendingQty),
            ],
            'received'              => [
                'qty'     => $receivedQty,
                'amount'  => $receivedAmount,
                'average' => $this->weightedAverage($receivedAmount, $receivedQty),
            ],
            'grand'                 => [
                'qty'     => $totalQty,
                'amount'  => $grandAmount,
                'average' => $this->weightedAverage($grandAmount, $totalQty),
            ],
        ];
    }

    protected function weightedAverage(float $amount, int $qtyTons): float
    {
        if ($qtyTons <= 0) {
            return 0.0;
        }

        return round($amount / ($qtyTons * 1000), 3);
    }

    /**
     * @return Collection<int, RawMaterial>
     */
    protected function activeMaterials(): Collection
    {
        return RawMaterial::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
