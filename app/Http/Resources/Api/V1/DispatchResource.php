<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DispatchManagement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a DispatchManagement model into the mobile API dispatch object.
 *
 * Expected eager loads (loaded by DispatchController::index()):
 *   order:id,unique_order_id,order_date,brand_id,dealer_id
 *   order.brand:id,name
 *   order.dealer:id,user_id,firm_shop_name
 *   order.dealer.user:id,name
 *   product:id,name,unit
 *   transporter:id,name,phone_no
 *
 * @mixin DispatchManagement
 */
class DispatchResource extends JsonResource
{
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

        return [
            // ── Dispatch identity ──────────────────────────────────────────────
            'id'            => $this->id,
            'dispatch_date' => $this->dispatch_date?->format('Y-m-d'),
            'no_of_bags'    => (int) $this->no_of_bags,

            // ── Payment ────────────────────────────────────────────────────────
            'payment_status'       => $statusLabel,
            'partial_paid_amount'  => $this->partial_paid_amount !== null
                ? (string) $this->partial_paid_amount
                : null,
            'accrued_late_fee'     => (string) ($this->accrued_late_fee ?? '0.00'),
            'late_fee_last_accrued_on' => $this->late_fee_last_accrued_on?->format('Y-m-d'),

            // ── Transport ─────────────────────────────────────────────────────
            'truck_number'   => $this->truck_number,
            'driver_contact' => $this->driver_contact,

            // ── Parent order ──────────────────────────────────────────────────
            'order' => $this->order ? [
                'id'           => $this->order->id,
                'order_number' => $this->order->unique_order_id,
                'order_date'   => $this->order->order_date?->format('Y-m-d'),
                'brand'        => $this->order->brand ? [
                    'id'   => $this->order->brand->id,
                    'name' => $this->order->brand->name,
                ] : null,
                'dealer'       => $this->order->dealer ? [
                    'id'             => $this->order->dealer->id,
                    'firm_shop_name' => $this->order->dealer->firm_shop_name,
                    'user_name'      => $this->order->dealer->user?->name,
                ] : null,
            ] : null,

            // ── Product ────────────────────────────────────────────────────────
            'product' => $this->product ? [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'unit' => $this->product->unit,
            ] : null,

            // ── Transporter ────────────────────────────────────────────────────
            'transporter' => $this->transporter ? [
                'id'       => $this->transporter->id,
                'name'     => $this->transporter->name,
                'phone_no' => $this->transporter->phone_no,
            ] : null,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
