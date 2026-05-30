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

    public static function initializeOrderItem(RawMaterialOrderItem $item): void
    {
        $item->total_price    = round($item->total_qty * 1000 * (float) $item->price, 3);
        $item->pending_qty    = $item->total_qty;
        $item->received_qty   = 0;
        $item->pending_price  = $item->total_price;
        $item->received_price = 0;
        $item->total_freight  = 0;
        $item->price_avg      = 0;
        $item->status         = 0;
    }

    public static function recalculateOrder(RawMaterialOrder $order): void
    {
        if ((int) $order->status === 3) {
            return;
        }

        $order->load('items');
        $order->total_qty     = (int) $order->items->sum('total_qty');
        $order->total_price   = (float) $order->items->sum('total_price');
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

        $sumPrice = (float) RawMaterialOrderItem::where('raw_material_id', $rawMaterialId)->sum('total_price');
        $sumQty   = (int) RawMaterialOrderItem::where('raw_material_id', $rawMaterialId)->sum('total_qty');
        $material->average_price = $sumQty > 0 ? round($sumPrice / ($sumQty * 1000), 3) : 0;
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

        $item->pending_qty    = max(0, (int) $item->pending_qty - $qty);
        $item->received_qty   = (int) $item->received_qty + $qty;
        $item->pending_price  = max(0, (float) $item->pending_price - $priceAmount);
        $item->received_price = (float) $item->received_price + $priceAmount;
        $item->total_freight  = (float) $item->total_freight + self::receiveFreightAmount($receive);
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);

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

        $item->pending_qty    = (int) $item->pending_qty + $qty;
        $item->received_qty   = max(0, (int) $item->received_qty - $qty);
        $item->pending_price  = (float) $item->pending_price + $priceAmount;
        $item->received_price = max(0, (float) $item->received_price - $priceAmount);
        $item->total_freight  = max(0, (float) $item->total_freight - self::receiveFreightAmount($receive));
        $item->saveQuietly();

        self::recalculateItemPriceAvg($item);
        self::syncItemStatus($item);

        $material->total_stock     = max(0, (float) $material->total_stock - $qty);
        $material->available_stock = max(0, (float) $material->available_stock - $qty);
        $material->saveQuietly();

        self::recalculateOrder($item->order);
        self::recalculateMaterialPrices($material->id);
    }
}
