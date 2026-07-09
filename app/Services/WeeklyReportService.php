<?php

namespace App\Services;

use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Support\ProductUnit;
use App\Support\SalesScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyReportService
{
    public function __construct(
        private SequentialDispatchService $sequentialDispatch,
        private PaymentReceivableService $receivableService,
    ) {}

    /**
     * Thursday-start week dates (Thu → Wed) containing or starting from $anchor.
     *
     * @return list<Carbon>
     */
    public function weekDatesFromThursday(Carbon|string $anchor): array
    {
        $date = Carbon::parse($anchor)->startOfDay();

        // Carbon: Sunday=0 … Saturday=6. Thursday=4.
        $daysSinceThursday = ($date->dayOfWeek - Carbon::THURSDAY + 7) % 7;
        $thursday = $date->copy()->subDays($daysSinceThursday);

        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $thursday->copy()->addDays($i);
        }

        return $dates;
    }

    /**
     * Create a single-day report. Throws if date already exists.
     */
    public function createDayReport(string $reportDate, ?int $createdBy = null): WeeklyReport
    {
        $date = Carbon::parse($reportDate)->toDateString();

        if (WeeklyReport::whereDate('report_date', $date)->exists()) {
            throw ValidationException::withMessages([
                'report_date' => 'A weekly report already exists for this date.',
            ]);
        }

        return WeeklyReport::create([
            'report_date'      => $date,
            'already_produced' => 0,
            'created_by'       => $createdBy ?? auth()->id(),
        ]);
    }

    /**
     * Create day shells for Thu–Wed week. Skips dates that already have a report.
     *
     * @return array{created: Collection<int, WeeklyReport>, skipped: list<string>}
     */
    public function createWeekReports(string $weekStartDate, ?int $createdBy = null): array
    {
        $dates = $this->weekDatesFromThursday($weekStartDate);
        $created = collect();
        $skipped = [];

        foreach ($dates as $date) {
            $dateStr = $date->toDateString();
            if (WeeklyReport::whereDate('report_date', $dateStr)->exists()) {
                $skipped[] = $dateStr;
                continue;
            }

            $created->push(WeeklyReport::create([
                'report_date'      => $dateStr,
                'already_produced' => 0,
                'created_by'       => $createdBy ?? auth()->id(),
            ]));
        }

        if ($created->isEmpty()) {
            throw ValidationException::withMessages([
                'week_start' => 'Reports already exist for all days in this week.',
            ]);
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Qty already reserved on pending weekly-report rows (any date).
     * Confirmed rows are excluded — those are already counted via dispatch pendingQty().
     */
    public function reservedPendingWeeklyQty(int $orderItemId, ?int $exceptItemId = null): int
    {
        $query = WeeklyReportItem::query()
            ->where('order_item_id', $orderItemId)
            ->where('status', WeeklyReportItem::STATUS_PENDING);

        if ($exceptItemId) {
            $query->where('id', '!=', $exceptItemId);
        }

        return (int) $query->sum('quantity');
    }

    /**
     * Remaining qty that can still be planned on weekly reports for this order line.
     * = order pending (after dispatches) − other pending weekly-report reservations.
     */
    public function availableWeeklyQty(OrderItem $orderItem, ?int $exceptItemId = null): int
    {
        return max(0, $orderItem->pendingQty() - $this->reservedPendingWeeklyQty((int) $orderItem->id, $exceptItemId));
    }

    /**
     * @param  array{already_produced: float|int|string, production_hours?: float|int|string|null}  $data
     */
    public function updateFooter(WeeklyReport $report, array $data): WeeklyReport
    {
        $report->loadMissing('items.product:id,unit');
        $total = $report->totalQuantityInBags();
        $produced = max(0, (float) $data['already_produced']);

        if ($produced > $total) {
            throw ValidationException::withMessages([
                'already_produced' => 'Already produced / ready stock cannot exceed total quantity ('
                    . number_format($total, 2) . ').',
            ]);
        }

        $hasHoursInput = array_key_exists('production_hours', $data)
            && $data['production_hours'] !== null
            && $data['production_hours'] !== '';

        $payload = ['already_produced' => $produced];

        if ($hasHoursInput) {
            $payload['production_hours'] = max(0, (float) $data['production_hours']);
        } else {
            $report->already_produced = $produced;
            $payload['production_hours'] = $report->calculatedProductionHours();
        }

        $report->update($payload);

        return $report->refresh();
    }

    /** @deprecated Use updateFooter */
    public function updateAlreadyProduced(WeeklyReport $report, float $value): WeeklyReport
    {
        return $this->updateFooter($report, ['already_produced' => $value]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addItem(WeeklyReport $report, array $data): WeeklyReportItem
    {
        $orderItem = OrderItem::with(['product', 'order', 'dispatches'])->findOrFail($data['order_item_id']);
        SalesScope::authorizeOrderAccess($orderItem->order);

        $available = $this->availableWeeklyQty($orderItem);
        $qty = (int) $data['quantity'];

        if ($qty < 1) {
            throw ValidationException::withMessages([
                'quantity' => ProductUnit::minMessage(),
            ]);
        }

        if ($available < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'No remaining pending quantity for this order line (already planned on weekly reports).',
            ]);
        }

        if ($qty > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The entered quantity cannot exceed the remaining pending quantity (' . $available . ').',
            ]);
        }

        $maxSort = (int) $report->items()->max('sort_order');

        return $report->items()->create([
            'sort_order'     => $maxSort + 1,
            'order_id'       => $orderItem->order_id,
            'order_item_id'  => $orderItem->id,
            'product_id'     => $orderItem->product_id,
            'quantity'       => $qty,
            'transport_id'   => $data['transport_id'] ?? null,
            'truck_number'   => $data['truck_number'] ?? null,
            'driver_contact' => $data['driver_contact'] ?? null,
            'note'           => $data['note'] ?? null,
            'status'         => WeeklyReportItem::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(WeeklyReportItem $item, array $data): WeeklyReportItem
    {
        if ($item->isLocked()) {
            throw ValidationException::withMessages([
                'item' => 'Confirmed rows cannot be edited.',
            ]);
        }

        $orderItem = OrderItem::with('dispatches')->findOrFail($item->order_item_id);
        $available = $this->availableWeeklyQty($orderItem, (int) $item->id);
        $qty = (int) ($data['quantity'] ?? $item->quantity);

        if ($qty < 1) {
            throw ValidationException::withMessages([
                'quantity' => ProductUnit::minMessage(),
            ]);
        }

        if ($qty > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'The entered quantity cannot exceed the remaining pending quantity (' . $available . ').',
            ]);
        }

        $item->update([
            'quantity'       => $qty,
            'transport_id'   => $data['transport_id'] ?? $item->transport_id,
            'truck_number'   => $data['truck_number'] ?? $item->truck_number,
            'driver_contact' => $data['driver_contact'] ?? $item->driver_contact,
            'note'           => array_key_exists('note', $data) ? $data['note'] : $item->note,
            'sort_order'     => isset($data['sort_order']) ? (int) $data['sort_order'] : $item->sort_order,
        ]);

        return $item->refresh();
    }

    /**
     * @param  list<array{id: int, sort_order: int}>  $orders
     */
    public function reorderItems(WeeklyReport $report, array $orders): void
    {
        DB::transaction(function () use ($report, $orders) {
            foreach ($orders as $row) {
                WeeklyReportItem::where('weekly_report_id', $report->id)
                    ->where('id', $row['id'])
                    ->where('status', WeeklyReportItem::STATUS_PENDING)
                    ->update(['sort_order' => (int) $row['sort_order']]);
            }
        });
    }

    public function deleteItem(WeeklyReportItem $item): void
    {
        if ($item->isLocked()) {
            throw ValidationException::withMessages([
                'item' => 'Confirmed rows cannot be deleted. Delete the dispatch entry first if needed.',
            ]);
        }

        $item->delete();
    }

    public function deleteReport(WeeklyReport $report): void
    {
        if ($report->hasConfirmedItems()) {
            throw ValidationException::withMessages([
                'report' => 'Cannot delete a report that has confirmed dispatch rows.',
            ]);
        }

        $report->items()->delete();
        $report->delete();
    }

    /**
     * Confirm a planned row → create real dispatch (same guards as DispatchManagementController::store).
     *
     * @param  array{status: int|string, partial_paid_amount?: mixed}  $payment
     */
    public function confirmItem(WeeklyReportItem $item, array $payment): DispatchManagement
    {
        if ($item->isConfirmed()) {
            throw ValidationException::withMessages([
                'item' => 'This row is already confirmed.',
            ]);
        }

        $item->loadMissing(['order.items.dispatches', 'orderItem.dispatches', 'product', 'report']);

        $missing = [];
        if (! $item->transport_id) {
            $missing['transport_id'] = 'Please select a transporter before confirming.';
        }
        if (! $item->truck_number) {
            $missing['truck_number'] = 'Truck number is required before confirming.';
        }
        if (! $item->driver_contact) {
            $missing['driver_contact'] = 'Driver contact is required before confirming.';
        }
        if ($missing) {
            throw ValidationException::withMessages($missing);
        }

        $orderItem = $item->orderItem;
        $pending = $orderItem->pendingQty();

        if ((int) $item->quantity > $pending) {
            throw ValidationException::withMessages([
                'quantity' => 'The entered quantity cannot exceed the pending quantity (' . $pending . ').',
            ]);
        }

        $parentOrder = $item->order;
        SalesScope::authorizeOrderAccess($parentOrder);

        $blockingPrior = $this->sequentialDispatch->findBlockingOrderFor($parentOrder, ['items.dispatches']);
        if ($blockingPrior) {
            throw ValidationException::withMessages([
                'order_id' => 'Order ' . $blockingPrior->unique_order_id
                    . ' must be fully dispatched before dispatching this order.',
            ]);
        }

        $status = (int) $payment['status'];
        $partial = $status === DispatchManagement::STATUS_PARTIAL
            ? $payment['partial_paid_amount']
            : null;

        return DB::transaction(function () use ($item, $status, $partial) {
            $dispatch = DispatchManagement::create([
                'order_id'            => $item->order_id,
                'order_item_id'       => $item->order_item_id,
                'product_id'          => $item->product_id,
                'no_of_bags'          => (int) $item->quantity,
                'dispatch_date'       => $item->report->report_date->toDateString(),
                'transport_id'        => $item->transport_id,
                'truck_number'        => $item->truck_number,
                'driver_contact'      => $item->driver_contact,
                'status'              => $status,
                'partial_paid_amount' => $partial,
            ]);

            $item->update([
                'status'      => WeeklyReportItem::STATUS_CONFIRMED,
                'dispatch_id' => $dispatch->id,
            ]);

            $order = OrderManagement::with(['items.dispatches'])->find($item->order_id);
            if ($order) {
                $order->syncPaymentStatusFromDispatches();
            }

            return $dispatch;
        });
    }

    /**
     * Pending order line items for manual pick (AJAX search).
     *
     * @return list<array<string, mixed>>
     */
    public function searchPendingOrderItems(?string $term = null, int $limit = 30): array
    {
        $query = OrderItem::query()
            ->with([
                'product:id,name,unit',
                'order:id,unique_order_id,dealer_id',
                'order.dealer:id,user_id,firm_shop_name,city_id',
                'order.dealer.user:id,name',
                'order.dealer.city:id,city_name',
                'dispatches',
            ])
            ->whereHas('order', function ($q) {
                SalesScope::scopeOrders($q);
            })
            ->whereRaw(
                'order_items.qty > (
                    COALESCE((
                        SELECT SUM(dm.no_of_bags)
                        FROM dispatch_management dm
                        WHERE dm.order_item_id = order_items.id
                          AND dm.deleted_at IS NULL
                    ), 0)
                    + COALESCE((
                        SELECT SUM(wri.quantity)
                        FROM weekly_report_items wri
                        WHERE wri.order_item_id = order_items.id
                          AND wri.status = ?
                          AND wri.deleted_at IS NULL
                    ), 0)
                )',
                [WeeklyReportItem::STATUS_PENDING]
            );

        if ($term) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('order', fn ($oq) => $oq->where('unique_order_id', 'like', $like))
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', $like))
                    ->orWhereHas('order.dealer.user', fn ($dq) => $dq->where('name', 'like', $like))
                    ->orWhereHas('order.dealer', fn ($dq) => $dq->where('firm_shop_name', 'like', $like));
            });
        }

        return $query->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (OrderItem $item) {
                $dealer = $item->order?->dealer;
                $available = $this->availableWeeklyQty($item);

                return [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'unique_order_id'=> $item->order?->unique_order_id,
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product?->name ?? '—',
                    'product_unit'   => $item->product?->unit,
                    'pending_qty'    => $available,
                    'dealer_name'    => $dealer?->user?->name ?? $dealer?->firm_shop_name ?? '—',
                    'city_name'      => $dealer?->city?->city_name ?? '—',
                    'label'          => ($item->order?->unique_order_id ?? '')
                        . ' — ' . ($item->product?->name ?? '')
                        . ' (remaining: ' . $available . ' '
                        . ProductUnit::dispatchedSuffix($item->product?->unit) . ')',
                ];
            })
            ->filter(fn (array $row) => $row['pending_qty'] > 0)
            ->values()
            ->all();
    }
}
