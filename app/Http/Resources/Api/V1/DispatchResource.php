<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DispatchManagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a DispatchManagement model into the mobile API dispatch object.
 *
 * Expected eager loads (set by DispatchController::index()):
 *   order:id,unique_order_id,order_date,delivery_address,brand_id,dealer_id,broker_id
 *   order.brand:id,name
 *   order.dealer:id,user_id,firm_shop_name
 *   order.dealer.user:id,name
 *   order.broker:id,name
 *   product:id,name,unit
 *   transporter:id,name,phone_no
 *   orderItem (with withSum('dispatches','no_of_bags'))
 *
 * Quantity fields explanation:
 *   no_of_bags            — bags dispatched in THIS specific dispatch event
 *   ordered_qty           — total bags ordered for the parent order item
 *   total_dispatched_qty  — sum of no_of_bags across ALL dispatch events for the item
 *   pending_qty           — bags still to be dispatched for the item
 *   is_item_complete      — true when total_dispatched_qty >= ordered_qty
 *
 * Payment receivable fields (computed inline, no extra queries):
 *   base_amount      — unit_price × no_of_bags (value at time of dispatch)
 *   total_receivable — base_amount + accrued_late_fee
 *   amount_paid      — 0 (unpaid) | partial_paid_amount (partial) | total_receivable (paid)
 *   balance_due      — total_receivable − amount_paid (0 when fully paid)
 *
 * @mixin DispatchManagement
 */
class DispatchResource extends JsonResource
{
    /**
     * Map the integer status stored in the DB to a human-readable string.
     */
    private const STATUS_MAP = [
        DispatchManagement::STATUS_UNPAID  => 'unpaid',
        DispatchManagement::STATUS_PAID    => 'paid',
        DispatchManagement::STATUS_PARTIAL => 'partial',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statusLabel = self::STATUS_MAP[(int) $this->status] ?? 'unpaid';

        // ── Order-item fulfilment context ──────────────────────────────────────
        // dispatches_sum_no_of_bags is added by withSum() in the controller query.
        // It represents the TOTAL bags dispatched for this order item across all
        // dispatch events — not just the bags in this specific dispatch record.
        $orderedQty             = (int) ($this->orderItem?->qty ?? 0);
        $totalDispatchedForItem = (int) ($this->orderItem?->dispatches_sum_no_of_bags ?? 0);
        $pendingQty             = max(0, $orderedQty - $totalDispatchedForItem);

        // ── Payment receivable (computed inline — no extra queries) ────────────
        // All inputs are already loaded: orderItem.unit_price, no_of_bags,
        // accrued_late_fee, status, partial_paid_amount.
        $unitPrice       = (float) ($this->orderItem?->unit_price ?? 0);
        $baseAmount      = round($unitPrice * (int) $this->no_of_bags, 2);
        $lateFee         = round((float) ($this->accrued_late_fee ?? 0), 2);
        $totalReceivable = round($baseAmount + $lateFee, 2);
        $amountPaid      = match ((int) $this->status) {
            DispatchManagement::STATUS_PAID    => $totalReceivable,
            DispatchManagement::STATUS_PARTIAL => (float) ($this->partial_paid_amount ?? 0),
            default                            => 0.0,
        };
        $balanceDue = (int) $this->status === DispatchManagement::STATUS_PAID
            ? 0.0
            : max(0.0, round($totalReceivable - $amountPaid, 2));

        return [
            // ── Dispatch identity ──────────────────────────────────────────────
            'id'              => $this->id,
            // Human-readable dispatch reference (padded id — no separate column exists).
            'dispatch_number' => 'DISP-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT),
            'dispatch_date'   => $this->dispatch_date?->format('Y-m-d'),

            // ── Quantity (this dispatch event) ─────────────────────────────────
            'no_of_bags' => (int) $this->no_of_bags,

            // ── Quantity (order-item totals) ───────────────────────────────────
            // These allow the mobile app to show fulfilment progress for the
            // parent order item without a separate API call.
            'ordered_qty'          => $orderedQty,
            'total_dispatched_qty' => $totalDispatchedForItem,
            'pending_qty'          => $pendingQty,
            'is_item_complete'     => $orderedQty > 0 && $totalDispatchedForItem >= $orderedQty,

            // ── Payment status ─────────────────────────────────────────────────
            'payment_status'           => $statusLabel,
            'partial_paid_amount'      => $this->partial_paid_amount !== null
                ? (string) $this->partial_paid_amount
                : null,
            'accrued_late_fee'         => (string) ($this->accrued_late_fee ?? '0.00'),
            'late_fee_last_accrued_on' => $this->late_fee_last_accrued_on?->format('Y-m-d'),

            // ── Payment receivable summary ─────────────────────────────────────
            // Gives the mobile app a complete financial picture per dispatch
            // without requiring a separate /payment endpoint call.
            'payment_receivable' => [
                'base_amount'      => number_format($baseAmount, 2, '.', ''),
                'total_receivable' => number_format($totalReceivable, 2, '.', ''),
                'amount_paid'      => number_format($amountPaid, 2, '.', ''),
                'balance_due'      => number_format($balanceDue, 2, '.', ''),
            ],

            // ── Transport ─────────────────────────────────────────────────────
            'truck_number'   => $this->truck_number,
            'driver_contact' => $this->driver_contact,

            // ── Parent order ──────────────────────────────────────────────────
            'order' => $this->order ? [
                'id'               => $this->order->id,
                'order_number'     => $this->order->unique_order_id,
                'order_date'       => $this->order->order_date?->format('Y-m-d'),
                'delivery_address' => $this->order->delivery_address,

                // Broker who placed / manages this order.
                'broker' => $this->order->broker ? [
                    'id'   => $this->order->broker->id,
                    'name' => $this->order->broker->name,
                ] : null,

                'brand' => $this->order->brand ? [
                    'id'   => $this->order->brand->id,
                    'name' => $this->order->brand->name,
                ] : null,

                'dealer' => $this->order->dealer ? [
                    'id'             => $this->order->dealer->id,
                    'firm_shop_name' => $this->order->dealer->firm_shop_name,
                    'user_name'      => $this->order->dealer->user?->name,
                ] : null,
            ] : null,

            // ── Product ────────────────────────────────────────────────────────
            'product' => $this->product ? [
                'id'         => $this->product->id,
                'name'       => $this->product->name,
                'unit'       => $this->product->unit,
                // Unit price from the order item (cost per bag at time of order).
                'unit_price' => $this->orderItem
                    ? (string) $this->orderItem->unit_price
                    : null,
            ] : null,

            // ── Transporter ───────────────────────────────────────────────────
            'transporter' => $this->transporter ? [
                'id'       => $this->transporter->id,
                'name'     => $this->transporter->name,
                'phone_no' => $this->transporter->phone_no,
            ] : null,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
