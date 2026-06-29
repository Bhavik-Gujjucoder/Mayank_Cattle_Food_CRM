<?php

namespace App\Services;

use App\Models\DispatchLateFeeLog;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Support\DispatchEmailDelivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentReceivableService
{
    public function paymentDueDays(): int
    {
        return max(0, (int) getSetting('payment_due_days'));
    }

    public function paymentDueAmountRate(): float
    {
        return max(0, (float) getSetting('payment_due_amount'));
    }

    public function isLateFeeEnabled(): bool
    {
        return $this->paymentDueDays() > 0 && $this->paymentDueAmountRate() > 0;
    }

    /**
     * First calendar date on which a daily late fee may be posted.
     */
    public function firstChargeDate(DispatchManagement $dispatch): ?Carbon
    {
        if ($dispatch->dispatch_date === null) {
            return null;
        }

        return $dispatch->dispatch_date
            ->copy()
            ->startOfDay()
            ->addDays($this->paymentDueDays() + 1);
    }

    public function daysSinceDispatch(DispatchManagement $dispatch, ?Carbon $asOf = null): int
    {
        if ($dispatch->dispatch_date === null) {
            return 0;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $dispatchDay = $dispatch->dispatch_date->copy()->startOfDay();

        return max(0, (int) $dispatchDay->diffInDays($asOf));
    }

    public function overdueDays(DispatchManagement $dispatch, ?Carbon $asOf = null): int
    {
        return max(0, $this->daysSinceDispatch($dispatch, $asOf) - $this->paymentDueDays());
    }

    public function isPastGracePeriod(DispatchManagement $dispatch, ?Carbon $asOf = null): bool
    {
        return $this->overdueDays($dispatch, $asOf) > 0;
    }

    public function dailyChargeAmount(DispatchManagement $dispatch): float
    {
        if (! $this->isLateFeeEnabled()) {
            return 0.0;
        }

        $qty = max(0, (int) $dispatch->no_of_bags);

        return round($this->paymentDueAmountRate() * $qty, 2);
    }

    public function baseAmount(DispatchManagement $dispatch): float
    {
        $dispatch->loadMissing('orderItem:id,unit_price');

        $unitPrice = (float) ($dispatch->orderItem?->unit_price ?? 0);
        $qty = max(0, (int) $dispatch->no_of_bags);

        return round($unitPrice * $qty, 2);
    }

    public function accruedLateFee(DispatchManagement $dispatch): float
    {
        return round((float) ($dispatch->accrued_late_fee ?? 0), 2);
    }

    public function totalReceivable(DispatchManagement $dispatch): float
    {
        return round($this->baseAmount($dispatch) + $this->accruedLateFee($dispatch), 2);
    }

    public function amountPaid(DispatchManagement $dispatch): float
    {
        return match ((int) $dispatch->status) {
            DispatchManagement::STATUS_PAID    => $this->totalReceivable($dispatch),
            DispatchManagement::STATUS_PARTIAL => (float) ($dispatch->partial_paid_amount ?? 0),
            default                            => 0.0,
        };
    }

    public function balanceDue(DispatchManagement $dispatch): float
    {
        if ((int) $dispatch->status === DispatchManagement::STATUS_PAID) {
            return 0.0;
        }

        return max(0, round($this->totalReceivable($dispatch) - $this->amountPaid($dispatch), 2));
    }

    public function formatBalanceDueDisplay(DispatchManagement $dispatch): string
    {
        if ((int) $dispatch->status === DispatchManagement::STATUS_PAID) {
            return '—';
        }

        $balance = $this->balanceDue($dispatch);

        return $balance > 0 ? self::formatMoney($balance) : '—';
    }

    /**
     * @return array{
     *     base_amount: float,
     *     accrued_late_fee: float,
     *     total_receivable: float,
     *     balance_due: float,
     *     overdue_days: int,
     *     days_since_dispatch: int
     * }
     */
    public function summarizeDispatch(DispatchManagement $dispatch, ?Carbon $asOf = null): array
    {
        return [
            'base_amount'           => $this->baseAmount($dispatch),
            'accrued_late_fee'      => $this->accruedLateFee($dispatch),
            'total_receivable'      => $this->totalReceivable($dispatch),
            'amount_paid'           => $this->amountPaid($dispatch),
            'balance_due'           => $this->balanceDue($dispatch),
            'overdue_days'          => $this->overdueDays($dispatch, $asOf),
            'days_since_dispatch'   => $this->daysSinceDispatch($dispatch, $asOf),
        ];
    }

    /**
     * @return 'low'|'mid'|'high'
     */
    public function dayAgingLevel(int $daysSinceDispatch): string
    {
        $dueDays = $this->paymentDueDays();

        if ($daysSinceDispatch <= $dueDays) {
            return 'low';
        }

        if ($daysSinceDispatch <= $dueDays + 7) {
            return 'mid';
        }

        return 'high';
    }

    /**
     * @return array{total_late_fee: float, total_balance_due: float, has_pending: bool}
     */
    public function summarizeOrderPendingDispatches(iterable $dispatches): array
    {
        $totalLateFee = 0.0;
        $totalBalance = 0.0;
        $hasPending = false;

        foreach ($dispatches as $dispatch) {
            if (! in_array((int) $dispatch->status, DispatchManagement::pendingPaymentStatuses(), true)) {
                continue;
            }

            $hasPending = true;
            $summary = $this->summarizeDispatch($dispatch);
            $totalLateFee += $summary['accrued_late_fee'];
            $totalBalance += $summary['balance_due'];
        }

        return [
            'total_late_fee'    => round($totalLateFee, 2),
            'total_balance_due' => round($totalBalance, 2),
            'has_pending'       => $hasPending,
        ];
    }

    public function formatReceivableCell(float $lateFee, float $balanceDue): string
    {
        if ($lateFee <= 0 && $balanceDue <= 0) {
            return '<span class="text-muted">—</span>';
        }

        $html = '<div class="dispatch-receivable-cell text-end text-nowrap">';
        if ($lateFee > 0) {
            $html .= '<div class="small text-warning">Late: ' . e(self::formatMoney($lateFee)) . '</div>';
        }
        if ($balanceDue > 0) {
            $html .= '<div class="fw-medium">Due: ' . e(self::formatMoney($balanceDue)) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    public static function formatMoney(float $amount): string
    {
        return '₹ ' . number_format($amount, 2);
    }

    public function isAccrualEligible(DispatchManagement $dispatch, Carbon $asOf): bool
    {
        if (! in_array((int) $dispatch->status, DispatchManagement::pendingPaymentStatuses(), true)) {
            return false;
        }

        if ($dispatch->dispatch_date === null) {
            return false;
        }

        if (! $this->isLateFeeEnabled()) {
            return false;
        }

        $firstCharge = $this->firstChargeDate($dispatch);
        if ($firstCharge === null || $asOf->lt($firstCharge)) {
            return false;
        }

        if ($dispatch->late_fee_last_accrued_on !== null) {
            $lastAccrued = Carbon::parse($dispatch->late_fee_last_accrued_on)->startOfDay();
            if ($lastAccrued->gte($asOf)) {
                return false;
            }
        }

        return $this->dailyChargeAmount($dispatch) > 0;
    }

    /**
     * @return array{days_accrued: int, amount_added: float}
     */
    public function accrueDispatch(DispatchManagement $dispatch, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        if (! $this->isAccrualEligible($dispatch, $asOf)) {
            return ['days_accrued' => 0, 'amount_added' => 0.0];
        }

        $dailyAmount = $this->dailyChargeAmount($dispatch);
        $firstCharge = $this->firstChargeDate($dispatch);
        if ($firstCharge === null) {
            return ['days_accrued' => 0, 'amount_added' => 0.0];
        }

        $lastAccrued = $dispatch->late_fee_last_accrued_on !== null
            ? Carbon::parse($dispatch->late_fee_last_accrued_on)->startOfDay()
            : null;

        $start = $lastAccrued ? $lastAccrued->copy()->addDay() : $firstCharge->copy();

        if ($start->gt($asOf)) {
            return ['days_accrued' => 0, 'amount_added' => 0.0];
        }

        $daysAccrued = 0;
        $amountAdded = 0.0;
        $rate = $this->paymentDueAmountRate();
        $qty = max(0, (int) $dispatch->no_of_bags);

        DB::transaction(function () use ($dispatch, $asOf, $start, $dailyAmount, $rate, $qty, &$daysAccrued, &$amountAdded) {
            $existingDates = DispatchLateFeeLog::query()
                ->where('dispatch_management_id', $dispatch->id)
                ->whereDate('charge_date', '>=', $start->toDateString())
                ->whereDate('charge_date', '<=', $asOf->toDateString())
                ->pluck('charge_date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->flip();

            $rows = [];
            $now = now();
            $current = $start->copy();

            while ($current->lte($asOf)) {
                $dateStr = $current->toDateString();

                if (! isset($existingDates[$dateStr])) {
                    $rows[] = [
                        'dispatch_management_id' => $dispatch->id,
                        'charge_date'            => $dateStr,
                        'daily_amount'           => $dailyAmount,
                        'rate_per_unit'          => $rate,
                        'quantity'               => $qty,
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ];

                    $daysAccrued++;
                    $amountAdded += $dailyAmount;
                }

                $current->addDay();
            }

            if ($rows !== []) {
                foreach (array_chunk($rows, 100) as $chunk) {
                    DispatchLateFeeLog::insert($chunk);
                }

                $dispatch->update([
                    'accrued_late_fee'         => round($this->accruedLateFee($dispatch) + $amountAdded, 2),
                    'late_fee_last_accrued_on' => $asOf->toDateString(),
                ]);
            }
        });

        $result = [
            'days_accrued'  => $daysAccrued,
            'amount_added'  => round($amountAdded, 2),
        ];

        if ($result['days_accrued'] > 0) {
            $dispatch->refresh();
            DispatchEmailDelivery::queuePaymentPendingReminder($dispatch, $result['amount_added']);
        }

        return $result;
    }

    /**
     * @return array{processed: int, accrued: int, amount: float}
     */
    public function accrueAll(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $stats = [
            'processed' => 0,
            'accrued'   => 0,
            'amount'    => 0.0,
        ];

        DispatchManagement::query()
            ->whereIn('status', DispatchManagement::pendingPaymentStatuses())
            ->whereNotNull('dispatch_date')
            ->whereHas('order', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('id')
            ->chunkById(100, function ($dispatches) use ($asOf, &$stats) {
                foreach ($dispatches as $dispatch) {
                    $result = $this->accrueDispatch($dispatch, $asOf);
                    $stats['processed']++;

                    if ($result['days_accrued'] > 0) {
                        $stats['accrued']++;
                        $stats['amount'] += $result['amount_added'];
                    }
                }
            });

        $stats['amount'] = round($stats['amount'], 2);

        return $stats;
    }

    /**
     * Keep parent order payment status aligned with dispatch payment rows.
     */
    public function syncOrderPaymentStatus(OrderManagement $order): void
    {
        $order->loadMissing(['items.dispatches']);

        $dispatches = $order->dispatches()->get();

        if ($dispatches->isEmpty()) {
            return;
        }

        $allPaid = $dispatches->every(
            fn (DispatchManagement $dispatch) => (int) $dispatch->status === DispatchManagement::STATUS_PAID
        );
        $allUnpaid = $dispatches->every(
            fn (DispatchManagement $dispatch) => (int) $dispatch->status === DispatchManagement::STATUS_UNPAID
        );

        if ($allPaid && $order->isFullyDispatched()) {
            $newStatus = 'paid';
            $newPartial = null;
        } elseif ($allUnpaid) {
            $newStatus = 'unpaid';
            $newPartial = null;
        } else {
            $newStatus = 'partial';
            $newPartial = round($dispatches->sum(fn (DispatchManagement $d) => $this->amountPaid($d)), 2);
        }

        $currentPartial = $order->partial_paid_amount !== null
            ? round((float) $order->partial_paid_amount, 2)
            : null;

        if ($order->payment_status === $newStatus
            && ($currentPartial === $newPartial
                || ($currentPartial !== null && $newPartial !== null && abs($currentPartial - $newPartial) < 0.005))) {
            return;
        }

        $order->update([
            'payment_status'      => $newStatus,
            'partial_paid_amount' => $newPartial,
        ]);
    }
}
