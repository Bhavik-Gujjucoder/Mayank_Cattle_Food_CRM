<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Support\LessFreight;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms an OrderItem model (with 'product' and 'dispatches' loaded)
 * into the mobile API order-item object.
 *
 * Calculates dispatch progress (dispatched qty, pending qty, dispatch status)
 * from the already-loaded dispatches collection — no additional DB queries.
 *
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ── Dispatch progress ─────────────────────────────────────────────────
        // Sum bags across all non-deleted dispatches (soft-deleted already
        // excluded by Eloquent's global scope when the relation was loaded).
        $dispatchedQty = (int) $this->dispatches->sum('no_of_bags');
        $pendingQty    = max(0, (int) $this->qty - $dispatchedQty);

        // Derive a simple dispatch state so the mobile app can gate-keep UI.
        $dispatchStatus = match (true) {
            $dispatchedQty === 0              => 'not_dispatched',
            $pendingQty    === 0              => 'fully_dispatched',
            default                           => 'partial',
        };

        return [
            'id'      => $this->id,

            // ── Product snapshot ──────────────────────────────────────────────
            // Returns null when the product has been soft-deleted.
            'product' => $this->product ? [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'unit' => $this->product->unit ?? null, // Bag | Ton | KG
            ] : null,

            // ── Quantities ────────────────────────────────────────────────────
            'qty'            => (int) $this->qty,      // Total ordered
            'unit_price'     => (string) $this->unit_price,
            'total_price'    => (string) $this->total_price,

            // ── Dispatch summary ──────────────────────────────────────────────
            'dispatched_qty' => $dispatchedQty,
            'pending_qty'    => $pendingQty,
            'dispatch_status'=> $dispatchStatus,

            // ── Individual dispatch records ───────────────────────────────────
            // Each entry represents one shipment against this line item.
            'dispatches' => $this->dispatches
                ->sortByDesc('dispatch_date')
                ->values()
                ->map(fn (DispatchManagement $d) => [
                    'id'             => $d->id,
                    'no_of_bags'     => (int) $d->no_of_bags,
                    'less_freight_per_qty' => $d->less_freight_per_qty !== null
                        ? (string) $d->less_freight_per_qty
                        : null,
                    'amount'         => number_format(LessFreight::gross((int) $d->no_of_bags, (float) $this->unit_price), 2, '.', ''),
                    'less_freight_total' => number_format(LessFreight::deduction((int) $d->no_of_bags, $d->less_freight_per_qty), 2, '.', ''),
                    'final_amount'   => number_format(LessFreight::finalAmount((int) $d->no_of_bags, (float) $this->unit_price, $d->less_freight_per_qty), 2, '.', ''),
                    'dispatch_date'  => $d->dispatch_date?->format('Y-m-d'),
                    'payment_status' => match ((int) $d->status) {
                        DispatchManagement::STATUS_PAID    => 'paid',
                        DispatchManagement::STATUS_PARTIAL => 'partial',
                        default                            => 'unpaid',
                    },
                ])
                ->all(),
        ];
    }
}
