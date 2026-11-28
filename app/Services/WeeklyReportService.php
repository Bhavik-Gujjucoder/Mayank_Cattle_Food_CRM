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

    public const MAX_WORKSPACE_DAYS = 31;

    public const MAX_DASHBOARD_DAYS = 7;

    /**
     * @return array{mode: string, date: string|null, date_from: string|null, date_to: string|null, order_type: string|null}
     */
    public function resolveWorkspaceFilters(
        ?string $date = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $orderType = null,
    ): array {
        $today = now()->toDateString();
        $orderType = in_array($orderType, [OrderManagement::TYPE_SODA, OrderManagement::TYPE_RETAIL], true)
            ? $orderType
            : null;

        if ($dateFrom || $dateTo) {
            $from = $dateFrom ?: $dateTo ?: $today;
            $to = $dateTo ?: $dateFrom ?: $today;

            return [
                'mode'       => 'range',
                'date'       => null,
                'date_from'  => Carbon::parse($from)->toDateString(),
                'date_to'    => Carbon::parse($to)->toDateString(),
                'order_type' => $orderType,
            ];
        }

        return [
            'mode'       => 'single',
            'date'       => Carbon::parse($date ?: $today)->toDateString(),
            'date_from'  => null,
            'date_to'    => null,
            'order_type' => $orderType,
        ];
    }

    /**
     * @return list<string> Y-m-d dates in order
     */
    public function datesForWorkspace(array $filters, int $maxDays = self::MAX_WORKSPACE_DAYS): array
    {
        if ($filters['mode'] === 'range') {
            $from = Carbon::parse($filters['date_from'])->startOfDay();
            $to = Carbon::parse($filters['date_to'])->startOfDay();

            if ($to->lt($from)) {
                [$from, $to] = [$to, $from];
            }

            $dayCount = $from->diffInDays($to) + 1;
            if ($dayCount > $maxDays) {
                throw ValidationException::withMessages([
                    'date_to' => 'Maximum ' . $maxDays . ' days allowed in one view.',
                ]);
            }

            $dates = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $dates[] = $d->toDateString();
            }

            return $dates;
        }

        return [Carbon::parse($filters['date'])->toDateString()];
    }

    public function findOrCreateDayReport(string $reportDate, ?int $createdBy = null): WeeklyReport
    {
        $date = Carbon::parse($reportDate)->toDateString();

        $existing = WeeklyReport::whereDate('report_date', $date)->first();
        if ($existing) {
            return $existing;
        }

        return WeeklyReport::create([
            'report_date'      => $date,
            'already_produced' => 0,
            'bags_per_hour'    => WeeklyReport::BAGS_PER_HOUR,
            'created_by'       => $createdBy ?? auth()->id(),
        ]);
    }

    /**
     * @return list<array{date: Carbon, report: WeeklyReport|null}>
     */
    public function workspaceDays(array $filters, bool $autoCreate = false, int $maxDays = self::MAX_WORKSPACE_DAYS): array
    {
        $dates = $this->datesForWorkspace($filters, $maxDays);

        if ($autoCreate) {
            foreach ($dates as $dateStr) {
                $this->findOrCreateDayReport($dateStr);
            }
        }

        $query = WeeklyReport::query()
            ->with($this->reportRelations());

        if (count($dates) === 1) {
            $query->whereDate('report_date', $dates[0]);
        } else {
            $query->whereDate('report_date', '>=', $dates[0])
                ->whereDate('report_date', '<=', $dates[array_key_last($dates)]);
        }

        $reports = $query
            ->orderBy('report_date')
            ->get()
            ->keyBy(fn (WeeklyReport $report) => $report->report_date->toDateString());

        return collect($dates)->map(function (string $dateStr) use ($reports) {
            return [
                'date'   => Carbon::parse($dateStr)->startOfDay(),
                'report' => $reports->get($dateStr),
            ];
        })->all();
    }

    /**
     * @return list<string>
     */
    public function reportRelations(): array
    {
        return [
            'items.product:id,name,unit',
            'items.orderItem:id,unit_price',
            'items.order:id,unique_order_id,dealer_id,order_type,party_name,payment_type',
            'items.order.dealer:id,user_id,firm_shop_name,city_id',
            'items.order.dealer.user:id,name',
            'items.order.dealer.city:id,city_name',
            'items.transporter:id,name,phone_no',
            'items.dispatch:id,accrued_late_fee,late_fee_source,late_fee_dispatch_id',
            'items.dispatch.lateFeeDispatch:id,order_id,dispatch_date',
            'items.dispatch.lateFeeDispatch.order:id,unique_order_id',
            'items.lateFeeDispatch:id,order_id,dispatch_date',
            'items.lateFeeDispatch.order:id,unique_order_id',
        ];
    }

    /**
     * Create a single-day report. Throws if date already exists.
     */
    public function createDayReport(string $reportDate, ?int $createdBy = null): WeeklyReport
    {
        $date = Carbon::parse($reportDate)->toDateString();

        if (WeeklyReport::whereDate('report_date', $date)->exists()) {
            throw ValidationException::withMessages([
                'report_date' => 'A Daily Dispatch already exists for this date.',
            ]);
        }

        return WeeklyReport::create([
            'report_date'      => $date,
            'already_produced' => 0,
            'bags_per_hour'    => WeeklyReport::BAGS_PER_HOUR,
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
                'bags_per_hour'    => WeeklyReport::BAGS_PER_HOUR,
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

    public function orderHasConfirmedItems(int $orderId): bool
    {
        return WeeklyReportItem::query()
            ->where('order_id', $orderId)
            ->where('status', WeeklyReportItem::STATUS_CONFIRMED)
            ->exists();
    }

    /** Live Daily Dispatch rows only. Soft-deleted rows are excluded. */
    public function orderHasLiveItems(int $orderId): bool
    {
        return WeeklyReportItem::query()
            ->where('order_id', $orderId)
            ->exists();
    }

    /**
     * Soft-delete the confirmed Daily Dispatch row that created this dispatch,
     * then insert the same row again as pending (no new dispatch).
     */
    public function recreatePendingFromDeletedDispatch(DispatchManagement $dispatch): void
    {
        if (! $dispatch->weekly_report_item_id) {
            return;
        }

        $source = WeeklyReportItem::withTrashed()->find($dispatch->weekly_report_item_id);
        if (! $source) {
            return;
        }

        $reportExists = WeeklyReport::withTrashed()->whereKey($source->weekly_report_id)->exists();
        if (! $reportExists) {
            return;
        }

        WeeklyReportItem::create([
            'weekly_report_id' => $source->weekly_report_id,
            'sort_order'       => $source->sort_order,
            'order_id'         => $source->order_id,
            'order_item_id'    => $source->order_item_id,
            'product_id'       => $source->product_id,
            'quantity'         => $source->quantity,
            'less_freight_per_qty' => $dispatch->less_freight_per_qty,
            'transport_id'     => $source->transport_id,
            'truck_number'     => $source->truck_number,
            'driver_contact'   => $source->driver_contact,
            'note'             => $source->note,
            'status'           => WeeklyReportItem::STATUS_PENDING,
            'dispatch_id'      => null,
        ]);

        if (! $source->trashed()) {
            $source->delete();
        }
    }

    public function syncLessFreightFromDispatch(DispatchManagement $dispatch): void
    {
        if (! $dispatch->weekly_report_item_id) {
            return;
        }

        WeeklyReportItem::query()
            ->whereKey($dispatch->weekly_report_item_id)
            ->update(['less_freight_per_qty' => $dispatch->less_freight_per_qty]);
    }

    public function orderItemHasConfirmedItems(int $orderItemId): bool
    {
        return WeeklyReportItem::query()
            ->where('order_item_id', $orderItemId)
            ->where('status', WeeklyReportItem::STATUS_CONFIRMED)
            ->exists();
    }

    /** @param  list<int>  $orderItemIds */
    public function deleteItemsForOrderItems(array $orderItemIds): void
    {
        if ($orderItemIds === []) {
            return;
        }

        WeeklyReportItem::query()
            ->whereIn('order_item_id', $orderItemIds)
            ->delete();
    }

    public function deleteItemsForOrder(int $orderId): void
    {
        WeeklyReportItem::query()
            ->where('order_id', $orderId)
            ->delete();
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
     * @param  array{already_produced: float|int|string, production_hours?: float|int|string|null, bags_per_hour?: float|int|string|null}  $data
     */
    public function updateFooter(WeeklyReport $report, array $data): WeeklyReport
    {
        $report->loadMissing('items.product:id,unit');
        $produced = max(0, (float) $data['already_produced']);

        $hasHoursInput = array_key_exists('production_hours', $data)
            && $data['production_hours'] !== null
            && $data['production_hours'] !== '';

        $hasBagsPerHourInput = array_key_exists('bags_per_hour', $data)
            && $data['bags_per_hour'] !== null
            && $data['bags_per_hour'] !== '';

        if ($hasBagsPerHourInput) {
            $bagsPerHour = (float) $data['bags_per_hour'];
            if ($bagsPerHour <= 0) {
                throw ValidationException::withMessages([
                    'bags_per_hour' => 'Bags per hour must be greater than zero.',
                ]);
            }
            $report->bags_per_hour = $bagsPerHour;
        }

        $payload = ['already_produced' => $produced];

        if ($hasBagsPerHourInput) {
            $payload['bags_per_hour'] = $report->bags_per_hour;
        }

        if ($hasHoursInput) {
            $payload['production_hours'] = (float) $data['production_hours'];
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
     * Create one or more pending planned rows for the same order line.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, WeeklyReportItem>
     */
    public function addItem(WeeklyReport $report, array $data): Collection
    {
        $orderItem = OrderItem::with(['product', 'order', 'dispatches'])->findOrFail($data['order_item_id']);
        SalesScope::authorizeOrderAccess($orderItem->order);

        $available = $this->availableWeeklyQty($orderItem);
        $qty = (int) $data['quantity'];
        $entries = max(1, (int) ($data['no_of_entries'] ?? 1));

        if ($qty < 1) {
            throw ValidationException::withMessages([
                'quantity' => ProductUnit::minMessage(),
            ]);
        }

        if ($entries < 1) {
            throw ValidationException::withMessages([
                'no_of_entries' => 'No. of Entries must be at least 1.',
            ]);
        }

        if ($entries > 100) {
            throw ValidationException::withMessages([
                'no_of_entries' => 'No. of Entries cannot exceed 100.',
            ]);
        }

        if ($available < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'No remaining pending quantity for this order line (already planned on Daily Dispatch).',
            ]);
        }

        $totalQty = $qty * $entries;

        $freightMessage = \App\Support\LessFreight::validationMessage(
            $data['less_freight_per_qty'] ?? null,
            (float) $orderItem->unit_price
        );
        if ($freightMessage) {
            throw ValidationException::withMessages([
                'less_freight_per_qty' => $freightMessage,
            ]);
        }

        $lessFreight = \App\Support\LessFreight::normalize($data['less_freight_per_qty'] ?? null);
        $lateFee = $this->receivableService->resolveManualLateFee(
            array_key_exists('accrued_late_fee', $data) ? ($data['accrued_late_fee'] ?? '') : null,
            0
        );
        $parentOrder = $orderItem->order;
        if ($parentOrder && $parentOrder->isRetail() && ($parentOrder->payment_type ?? 'cash') !== PaymentReceivableService::PAYMENT_TYPE_CREDIT) {
            $lateFee = 0.0;
            $lateFeeDispatchId = null;
        } elseif ($parentOrder && $parentOrder->isRetail()) {
            $lateFeeError = $this->receivableService->lateFeeDispatchErrorForOrder(
                $data['late_fee_dispatch_id'] ?? null,
                (int) $parentOrder->id,
                $lateFee
            );
            if ($lateFeeError) {
                throw ValidationException::withMessages([
                    'late_fee_dispatch_id' => $lateFeeError,
                ]);
            }
            $lateFeeDispatchId = $lateFee > 0 && ! empty($data['late_fee_dispatch_id'])
                ? (int) $data['late_fee_dispatch_id']
                : null;
        } else {
            $lateFeeError = $this->receivableService->lateFeeDispatchError(
                $data['late_fee_dispatch_id'] ?? null,
                (int) ($parentOrder?->dealer_id ?? 0),
                $lateFee
            );
            if ($lateFeeError) {
                throw ValidationException::withMessages([
                    'late_fee_dispatch_id' => $lateFeeError,
                ]);
            }
            $lateFeeDispatchId = $lateFee > 0 ? (int) $data['late_fee_dispatch_id'] : null;
        }

        if ($totalQty > $available) {
            if ($entries > 1) {
                throw ValidationException::withMessages([
                    'no_of_entries' => 'Qty × No. of Entries (' . $qty . ' × ' . $entries . ' = ' . $totalQty
                        . ') cannot exceed the remaining pending quantity (' . $available . ').',
                ]);
            }

            throw ValidationException::withMessages([
                'quantity' => 'The entered quantity cannot exceed the remaining pending quantity (' . $available . ').',
            ]);
        }

        return DB::transaction(function () use ($report, $orderItem, $data, $qty, $entries, $lessFreight, $lateFee, $lateFeeDispatchId) {
            $maxSort = (int) $report->items()->max('sort_order');
            $created = collect();

            for ($i = 1; $i <= $entries; $i++) {
                $created->push($report->items()->create([
                    'sort_order'     => $maxSort + $i,
                    'order_id'       => $orderItem->order_id,
                    'order_item_id'  => $orderItem->id,
                    'product_id'     => $orderItem->product_id,
                    'quantity'       => $qty,
                    'less_freight_per_qty' => $lessFreight,
                    'accrued_late_fee' => $i === 1 ? $lateFee : 0,
                    'late_fee_dispatch_id' => $i === 1 ? $lateFeeDispatchId : null,
                    'transport_id'   => $data['transport_id'] ?? null,
                    'truck_number'   => $data['truck_number'] ?? null,
                    'driver_contact' => $data['driver_contact'] ?? null,
                    'note'           => $data['note'] ?? null,
                    'status'         => WeeklyReportItem::STATUS_PENDING,
                ]));
            }

            return $created;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(WeeklyReportItem $item, array $data): WeeklyReportItem
    {
        if ($item->isLocked()) {
            if (array_key_exists('sort_order', $data)) {
                $item->update(['sort_order' => (int) $data['sort_order']]);

                return $item->refresh();
            }

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

        $item->loadMissing('order:id,order_type,dealer_id,payment_type');
        $isRetail = (bool) $item->order?->isRetail();

        $lessFreight = $item->less_freight_per_qty;
        if ($isRetail) {
            $lessFreight = null;
        } elseif (array_key_exists('less_freight_per_qty', $data)) {
            $freightMessage = \App\Support\LessFreight::validationMessage(
                $data['less_freight_per_qty'],
                (float) $orderItem->unit_price
            );
            if ($freightMessage) {
                throw ValidationException::withMessages([
                    'less_freight_per_qty' => $freightMessage,
                ]);
            }
            $lessFreight = \App\Support\LessFreight::normalize($data['less_freight_per_qty']);
        }

        $lateFee = round((float) ($item->accrued_late_fee ?? 0), 2);
        $lateFeeDispatchId = $item->late_fee_dispatch_id;
        if (array_key_exists('accrued_late_fee', $data) || array_key_exists('late_fee_dispatch_id', $data)) {
            $lateFee = $this->receivableService->resolveManualLateFee(
                array_key_exists('accrued_late_fee', $data) ? ($data['accrued_late_fee'] ?? '') : $lateFee,
                $lateFee
            );
            $parentOrder = $item->order;
            if ($parentOrder && $parentOrder->isRetail() && ($parentOrder->payment_type ?? 'cash') !== PaymentReceivableService::PAYMENT_TYPE_CREDIT) {
                $lateFee = 0.0;
                $lateFeeDispatchId = null;
            } elseif ($parentOrder && $parentOrder->isRetail()) {
                $lateFeeError = $this->receivableService->lateFeeDispatchErrorForOrder(
                    $data['late_fee_dispatch_id'] ?? $lateFeeDispatchId,
                    (int) $parentOrder->id,
                    $lateFee
                );
                if ($lateFeeError) {
                    throw ValidationException::withMessages([
                        'late_fee_dispatch_id' => $lateFeeError,
                    ]);
                }
                $linked = $data['late_fee_dispatch_id'] ?? $lateFeeDispatchId;
                $lateFeeDispatchId = $lateFee > 0 && ! empty($linked) ? (int) $linked : null;
            } else {
            $lateFeeError = $this->receivableService->lateFeeDispatchError(
                $data['late_fee_dispatch_id'] ?? $lateFeeDispatchId,
                (int) ($item->order?->dealer_id ?? 0),
                $lateFee
            );
            if ($lateFeeError) {
                throw ValidationException::withMessages([
                    'late_fee_dispatch_id' => $lateFeeError,
                ]);
            }
            $lateFeeDispatchId = $lateFee > 0
                ? (int) ($data['late_fee_dispatch_id'] ?? $lateFeeDispatchId)
                : null;
            }
        }

        $item->update([
            'quantity'       => $qty,
            'less_freight_per_qty' => $lessFreight,
            'accrued_late_fee' => $lateFee,
            'late_fee_dispatch_id' => $lateFeeDispatchId,
            'transport_id'   => $isRetail ? null : ($data['transport_id'] ?? $item->transport_id),
            'truck_number'   => $isRetail ? null : ($data['truck_number'] ?? $item->truck_number),
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
     * @param  array{status: int|string, partial_paid_amount?: mixed, accrued_late_fee?: mixed}  $payment
     */
    public function confirmItem(WeeklyReportItem $item, array $payment): DispatchManagement
    {
        if ($item->isConfirmed()) {
            throw ValidationException::withMessages([
                'item' => 'This row is already confirmed.',
            ]);
        }

        $item->loadMissing(['order.items.dispatches', 'orderItem.dispatches', 'product', 'report']);

        $isRetail = (bool) $item->order?->isRetail();
        $missing = [];
        if (! $isRetail && ! $item->transport_id) {
            $missing['transport_id'] = 'Please select a transporter before confirming.';
        }
        if (! $isRetail && ! $item->truck_number) {
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

        // Sequential dispatch is disabled. Uncomment to require an older order
        // for the same dealer to be fully dispatched before this one.
        // $blockingPrior = $this->sequentialDispatch->findBlockingOrderFor($parentOrder, ['items.dispatches']);
        // if ($blockingPrior) {
        //     throw ValidationException::withMessages([
        //         'order_id' => 'Order ' . $blockingPrior->unique_order_id
        //             . ' must be fully dispatched before dispatching this order.',
        //     ]);
        // }

        $freightMessage = \App\Support\LessFreight::validationMessage(
            $item->less_freight_per_qty,
            (float) $orderItem->unit_price
        );
        if ($freightMessage) {
            throw ValidationException::withMessages([
                'less_freight_per_qty' => $freightMessage,
            ]);
        }

        $status = (int) $payment['status'];
        $partial = $status === DispatchManagement::STATUS_PARTIAL
            ? $payment['partial_paid_amount']
            : null;
        $lateFee = round((float) ($item->accrued_late_fee ?? 0), 2);
        $lateFeeDispatchId = $lateFee > 0 ? $item->late_fee_dispatch_id : null;

        if ($status === DispatchManagement::STATUS_PARTIAL) {
            $finalAmount = round(\App\Support\LessFreight::finalAmount(
                (int) $item->quantity,
                (float) $orderItem->unit_price,
                $item->less_freight_per_qty
            ) + $lateFee, 2);
            if ((float) $partial > $finalAmount) {
                throw ValidationException::withMessages([
                    'partial_paid_amount' => 'Paid amount cannot exceed total receivable of '
                        . PaymentReceivableService::formatMoney($finalAmount) . '.',
                ]);
            }
        }

        return DB::transaction(function () use ($item, $status, $partial, $lateFee, $lateFeeDispatchId) {
            $dispatch = DispatchManagement::create([
                'order_id'            => $item->order_id,
                'order_item_id'       => $item->order_item_id,
                'product_id'          => $item->product_id,
                'no_of_bags'          => (int) $item->quantity,
                'less_freight_per_qty' => $item->less_freight_per_qty,
                'dispatch_date'       => $item->report->report_date->toDateString(),
                'transport_id'        => $item->transport_id,
                'truck_number'        => $item->truck_number,
                'driver_contact'        => $item->driver_contact,
                'status'                => $status,
                'partial_paid_amount'   => $partial,
                'accrued_late_fee'      => $lateFee,
                'late_fee_dispatch_id'  => $lateFeeDispatchId,
                'late_fee_source'       => $this->receivableService->nextLateFeeSource(
                    null,
                    PaymentReceivableService::LATE_FEE_SOURCE_MANUAL,
                    0,
                    $lateFee
                ),
                'weekly_report_item_id' => $item->id,
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
     * Create a retail order when this is a new party sale, then add one Daily Dispatch row.
     * An existing pending retail line is reused so a smaller dispatch does not open a second order.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, WeeklyReportItem>
     */
    public function addRetailLine(WeeklyReport $report, array $data): Collection
    {
        if (! SalesScope::hasGlobalAccess()) {
            abort(403, 'Retail orders are available to admin and staff only.');
        }

        return DB::transaction(function () use ($report, $data) {
            if (! empty($data['order_item_id'])) {
                $orderItem = OrderItem::with('order')->findOrFail($data['order_item_id']);
                if (! $orderItem->order?->isRetail()) {
                    throw ValidationException::withMessages([
                        'order_item_id' => 'Select a pending retail order.',
                    ]);
                }
                SalesScope::authorizeOrderAccess($orderItem->order);
                $paymentType = $orderItem->order->payment_type ?? 'cash';
            } else {
                $qty = (int) $data['order_qty'];
                $price = (float) $data['unit_price'];
                $total = round($qty * $price, 2);
                $paymentType = $data['payment_type'];
                $order = OrderManagement::create([
                    'unique_order_id' => $this->nextOrderNumber($data['order_date']),
                    'order_type' => OrderManagement::TYPE_RETAIL,
                    'broker_id' => null,
                    'brand_id' => $data['brand_id'],
                    'dealer_id' => null,
                    'party_name' => $data['party_name'],
                    'order_date' => $data['order_date'],
                    'delivery_address' => null,
                    'note' => $data['note'] ?? null,
                    'payment_status' => $data['payment_status'],
                    'payment_type' => $paymentType,
                    'partial_paid_amount' => null,
                    'total_order_amount' => $total,
                    'grand_total' => $total,
                    'status' => 1,
                ]);
                $orderItem = $order->items()->create([
                    'product_id' => $data['product_id'],
                    'qty' => $qty,
                    'unit_price' => $price,
                    'total_price' => $total,
                ]);
                $orderItem->setRelation('order', $order);
            }

            return $this->addItem($report, [
                'order_item_id' => $orderItem->id,
                'quantity' => $data['quantity'],
                'less_freight_per_qty' => $data['less_freight_per_qty'] ?? null,
                'accrued_late_fee' => $paymentType === PaymentReceivableService::PAYMENT_TYPE_CREDIT
                    ? ($data['accrued_late_fee'] ?? null)
                    : null,
                'late_fee_dispatch_id' => $data['late_fee_dispatch_id'] ?? null,
                'no_of_entries' => 1,
                'transport_id' => $data['transport_id'] ?? null,
                'truck_number' => $data['truck_number'] ?? null,
                'driver_contact' => $data['driver_contact'] ?? null,
                'note' => $data['dispatch_note'] ?? null,
            ]);
        });
    }

    private function nextOrderNumber(string $orderDate): string
    {
        $date = Carbon::parse($orderDate);
        $startYear = $date->month >= 4 ? $date->year : $date->year - 1;
        $prefix = 'ORD/' . $startYear . '-' . substr((string) ($startYear + 1), -2) . '/';
        $max = 0;

        foreach (OrderManagement::withTrashed()->where('unique_order_id', 'like', $prefix . '%')->pluck('unique_order_id') as $id) {
            if (preg_match('/(\d+)$/', (string) $id, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Pending order line items for manual pick (AJAX search).
     * When dealer_id is provided, only that dealer's unpaid/partial orders are returned.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPendingOrderItems(?string $term = null, int $limit = 30, ?int $dealerId = null, string $orderType = OrderManagement::TYPE_SODA): array
    {
        $orderType = $orderType === OrderManagement::TYPE_RETAIL
            ? OrderManagement::TYPE_RETAIL
            : OrderManagement::TYPE_SODA;

        $query = OrderItem::query()
            ->with([
                'product:id,name,unit',
                'order:id,unique_order_id,order_date,dealer_id,payment_status,order_type,party_name,payment_type',
                'order.dealer:id,user_id,firm_shop_name,city_id',
                'order.dealer.user:id,name',
                'order.dealer.city:id,city_name',
                'dispatches',
            ])
            ->whereHas('order', function ($q) use ($dealerId, $orderType) {
                SalesScope::scopeOrders($q);
                $q->where('order_type', $orderType);
                $q->whereIn('payment_status', OrderManagement::pendingPaymentStatuses());

                if ($orderType === OrderManagement::TYPE_SODA && $dealerId) {
                    $q->where('dealer_id', $dealerId);
                }
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
                $q->whereHas('order', fn ($oq) => $oq->where('unique_order_id', 'like', $like)->orWhere('party_name', 'like', $like))
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

                $orderDate = $item->order?->order_date?->format('d-m-Y') ?? '—';
                $party = $item->order?->partyLabel() ?? '—';

                return [
                    'order_item_id'  => $item->id,
                    'order_id'       => $item->order_id,
                    'unique_order_id'=> $item->order?->unique_order_id,
                    'order_type'     => $item->order?->order_type ?? OrderManagement::TYPE_SODA,
                    'payment_type'   => $item->order?->payment_type ?? 'cash',
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product?->name ?? '—',
                    'product_unit'   => $item->product?->unit,
                    'unit_price'     => (string) $item->unit_price,
                    'pending_qty'    => $available,
                    'dealer_name'    => $party,
                    'party_name'     => $party,
                    'city_name'      => $dealer?->city?->city_name ?? '—',
                    'label'          => ($item->order?->orderTypeLabel() ?? 'Soda')
                        . ' · ' . ($item->order?->unique_order_id ?? '')
                        . ' - ' . $party
                        . ' - ' . $orderDate
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
