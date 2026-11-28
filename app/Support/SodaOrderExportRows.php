<?php

namespace App\Support;

use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Services\PaymentReceivableService;
use Illuminate\Support\Collection;

/** Shared rows for the Soda/Order Excel sheets and PDF. */
class SodaOrderExportRows
{
    /**
     * @param  array{ordered: int, dispatched: int, pending: int}  $summary
     * @param  Collection<int, OrderManagement>  $orders
     * @return array{
     *     cards: list<list<int|string>>,
     *     groups: list<array{meta: list<array{label: string, value: string}>, products: list<array{line: list<int|string>, dispatches: list<list<int|string>>}>}>
     * }
     */
    public static function build(array $summary, Collection $orders): array
    {
        $receivable = app(PaymentReceivableService::class);
        $groups = [];

        foreach ($orders as $order) {
            $groups[] = self::group($order, $receivable);
        }

        return [
            'cards' => [
                ['Total number of bags', (int) ($summary['ordered'] ?? 0)],
                ['Total number of dispatch bags', (int) ($summary['dispatched'] ?? 0)],
                ['Total number of pending bags', (int) ($summary['pending'] ?? 0)],
                ['Orders', $orders->count()],
            ],
            'groups' => $groups,
        ];
    }

    /**
     * One worksheet laid out like the PDF: cards, then each order, then each product with its dispatches underneath.
     *
     * @param  array{ordered: int, dispatched: int, pending: int}  $summary
     * @param  Collection<int, OrderManagement>  $orders
     * @return array{rows: list<list<int|string>>, kinds: list<string>}
     */
    public static function excel(array $summary, Collection $orders, string $title): array
    {
        $built = self::build($summary, $orders);
        $rows = [];
        $kinds = [];

        $push = function (array $row, string $kind) use (&$rows, &$kinds) {
            $rows[] = array_pad($row, 11, '');
            $kinds[] = $kind;
        };

        $push([$title], 'title');
        $push(['Exported ' . now()->format('d M Y') . ' · ' . count($built['groups']) . ' orders'], 'subtitle');
        $push([], 'blank');
        $push(['Cards'], 'section');
        $push(self::cardHeadings(), 'cards-head');
        foreach ($built['cards'] as $card) {
            $push($card, 'cards');
        }

        $push([], 'blank');
        $push(['Orders'], 'section');

        foreach ($built['groups'] as $group) {
            $push(array_column($group['meta'], 'label'), 'order-head');
            $push(array_column($group['meta'], 'value'), 'order-values');

            if ($group['products'] === []) {
                $push(['No products'], 'empty');
                $push([], 'blank');
                continue;
            }

            foreach ($group['products'] as $product) {
                $push(self::lineHeadings(), 'product-head');
                $push($product['line'], 'product');
                $push(self::dispatchHeadings(), 'dispatch-head');

                if ($product['dispatches'] === []) {
                    $push(['No dispatch'], 'empty');
                    continue;
                }

                foreach ($product['dispatches'] as $dispatch) {
                    $push($dispatch, 'dispatch');
                }
            }

            $push([], 'blank');
        }

        return ['rows' => $rows, 'kinds' => $kinds];
    }

    /** @return list<string> */
    public static function cardHeadings(): array
    {
        return ['Metric', 'Value'];
    }

    /** @return list<string> */
    public static function lineHeadings(): array
    {
        return ['Product', 'Unit Price', 'Ordered Bags', 'Dispatched Bags', 'Pending Bags', 'Line Total'];
    }

    /** @return list<string> */
    public static function dispatchHeadings(): array
    {
        return [
            'Dispatch Date',
            'Bags',
            'Dispatch Amount',
            'Less Freight',
            'Final Amount',
            'Transporter',
            'Truck Number',
            'Driver Contact',
            'Late Fee',
            'Balance Due',
            'Payment Status',
        ];
    }

    /**
     * @return array{meta: list<array{label: string, value: string}>, lines: list<list<int|string>>, dispatches: list<list<int|string>>}
     */
    private static function group(OrderManagement $order, PaymentReceivableService $receivable): array
    {
        $due = $receivable->summarizeOrderPendingDispatches($order->dispatches);

        $meta = [
            ['label' => 'Order ID', 'value' => (string) $order->unique_order_id],
            ['label' => 'Order Type', 'value' => $order->orderTypeLabel()],
        ];
        if ($order->isRetail()) {
            $meta[] = ['label' => 'Party Name', 'value' => $order->party_name ?: '—'];
            $meta[] = ['label' => 'Brand', 'value' => $order->brand?->name ?? '—'];
            $meta[] = ['label' => 'Note', 'value' => $order->note ?: '—'];
        } else {
            $meta[] = ['label' => 'Broker', 'value' => $order->broker?->name ?? '—'];
            $meta[] = ['label' => 'Brand', 'value' => $order->brand?->name ?? '—'];
            $meta[] = ['label' => 'Dealer', 'value' => $order->dealer?->user?->name ?? $order->dealer?->firm_shop_name ?? '—'];
        }
        $meta = array_merge($meta, [
            ['label' => 'Order Date', 'value' => $order->order_date?->format('d-m-Y') ?? '—'],
            ['label' => 'Payment Status', 'value' => self::orderPaymentLabel($order->payment_status)],
            ['label' => 'Payment Type', 'value' => $order->paymentTypeLabel()],
            ['label' => 'Order Amount', 'value' => number_format((float) $order->grand_total, 2, '.', '')],
            ['label' => 'Less Freight', 'value' => number_format($order->lessFreightTotal(), 2, '.', '')],
            ['label' => 'Net Amount', 'value' => number_format($order->netAmount(), 2, '.', '')],
            ['label' => 'Due Charges', 'value' => number_format($due['total_balance_due'], 2, '.', '')],
        ]);

        $dispatchesByItem = $order->dispatches->groupBy(
            fn (DispatchManagement $dispatch) => (int) $dispatch->order_item_id
        );
        $products = [];

        foreach ($order->items as $item) {
            $ordered = (int) $item->qty;
            $itemDispatches = $dispatchesByItem->get($item->id, collect());
            $dispatched = (int) $itemDispatches->sum('no_of_bags');
            $products[] = [
                'line' => [
                    $item->product?->name ?? '—',
                    number_format((float) $item->unit_price, 2, '.', ''),
                    $ordered,
                    $dispatched,
                    max(0, $ordered - $dispatched),
                    number_format((float) $item->total_price, 2, '.', ''),
                ],
                'dispatches' => self::dispatchRows($itemDispatches, $receivable),
            ];
        }

        $matchedItemIds = $order->items->pluck('id')->map(fn ($id) => (int) $id);
        $orphans = $order->dispatches->reject(
            fn (DispatchManagement $dispatch) => $matchedItemIds->contains((int) $dispatch->order_item_id)
        );
        if ($orphans->isNotEmpty()) {
            $products[] = [
                'line' => ['Other', '—', 0, (int) $orphans->sum('no_of_bags'), 0, '—'],
                'dispatches' => self::dispatchRows($orphans, $receivable),
            ];
        }

        return [
            'meta' => $meta,
            'products' => $products,
        ];
    }

    /**
     * @param  Collection<int, DispatchManagement>  $dispatches
     * @return list<list<int|string>>
     */
    private static function dispatchRows(Collection $dispatches, PaymentReceivableService $receivable): array
    {
        return $dispatches->map(function (DispatchManagement $dispatch) use ($receivable) {
            $bags = (int) $dispatch->no_of_bags;
            $unitPrice = (float) ($dispatch->orderItem?->unit_price ?? 0);

            return [
                $dispatch->dispatch_date?->format('d-m-Y') ?? '—',
                $bags,
                number_format(LessFreight::gross($bags, $unitPrice), 2, '.', ''),
                number_format(LessFreight::deduction($bags, $dispatch->less_freight_per_qty), 2, '.', ''),
                number_format(LessFreight::finalAmount($bags, $unitPrice, $dispatch->less_freight_per_qty), 2, '.', ''),
                $dispatch->transporter?->name ?? '—',
                $dispatch->truck_number ?: '—',
                $dispatch->driver_contact ?: '—',
                number_format($receivable->accruedLateFee($dispatch), 2, '.', ''),
                number_format($receivable->balanceDue($dispatch), 2, '.', ''),
                self::dispatchPaymentLabel((int) $dispatch->status),
            ];
        })->values()->all();
    }

    private static function orderPaymentLabel(?string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'partial' => 'Partial',
            default => 'Unpaid',
        };
    }

    private static function dispatchPaymentLabel(int $status): string
    {
        return match ($status) {
            DispatchManagement::STATUS_PAID => 'Paid',
            DispatchManagement::STATUS_PARTIAL => 'Partial',
            default => 'Unpaid',
        };
    }
}
