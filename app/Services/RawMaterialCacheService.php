<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;

class RawMaterialCacheService
{
    /** Freight contributed to order item: receive.freight (per ton) × receive.qty (tons). */
    public static function receiveFreightAmount(RawMaterialReceive $receive): float
    {
        return round((float) $receive->freight * rmQty($receive->qty), 3);
    }

    public static function receiveFreightRateLabel(RawMaterialReceive $receive): string
    {
        return '₹ ' . number_format((float) $receive->freight, 2) . '/ton';
    }

    public static function receiveFreightLineLabel(RawMaterialReceive $receive): string
    {
        return 'Line: ₹ ' . number_format(self::receiveFreightAmount($receive), 2);
    }

    /** Two-line freight for list tables (rate/ton + line total). */
    public static function receiveFreightHtml(RawMaterialReceive $receive): string
    {
        return self::receiveFreightRateLabel($receive)
            . '<br><small class="text-muted">' . self::receiveFreightLineLabel($receive) . '</small>';
    }

    /** Two-line freight for PDF exports. */
    public static function receiveFreightPdfHtml(RawMaterialReceive $receive): string
    {
        return self::receiveFreightRateLabel($receive)
            . '<br><span class="freight-sub">' . self::receiveFreightLineLabel($receive) . '</span>';
    }

    /** Two-line freight for Excel exports (newline-separated). */
    public static function receiveFreightPlain(RawMaterialReceive $receive): string
    {
        return self::receiveFreightRateLabel($receive) . "\n" . self::receiveFreightLineLabel($receive);
    }

    /** Material value: qty (tons) × 1000 × price/kg. */
    public static function qtyMaterialAmount(float $qty, float $price): float
    {
        return round(rmQty($qty) * 1000 * $price, 3);
    }

    /** Tax amount on material value. */
    public static function qtyTaxAmount(float $qty, float $price, float $taxPercent): float
    {
        return round(self::qtyMaterialAmount($qty, $price) * ($taxPercent / 100), 3);
    }

    /** Material value plus tax for a qty. */
    public static function qtyAmountWithTax(float $qty, float $price, float $taxPercent): float
    {
        return round(
            self::qtyMaterialAmount($qty, $price) + self::qtyTaxAmount($qty, $price, $taxPercent),
            3
        );
    }

    /**
     * Ordered line total: (material + tax + other expense) − TDS, floored at 0.
     * Extra qty is not included (see itemExtraAmount / itemTotalAmount).
     */
    public static function itemLineTotal(RawMaterialOrderItem $item): float
    {
        $withTax = self::qtyAmountWithTax(
            rmQty($item->total_qty),
            (float) $item->price,
            (float) ($item->tax_percent ?? 0)
        );
        $other = round((float) ($item->other_expense ?? 0), 3);
        $tds   = round((float) ($item->tds_amount ?? 0), 3);

        return max(0, round($withTax + $other - $tds, 3));
    }

    public static function initializeOrderItem(RawMaterialOrderItem $item): void
    {
        $item->tax_percent    = round((float) ($item->tax_percent ?? 0), 3);
        $item->other_expense  = round((float) ($item->other_expense ?? 0), 3);
        $item->tds_amount     = round((float) ($item->tds_amount ?? 0), 3);
        $item->total_price    = self::itemLineTotal($item);
        $item->pending_qty    = rmQty($item->total_qty);
        $item->received_qty   = 0;
        $item->extra_qty      = 0;
        $item->pending_price  = $item->total_price;
        $item->received_price = 0;
        $item->total_freight  = 0;
        $item->price_avg      = 0;
        $item->status         = 0;
    }

    /** Tax rupees on ordered qty plus extra qty. */
    public static function itemTaxAmount(RawMaterialOrderItem $item): float
    {
        return round(
            self::qtyTaxAmount(rmQty($item->total_qty), (float) $item->price, (float) ($item->tax_percent ?? 0))
            + self::qtyTaxAmount(rmQty($item->extra_qty), (float) $item->price, (float) ($item->tax_percent ?? 0)),
            3
        );
    }

    /** Extra qty value including tax (other expense and TDS stay on the ordered line). */
    public static function itemExtraAmount(RawMaterialOrderItem $item): float
    {
        return self::qtyAmountWithTax(
            rmQty($item->extra_qty),
            (float) $item->price,
            (float) ($item->tax_percent ?? 0)
        );
    }

    /**
     * Other expense − TDS allocated across ordered tons.
     * Matches line total when TDS does not floor the line at zero.
     */
    public static function itemAllocatableOtherAmount(RawMaterialOrderItem $item): float
    {
        $orderedMaterialTax = self::qtyAmountWithTax(
            rmQty($item->total_qty),
            (float) $item->price,
            (float) ($item->tax_percent ?? 0)
        );

        return round(self::itemLineTotal($item) - $orderedMaterialTax, 3);
    }

    /**
     * Payable added for a receive: material+tax for all qty, plus a share of
     * (other expense − TDS) for tons that count toward ordered total_qty.
     */
    public static function receivePayableAmount(RawMaterialOrderItem $item, float $qty, float $receivedQtyBefore): float
    {
        $qty               = max(0, rmQty($qty));
        $receivedQtyBefore = max(0, rmQty($receivedQtyBefore));
        $materialTax       = self::qtyAmountWithTax(
            $qty,
            (float) $item->price,
            (float) ($item->tax_percent ?? 0)
        );
        $totalQty = rmQty($item->total_qty);
        if ($qty <= 0 || $totalQty <= 0) {
            return $materialTax;
        }

        $alreadyOrdered = rmQty(min($receivedQtyBefore, $totalQty));
        $orderedPart    = rmQty(min($qty, max(0, $totalQty - $alreadyOrdered)));
        if ($orderedPart <= 0) {
            return $materialTax;
        }

        $allocatable  = self::itemAllocatableOtherAmount($item);
        $newOrdered   = rmQty(min($alreadyOrdered + $orderedPart, $totalQty));
        $beforeShare  = $alreadyOrdered <= 0 ? 0.0 : round($allocatable * $alreadyOrdered / $totalQty, 3);
        $afterShare   = $newOrdered >= $totalQty
            ? round($allocatable, 3)
            : round($allocatable * $newOrdered / $totalQty, 3);

        return round($materialTax + ($afterShare - $beforeShare), 3);
    }

    /** Remaining payable: line total + extra with tax − received_price. */
    public static function itemPendingAmount(RawMaterialOrderItem $item): float
    {
        return max(0, round(self::itemTotalAmount($item) - (float) $item->received_price, 3));
    }

    /** Material+tax of received qty (excludes other expense and TDS; used for avg price/kg). */
    public static function itemReceivedMaterialTaxAmount(RawMaterialOrderItem $item): float
    {
        return self::qtyAmountWithTax(
            rmQty($item->received_qty),
            (float) $item->price,
            (float) ($item->tax_percent ?? 0)
        );
    }

    /** Ordered line total plus extra qty with tax (matches order item Total Price display). */
    public static function itemTotalAmount(RawMaterialOrderItem $item): float
    {
        return round((float) $item->total_price + self::itemExtraAmount($item), 3);
    }

    /** Active pipeline qty (on-road + received; excludes cancelled). */
    public static function itemPipelineQty(RawMaterialOrderItem $item): float
    {
        return rmQty(RawMaterialReceive::where('raw_material_order_item_id', $item->id)
            ->whereIn('status', [0, 1])
            ->sum('qty'));
    }

    /** Ordered tons still open for new receive entries (pipeline can exclude one entry when editing). */
    public static function itemOrderedRemaining(RawMaterialOrderItem $item, float $excludeReceiveQty = 0): float
    {
        $pipelineQty = max(0, rmQty(self::itemPipelineQty($item) - max(0, rmQty($excludeReceiveQty))));

        return max(0, rmQty(rmQty($item->total_qty) - $pipelineQty));
    }

    public static function itemHasOrderedRemaining(RawMaterialOrderItem $item, float $excludeReceiveQty = 0): bool
    {
        return self::itemOrderedRemaining($item, $excludeReceiveQty) > 0;
    }

    /** Orders that still have open ordered qty for receive add/edit. */
    public static function receivableOrders(?RawMaterialReceive $editingReceive = null)
    {
        return RawMaterialOrder::query()
            ->with(['supplier', 'supplierBroker', 'items'])
            ->whereIn('status', [0, 1, 2])
            ->orderByDesc('id')
            ->get()
            ->filter(function (RawMaterialOrder $order) use ($editingReceive) {
                if ($editingReceive && (int) $editingReceive->raw_material_order_id === (int) $order->id) {
                    return true;
                }

                return $order->items->contains(function (RawMaterialOrderItem $item) use ($editingReceive) {
                    if ((int) $item->status === 3) {
                        return false;
                    }

                    $excludeQty = ($editingReceive && (int) $editingReceive->raw_material_order_item_id === (int) $item->id)
                        ? rmQty($editingReceive->qty)
                        : 0;

                    return self::itemHasOrderedRemaining($item, $excludeQty);
                });
            })
            ->values();
    }

    /** Sync extra_qty and pending_price (pending is remaining payable including extra). */
    public static function syncItemExtraQty(RawMaterialOrderItem $item): void
    {
        $extraQty     = max(0, rmQty(self::itemPipelineQty($item) - rmQty($item->total_qty)));
        $pendingPrice = max(
            0,
            round(
                (float) $item->total_price
                + self::qtyAmountWithTax(
                    $extraQty,
                    (float) $item->price,
                    (float) ($item->tax_percent ?? 0)
                )
                - (float) $item->received_price,
                3
            )
        );

        if (rmQty($item->extra_qty) === $extraQty
            && round((float) $item->pending_price, 3) === $pendingPrice) {
            return;
        }

        $item->extra_qty     = $extraQty;
        $item->pending_price = $pendingPrice;
        $item->saveQuietly();
    }

    public static function refreshItemExtraAndOrder(int $orderItemId): void
    {
        $item = RawMaterialOrderItem::with('order')->find($orderItemId);
        if (! $item) {
            return;
        }

        self::syncItemExtraQty($item);
        $item->refresh();

        if ($item->order) {
            self::recalculateOrder($item->order);
        }
    }

    public static function recalculateOrder(RawMaterialOrder $order): void
    {
        if ((int) $order->status === 3) {
            return;
        }

        $order->load('items');
        $order->total_qty     = rmQty($order->items->sum('total_qty'));
        $order->total_price   = (float) $order->items->sum(
            fn (RawMaterialOrderItem $item) => (float) $item->total_price
                + self::itemExtraAmount($item)
        );
        $order->total_freight = (float) $order->items->sum('total_freight');

        $statuses = $order->items->pluck('status')->unique();
        if ($statuses->isEmpty()) {
            $order->status = 0;
        } elseif ($statuses->every(fn ($s) => (int) $s === 3)) {
            $order->status = 3;
        } elseif ($statuses->every(fn ($s) => (int) $s === 0)) {
            $order->status = 0;
        } elseif ($statuses->every(fn ($s) => (int) $s === 2)) {
            $order->status = 2;
        } else {
            $order->status = 1;
        }

        $order->saveQuietly();
    }

    public static function syncItemStatus(RawMaterialOrderItem $item): void
    {
        if ((int) $item->status === 3) {
            return;
        }

        $receivedQty = rmQty($item->received_qty);
        $totalQty    = rmQty($item->total_qty);

        if ($receivedQty <= 0) {
            $item->status = 0;
        } elseif ($receivedQty >= $totalQty) {
            $item->status = 2;
        } else {
            $item->status = 1;
        }

        $item->saveQuietly();
    }

    public static function recalculateItemPriceAvg(RawMaterialOrderItem $item): void
    {
        $item->price_avg = rmQty($item->received_qty) > 0
            ? round(
                (self::itemReceivedMaterialTaxAmount($item) + (float) $item->total_freight)
                / ($item->received_qty * 1000),
                3
            )
            : 0;
        $item->saveQuietly();
    }

    public static function recalculateMaterialPrices(int $rawMaterialId): void
    {
        $material = RawMaterial::find($rawMaterialId);
        if (! $material) {
            return;
        }

        $lastItem = RawMaterialOrderItem::where('raw_material_id', $rawMaterialId)->orderByDesc('id')->first();
        $material->last_purchase_price = $lastItem ? (float) $lastItem->price : 0;

        $items = RawMaterialOrderItem::where('raw_material_id', $rawMaterialId)->get();
        $sumLanded      = (float) $items->sum(
            fn (RawMaterialOrderItem $item) => self::itemReceivedMaterialTaxAmount($item) + (float) $item->total_freight
        );
        $sumReceivedQty = rmQty($items->sum('received_qty'));
        $material->average_price = $sumReceivedQty > 0
            ? round($sumLanded / ($sumReceivedQty * 1000), 3)
            : 0;
        $material->saveQuietly();
    }

    public static function applyReceive(RawMaterialReceive $receive): void
    {
        $item     = RawMaterialOrderItem::with('order')->find($receive->raw_material_order_item_id);
        $material = RawMaterial::find($receive->raw_material_id);
        if (! $item || ! $material) {
            return;
        }

        $qty         = rmQty($receive->qty);
        $priceAmount = self::receivePayableAmount($item, $qty, rmQty($item->received_qty));

        $item->received_qty   = rmQty(rmQty($item->received_qty) + $qty);
        $item->pending_qty    = max(0, rmQty(rmQty($item->total_qty) - rmQty($item->received_qty)));
        $item->received_price = (float) $item->received_price + $priceAmount;
        $item->total_freight  = (float) $item->total_freight + self::receiveFreightAmount($receive);
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);
        self::syncItemExtraQty($item);

        $material->total_stock     = rmQty((float) $material->total_stock + $qty);
        $material->available_stock = rmQty((float) $material->available_stock + $qty);
        $material->saveQuietly();

        self::recalculateOrder($item->order);
        self::recalculateMaterialPrices($material->id);
    }

    /** Rebuild item total_freight from all received (status=1) entries using freight × qty. */
    public static function recalculateItemFreightFromReceives(RawMaterialOrderItem $item): void
    {
        $item->total_freight = (float) RawMaterialReceive::where('raw_material_order_item_id', $item->id)
            ->where('status', 1)
            ->get()
            ->sum(fn (RawMaterialReceive $r) => self::receiveFreightAmount($r));
        $item->saveQuietly();
        self::recalculateItemPriceAvg($item);
        $item->load('order');
        if ($item->order) {
            self::recalculateOrder($item->order);
        }
        self::recalculateMaterialPrices((int) $item->raw_material_id);
    }

    public static function reverseReceive(RawMaterialReceive $receive): void
    {
        $item     = RawMaterialOrderItem::with('order')->find($receive->raw_material_order_item_id);
        $material = RawMaterial::find($receive->raw_material_id);
        if (! $item || ! $material) {
            return;
        }

        $qty               = rmQty($receive->qty);
        $receivedQtyBefore = max(0, rmQty(rmQty($item->received_qty) - $qty));
        $priceAmount       = self::receivePayableAmount($item, $qty, $receivedQtyBefore);

        $item->received_qty   = max(0, rmQty(rmQty($item->received_qty) - $qty));
        $item->pending_qty    = max(0, rmQty(rmQty($item->total_qty) - rmQty($item->received_qty)));
        $item->received_price = max(0, (float) $item->received_price - $priceAmount);
        $item->total_freight  = max(0, (float) $item->total_freight - self::receiveFreightAmount($receive));
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);
        self::syncItemExtraQty($item);

        $material->total_stock     = max(0, rmQty((float) $material->total_stock - $qty));
        $material->available_stock = max(0, rmQty((float) $material->available_stock - $qty));
        $material->saveQuietly();

        self::recalculateOrder($item->order);
        self::recalculateMaterialPrices($material->id);
    }

    /** Rebuild received_price and pending_price from approved receives. */
    public static function rebuildItemPayables(RawMaterialOrderItem $item): void
    {
        $runningQty    = 0;
        $receivedPrice = 0.0;

        RawMaterialReceive::query()
            ->where('raw_material_order_item_id', $item->id)
            ->where('status', 1)
            ->orderBy('id')
            ->get()
            ->each(function (RawMaterialReceive $receive) use ($item, &$runningQty, &$receivedPrice) {
                $qty = rmQty($receive->qty);
                $receivedPrice += self::receivePayableAmount($item, $qty, $runningQty);
                $runningQty += $qty;
            });

        $item->received_price = round($receivedPrice, 3);
        $item->saveQuietly();
        self::recalculateItemPriceAvg($item);
        self::syncItemExtraQty($item);
    }
}
