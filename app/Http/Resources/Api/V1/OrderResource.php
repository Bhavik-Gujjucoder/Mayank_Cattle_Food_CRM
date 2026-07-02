<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderManagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms an OrderManagement model (with broker, brand, dealer.user,
 * items.product, and items.dispatches loaded) into the mobile API order object.
 *
 * Dispatch summary aggregates are computed from already-loaded collections
 * so no additional queries are issued per order (avoids N+1).
 *
 * @mixin OrderManagement
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ── Aggregate dispatch progress ────────────────────────────────────────
        // These mirror the logic in OrderManagement::totalOrderedQty(),
        // totalDispatchedQty(), and dispatchPercent() but operate on the
        // already-loaded 'items' + 'items.dispatches' collections.
        $orderedQty    = (int) $this->items->sum('qty');
        $dispatchedQty = (int) $this->items->sum(
            fn ($item) => (int) $item->dispatches->sum('no_of_bags')
        );
        $pendingQty    = max(0, $orderedQty - $dispatchedQty);

        $dispatchPercent = $orderedQty > 0
            ? (int) round(($dispatchedQty / $orderedQty) * 100)
            : 0;

        // Count line items that still have outstanding bags.
        $pendingLineItems = $this->items->filter(
            fn ($item) => max(0, (int) $item->qty - (int) $item->dispatches->sum('no_of_bags')) > 0
        )->count();

        return [
            // ── Order identity ─────────────────────────────────────────────────
            'id'           => $this->id,
            'order_number' => $this->unique_order_id,
            'order_date'   => $this->order_date?->format('Y-m-d'),

            // ── Parties ────────────────────────────────────────────────────────
            // broker: the User with broker role who placed / manages this order.
            'broker' => $this->broker ? [
                'id'   => $this->broker->id,
                'name' => $this->broker->name,
            ] : null,

            // brand: the brand/product line the order is placed under.
            'brand' => $this->brand ? [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ] : null,

            // dealer: the dealer_management profile + their linked user account.
            'dealer' => $this->dealer ? [
                'id'             => $this->dealer->id,
                'firm_shop_name' => $this->dealer->firm_shop_name,
                'user_name'      => $this->dealer->user?->name,
            ] : null,

            // ── Delivery ───────────────────────────────────────────────────────
            'delivery_address' => $this->delivery_address,

            // ── Payment ────────────────────────────────────────────────────────
            'payment_status'      => $this->payment_status,  // unpaid | paid | partial
            'partial_paid_amount' => $this->partial_paid_amount !== null
                ? (string) $this->partial_paid_amount
                : null,
            'total_order_amount'  => (string) $this->total_order_amount,
            'grand_total'         => (string) $this->grand_total,

            // ── Order status ───────────────────────────────────────────────────
            'status' => (int) $this->status,  // 1 = active, 0 = inactive

            // ── Dispatch summary (aggregated across all line items) ────────────
            'dispatch_summary' => [
                'ordered_qty'          => $orderedQty,
                'dispatched_qty'       => $dispatchedQty,
                'pending_qty'          => $pendingQty,
                'dispatch_percent'     => $dispatchPercent,
                'pending_line_items'   => $pendingLineItems,
                'is_fully_dispatched'  => $orderedQty > 0 && $dispatchedQty >= $orderedQty,
            ],

            // ── Line items ────────────────────────────────────────────────────
            // Each item includes its product details + per-item dispatch history.
            'items' => OrderItemResource::collection($this->items),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
