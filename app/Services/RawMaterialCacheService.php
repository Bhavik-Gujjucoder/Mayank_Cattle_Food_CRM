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
        return round((float) $receive->freight * (int) $receive->qty, 3);
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

    public static function initializeOrderItem(RawMaterialOrderItem $item): void
    {
        $item->total_price    = round($item->total_qty * 1000 * (float) $item->price, 3);
        $item->other_expense  = round((float) ($item->other_expense ?? 0), 3);
        $item->pending_qty    = $item->total_qty;
        $item->received_qty   = 0;
        $item->extra_qty      = 0;
        $item->pending_price  = $item->total_price;
        $item->received_price = 0;
        $item->total_freight  = 0;
        $item->price_avg      = 0;
        $item->status         = 0;
    }

    /** Material value for qty received above ordered total_qty. */
    public static function itemExtraAmount(RawMaterialOrderItem $item): float
    {
        return round((int) $item->extra_qty * 1000 * (float) $item->price, 3);
    }

    /** Ordered + extra material value still not in received_price. */
    public static function itemPendingAmount(RawMaterialOrderItem $item): float
    {
        return max(
            0,
            round(
                (float) $item->total_price
                + self::itemExtraAmount($item)
                - (float) $item->received_price,
                3
            )
        );
    }

    /** Ordered + extra material value (matches order item Total Price display). */
    public static function itemTotalAmount(RawMaterialOrderItem $item): float
    {
        return round((float) $item->total_price + self::itemExtraAmount($item), 3);
    }

    /** Active pipeline qty (on-road + received; excludes cancelled). */
    public static function itemPipelineQty(RawMaterialOrderItem $item): int
    {
        return (int) RawMaterialReceive::where('raw_material_order_item_id', $item->id)
            ->whereIn('status', [0, 1])
            ->sum('qty');
    }

    /** Ordered tons still open for new receive entries (pipeline can exclude one entry when editing). */
    public static function itemOrderedRemaining(RawMaterialOrderItem $item, int $excludeReceiveQty = 0): int
    {
        $pipelineQty = max(0, self::itemPipelineQty($item) - max(0, $excludeReceiveQty));

        return max(0, (int) $item->total_qty - $pipelineQty);
    }

    public static function itemHasOrderedRemaining(RawMaterialOrderItem $item, int $excludeReceiveQty = 0): bool
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
                        ? (int) $editingReceive->qty
                        : 0;

                    return self::itemHasOrderedRemaining($item, $excludeQty);
                });
            })
            ->values();
    }

    /** Sync extra_qty and pending_price (pending includes extra amount not yet received). */
    public static function syncItemExtraQty(RawMaterialOrderItem $item): void
    {
        $extraQty     = max(0, self::itemPipelineQty($item) - (int) $item->total_qty);
        $extraAmount  = round($extraQty * 1000 * (float) $item->price, 3);
        $pendingPrice = max(0, round((float) $item->total_price + $extraAmount - (float) $item->received_price, 3));

        if ((int) $item->extra_qty === $extraQty
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
        $order->total_qty     = (int) $order->items->sum('total_qty');
        $order->total_price   = (float) $order->items->sum(
            fn (RawMaterialOrderItem $item) => (float) $item->total_price
                + (float) $item->other_expense
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

        if ((int) $item->received_qty === 0) {
            $item->status = 0;
        } elseif ((int) $item->received_qty >= (int) $item->total_qty) {
            $item->status = 2;
        } else {
            $item->status = 1;
        }

        $item->saveQuietly();
    }

    public static function recalculateItemPriceAvg(RawMaterialOrderItem $item): void
    {
        $item->price_avg = (int) $item->received_qty > 0
            ? round(((float) $item->received_price + (float) $item->total_freight) / ($item->received_qty * 1000), 3)
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

        $itemsQuery = RawMaterialOrderItem::where('raw_material_id', $rawMaterialId);
        $sumLanded      = (float) (clone $itemsQuery)->selectRaw('COALESCE(SUM(received_price + total_freight), 0) as total')->value('total');
        $sumReceivedQty = (int) (clone $itemsQuery)->sum('received_qty');
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

        $qty         = (int) $receive->qty;
        $priceAmount = $qty * 1000 * (float) $item->price;

        $item->received_qty   = (int) $item->received_qty + $qty;
        $item->pending_qty    = max(0, (int) $item->total_qty - (int) $item->received_qty);
        $item->received_price = (float) $item->received_price + $priceAmount;
        $item->total_freight  = (float) $item->total_freight + self::receiveFreightAmount($receive);
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);
        self::syncItemExtraQty($item);

        $material->total_stock     = (float) $material->total_stock + $qty;
        $material->available_stock = (float) $material->available_stock + $qty;
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

        $qty         = (int) $receive->qty;
        $priceAmount = $qty * 1000 * (float) $item->price;

        $item->received_qty   = max(0, (int) $item->received_qty - $qty);
        $item->pending_qty    = max(0, (int) $item->total_qty - (int) $item->received_qty);
        $item->received_price = max(0, (float) $item->received_price - $priceAmount);
        $item->total_freight  = max(0, (float) $item->total_freight - self::receiveFreightAmount($receive));
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);
        self::syncItemExtraQty($item);

        $material->total_stock     = max(0, (float) $material->total_stock - $qty);
        $material->available_stock = max(0, (float) $material->available_stock - $qty);
        $material->saveQuietly();

        self::recalculateOrder($item->order);
        self::recalculateMaterialPrices($material->id);
    }
}
