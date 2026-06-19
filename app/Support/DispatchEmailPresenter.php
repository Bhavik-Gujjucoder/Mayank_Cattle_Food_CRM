<?php

namespace App\Support;

use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Services\PaymentReceivableService;

class DispatchEmailPresenter
{
    public function __construct(
        protected PaymentReceivableService $receivableService
    ) {}

    /**
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    public function forDispatch(DispatchManagement $dispatch, array $extras = []): array
    {
        $dispatch->loadMissing([
            'order:id,unique_order_id,brand_id,dealer_id,order_date,payment_status,partial_paid_amount,grand_total',
            'order.brand:id,name',
            'order.dealer:id,user_id,firm_shop_name',
            'order.dealer.user:id,name,email',
            'product:id,name,unit',
            'orderItem:id,order_id,product_id,qty,unit_price,total_price',
            'orderItem.product:id,name,unit',
            'orderItem.dispatches:id,order_item_id,no_of_bags',
            'transporter:id,name,phone_no',
        ]);

        $order = $dispatch->order;
        $orderItem = $dispatch->orderItem;
        $receivable = $this->receivableService->summarizeDispatch($dispatch);

        $dispatchedQty = $orderItem
            ? (int) $orderItem->dispatches->sum('no_of_bags')
            : 0;
        $orderedQty = $orderItem ? (int) $orderItem->qty : 0;
        $pendingQty = max(0, $orderedQty - $dispatchedQty);

        $productUnit = $dispatch->product?->unit
            ?? $orderItem?->product?->unit
            ?? '';

        return array_merge([
            'dealer_name' => $this->dealerDisplayName($order),
            'order' => [
                'unique_order_id'     => $order?->unique_order_id ?? '—',
                'order_date'          => $order?->order_date?->format('d M Y') ?? '—',
                'brand_name'          => $order?->brand?->name ?? '—',
                'payment_status'      => $this->orderPaymentLabel($order?->payment_status),
                'grand_total'         => PaymentReceivableService::formatMoney((float) ($order?->grand_total ?? 0)),
            ],
            'line_item' => [
                'product_name'  => $orderItem?->product?->name ?? $dispatch->product?->name ?? '—',
                'qty'           => ProductUnit::formatWithUnit($orderedQty, $productUnit),
                'unit_price'    => PaymentReceivableService::formatMoney((float) ($orderItem?->unit_price ?? 0)),
                'line_total'    => PaymentReceivableService::formatMoney((float) ($orderItem?->total_price ?? 0)),
                'dispatched_qty'=> ProductUnit::formatWithUnit($dispatchedQty, $productUnit),
                'pending_qty'   => ProductUnit::formatWithUnit($pendingQty, $productUnit),
            ],
            'dispatch' => [
                'dispatch_date'       => $dispatch->dispatch_date?->format('d M Y') ?? '—',
                'qty'                 => ProductUnit::formatWithUnit((int) $dispatch->no_of_bags, $productUnit),
                'transporter_name'    => $dispatch->transporter?->name ?? '—',
                'truck_number'        => $dispatch->truck_number ?? '—',
                'driver_contact'      => $dispatch->driver_contact ?? '—',
                'payment_status'      => $this->dispatchPaymentLabel((int) $dispatch->status),
                'partial_paid_amount' => (int) $dispatch->status === DispatchManagement::STATUS_PARTIAL
                    ? PaymentReceivableService::formatMoney((float) ($dispatch->partial_paid_amount ?? 0))
                    : null,
            ],
            'receivable' => [
                'base_amount'      => PaymentReceivableService::formatMoney($receivable['base_amount']),
                'accrued_late_fee' => PaymentReceivableService::formatMoney($receivable['accrued_late_fee']),
                'total_receivable' => PaymentReceivableService::formatMoney($receivable['total_receivable']),
                'balance_due'      => (int) $dispatch->status === DispatchManagement::STATUS_PAID
                    ? '—'
                    : PaymentReceivableService::formatMoney($receivable['balance_due']),
            ],
        ], $extras);
    }

    private function dealerDisplayName(?OrderManagement $order): string
    {
        if ($order === null) {
            return 'Dealer';
        }

        return $order->dealer?->user?->name
            ?? $order->dealer?->firm_shop_name
            ?? 'Dealer';
    }

    private function dispatchPaymentLabel(int $status): string
    {
        return match ($status) {
            DispatchManagement::STATUS_PAID    => 'Paid',
            DispatchManagement::STATUS_PARTIAL => 'Partial Payment',
            default                            => 'Unpaid',
        };
    }

    private function orderPaymentLabel(?string $status): string
    {
        return match ($status) {
            'paid'    => 'Paid',
            'partial' => 'Partial Payment',
            default   => 'Unpaid',
        };
    }

    public function forPaymentPendingReminder(DispatchManagement $dispatch, float $lateFeeAddedToday): array
    {
        return $this->forDispatch($dispatch, [
            'late_fee_added_today' => PaymentReceivableService::formatMoney($lateFeeAddedToday),
            'overdue_days'         => $this->receivableService->overdueDays($dispatch),
        ]);
    }

    public function previousPaymentExtras(DispatchManagement $dispatch): array
    {
        $previousStatus = (int) $dispatch->getOriginal('status');
        $extras = [
            'previous_payment_status' => $this->dispatchPaymentLabel($previousStatus),
        ];

        if ($previousStatus === DispatchManagement::STATUS_PARTIAL) {
            $extras['previous_partial_paid_amount'] = PaymentReceivableService::formatMoney(
                (float) ($dispatch->getOriginal('partial_paid_amount') ?? 0)
            );
        }

        return $extras;
    }
}
