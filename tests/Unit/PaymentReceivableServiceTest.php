<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchLateFeeLog;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Services\PaymentReceivableService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function prsUpdateSettings(int $dueDays = 0, float $dueAmount = 0): void
{
    DB::table('general_settings')
        ->where('key', 'payment_due_days')
        ->update(['value' => (string) $dueDays]);
    DB::table('general_settings')
        ->where('key', 'payment_due_amount')
        ->update(['value' => (string) $dueAmount]);
}

function prsMakeDispatch(array $attrs = []): DispatchManagement
{
    $brand = BrandManagement::create(['name' => 'Brand-' . uniqid(), 'status' => 1]);

    $product = Product::create([
        'name'     => 'Prod-' . uniqid(),
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => 100.00,
        'status'   => 1,
    ]);

    $broker = User::factory()->create(['status' => 1]);
    $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

    $dealerUser = User::factory()->create(['status' => 1]);
    $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'Firm-' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD/' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ]);

    $orderItem = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => 10,
        'unit_price'  => 100.00,
        'total_price' => 1000.00,
    ]);

    $transporter = User::factory()->create(['status' => 1]);
    $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

    return DispatchManagement::create(array_merge([
        'order_id'         => $order->id,
        'order_item_id'    => $orderItem->id,
        'product_id'       => $product->id,
        'no_of_bags'       => 5,
        'dispatch_date'    => '2026-01-15',
        'transport_id'     => $transporter->id,
        'truck_number'     => 'GJ01AA1234',
        'driver_contact'   => '9876543210',
        'status'           => DispatchManagement::STATUS_UNPAID,
        'accrued_late_fee' => 0,
    ], $attrs));
}

// ─────────────────────────────────────────────

beforeEach(function () {
    foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
    DB::table('general_settings')->insert([
        ['key' => 'payment_due_days',   'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'payment_due_amount', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// ─────────────────────────────────────────────

describe('settings', function () {
    it('reads payment_due_days from general settings', function () {
        prsUpdateSettings(dueDays: 7, dueAmount: 10);
        expect((new PaymentReceivableService())->paymentDueDays())->toBe(7);
    });

    it('reads payment_due_amount from general settings', function () {
        prsUpdateSettings(dueDays: 7, dueAmount: 25.5);
        expect((new PaymentReceivableService())->paymentDueAmountRate())->toBe(25.5);
    });

    it('isLateFeeEnabled returns true when both settings are positive', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        expect((new PaymentReceivableService())->isLateFeeEnabled())->toBeTrue();
    });

    it('isLateFeeEnabled returns false when due days is zero', function () {
        prsUpdateSettings(dueDays: 0, dueAmount: 10);
        expect((new PaymentReceivableService())->isLateFeeEnabled())->toBeFalse();
    });

    it('isLateFeeEnabled returns false when due amount is zero', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 0);
        expect((new PaymentReceivableService())->isLateFeeEnabled())->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('date-calculations', function () {
    it('firstChargeDate returns dispatch_date plus due_days plus one day', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-10']);
        $service  = new PaymentReceivableService();

        $expected = Carbon::parse('2026-01-14')->startOfDay(); // +3 days grace +1 first charge
        expect($service->firstChargeDate($dispatch)?->toDateString())->toBe($expected->toDateString());
    });

    it('firstChargeDate returns null when dispatch_date is null', function () {
        // Use make() — not persisted, so NOT NULL DB constraint is not triggered
        $dispatch = DispatchManagement::make(['dispatch_date' => null]);
        expect((new PaymentReceivableService())->firstChargeDate($dispatch))->toBeNull();
    });

    it('daysSinceDispatch returns correct number of days', function () {
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01']);
        $service  = new PaymentReceivableService();
        $asOf     = Carbon::parse('2026-01-11');
        expect($service->daysSinceDispatch($dispatch, $asOf))->toBe(10);
    });

    it('daysSinceDispatch returns 0 when dispatch_date is null', function () {
        $dispatch = DispatchManagement::make(['dispatch_date' => null]);
        expect((new PaymentReceivableService())->daysSinceDispatch($dispatch))->toBe(0);
    });

    it('overdueDays is zero within grace period', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01']);
        $service  = new PaymentReceivableService();
        $asOf     = Carbon::parse('2026-01-04'); // 3 days since, due=5, overdue=0
        expect($service->overdueDays($dispatch, $asOf))->toBe(0);
    });

    it('overdueDays returns positive days past grace period', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01']);
        $service  = new PaymentReceivableService();
        $asOf     = Carbon::parse('2026-01-09'); // 8 days since, due=5, overdue=3
        expect($service->overdueDays($dispatch, $asOf))->toBe(3);
    });

    it('isPastGracePeriod returns false within grace period', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01']);
        $service  = new PaymentReceivableService();
        expect($service->isPastGracePeriod($dispatch, Carbon::parse('2026-01-04')))->toBeFalse();
    });

    it('isPastGracePeriod returns true past grace period', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01']);
        $service  = new PaymentReceivableService();
        expect($service->isPastGracePeriod($dispatch, Carbon::parse('2026-01-09')))->toBeTrue();
    });
});

// ─────────────────────────────────────────────

describe('charge-amounts', function () {
    it('dailyChargeAmount returns zero when late fee is disabled', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 10]);
        expect((new PaymentReceivableService())->dailyChargeAmount($dispatch))->toBe(0.0);
    });

    it('dailyChargeAmount returns rate times bags when enabled', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 5);
        $dispatch = prsMakeDispatch(['no_of_bags' => 10]);
        expect((new PaymentReceivableService())->dailyChargeAmount($dispatch))->toBe(50.0);
    });

    it('baseAmount is unit_price times no_of_bags', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5]);
        $service  = new PaymentReceivableService();
        // unit_price=100, bags=5 → 500
        expect($service->baseAmount($dispatch))->toBe(500.0);
    });

    it('accruedLateFee returns value from dispatch model', function () {
        $dispatch = prsMakeDispatch(['accrued_late_fee' => 250.0]);
        expect((new PaymentReceivableService())->accruedLateFee($dispatch))->toBe(250.0);
    });

    it('totalReceivable is base amount plus accrued late fee', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 50.0]);
        $service  = new PaymentReceivableService();
        // base=500, late_fee=50 → 550
        expect($service->totalReceivable($dispatch))->toBe(550.0);
    });
});

// ─────────────────────────────────────────────

describe('payment-status', function () {
    it('amountPaid returns totalReceivable when status is paid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_PAID]);
        expect((new PaymentReceivableService())->amountPaid($dispatch))->toBe(500.0);
    });

    it('amountPaid returns partial_paid_amount when status is partial', function () {
        $dispatch = prsMakeDispatch(['status' => DispatchManagement::STATUS_PARTIAL, 'partial_paid_amount' => 200.0]);
        expect((new PaymentReceivableService())->amountPaid($dispatch))->toBe(200.0);
    });

    it('amountPaid returns zero when status is unpaid', function () {
        $dispatch = prsMakeDispatch(['status' => DispatchManagement::STATUS_UNPAID]);
        expect((new PaymentReceivableService())->amountPaid($dispatch))->toBe(0.0);
    });

    it('balanceDue returns zero when status is paid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_PAID]);
        expect((new PaymentReceivableService())->balanceDue($dispatch))->toBe(0.0);
    });

    it('balanceDue returns full totalReceivable when unpaid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_UNPAID]);
        expect((new PaymentReceivableService())->balanceDue($dispatch))->toBe(500.0);
    });

    it('balanceDue subtracts partial payment amount', function () {
        $dispatch = prsMakeDispatch([
            'no_of_bags'          => 5,
            'accrued_late_fee'    => 0,
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 200.0,
        ]);
        expect((new PaymentReceivableService())->balanceDue($dispatch))->toBe(300.0);
    });

    it('formatBalanceDueDisplay returns dash when status is paid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_PAID]);
        expect((new PaymentReceivableService())->formatBalanceDueDisplay($dispatch))->toBe('—');
    });

    it('formatBalanceDueDisplay returns formatted amount when balance is due', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_UNPAID]);
        $display  = (new PaymentReceivableService())->formatBalanceDueDisplay($dispatch);
        expect($display)->toContain('500.00');
    });
});

// ─────────────────────────────────────────────

describe('aging-level', function () {
    it('returns low when within due days', function () {
        prsUpdateSettings(dueDays: 10, dueAmount: 5);
        expect((new PaymentReceivableService())->dayAgingLevel(5))->toBe('low');
    });

    it('returns mid when 1 to 7 days past due', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 5);
        expect((new PaymentReceivableService())->dayAgingLevel(8))->toBe('mid'); // 8-5=3 days over
    });

    it('returns high when more than 7 days past due', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 5);
        expect((new PaymentReceivableService())->dayAgingLevel(14))->toBe('high'); // 14-5=9 days over
    });

    it('returns low when exactly on the due day', function () {
        prsUpdateSettings(dueDays: 7, dueAmount: 5);
        expect((new PaymentReceivableService())->dayAgingLevel(7))->toBe('low');
    });

    it('returns mid when exactly 7 days past due', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 5);
        expect((new PaymentReceivableService())->dayAgingLevel(12))->toBe('mid'); // exactly 7 days over
    });
});

// ─────────────────────────────────────────────

describe('accrual-eligibility', function () {
    it('returns false when late fee is disabled', function () {
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);
        $asOf     = Carbon::parse('2026-01-15');
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeFalse();
    });

    it('returns false when dispatch is paid', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_PAID]);
        $asOf     = Carbon::parse('2026-01-15');
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeFalse();
    });

    it('returns false when no dispatch_date', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $dispatch = DispatchManagement::make([
            'dispatch_date' => null,
            'status'        => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'    => 5,
        ]);
        $asOf = Carbon::parse('2026-01-15');
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeFalse();
    });

    it('returns false before first charge date', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch([
            'dispatch_date' => '2026-01-01',
            'status'        => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'    => 5,
        ]);
        $asOf = Carbon::parse('2026-01-03'); // only 2 days since, first charge is day 6
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeFalse();
    });

    it('returns true when all conditions are met', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $dispatch = prsMakeDispatch([
            'dispatch_date' => '2026-01-01',
            'status'        => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'    => 5,
        ]);
        $asOf = Carbon::parse('2026-01-10'); // well past first charge date (day 4)
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeTrue();
    });

    it('returns false when already accrued today', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $asOf     = Carbon::parse('2026-01-10');
        $dispatch = prsMakeDispatch([
            'dispatch_date'            => '2026-01-01',
            'status'                   => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'               => 5,
            'late_fee_last_accrued_on' => '2026-01-10',
        ]);
        expect((new PaymentReceivableService())->isAccrualEligible($dispatch, $asOf))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('accrue-dispatch', function () {
    it('accrues fees, creates log entries, and updates dispatch', function () {
        Mail::fake();
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $dispatch = prsMakeDispatch([
            'dispatch_date' => '2026-01-01',
            'status'        => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'    => 5,
        ]);
        $service = new PaymentReceivableService();
        $asOf    = Carbon::parse('2026-01-07'); // first charge = Jan 5, charge 3 days (5,6,7)

        $result = $service->accrueDispatch($dispatch, $asOf);

        expect($result['days_accrued'])->toBe(3);
        expect($result['amount_added'])->toBe(150.0); // 3 days × 5 bags × ₹10 = ₹150

        $dispatch->refresh();
        expect((float) $dispatch->accrued_late_fee)->toBe(150.0);
        expect($dispatch->late_fee_last_accrued_on->toDateString())->toBe('2026-01-07');
        expect(DispatchLateFeeLog::where('dispatch_management_id', $dispatch->id)->count())->toBe(3);
        Mail::assertQueued(\App\Mail\DispatchPaymentPendingReminderMail::class);
    });

    it('returns zero when dispatch is not eligible', function () {
        $dispatch = prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);
        $service  = new PaymentReceivableService();
        $asOf     = Carbon::parse('2026-01-07');

        $result = $service->accrueDispatch($dispatch, $asOf);

        expect($result['days_accrued'])->toBe(0);
        expect($result['amount_added'])->toBe(0.0);
    });

    it('is idempotent when re-accrued for the same date', function () {
        Mail::fake();
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        $asOf     = Carbon::parse('2026-01-07');
        $dispatch = prsMakeDispatch([
            'dispatch_date'            => '2026-01-01',
            'status'                   => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'               => 5,
            'late_fee_last_accrued_on' => '2026-01-07',
            'accrued_late_fee'         => 150.0,
        ]);
        $service = new PaymentReceivableService();
        $result  = $service->accrueDispatch($dispatch, $asOf);

        expect($result['days_accrued'])->toBe(0);
        expect($result['amount_added'])->toBe(0.0);
    });

    it('accrueAll processes all eligible dispatches', function () {
        Mail::fake();
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID, 'no_of_bags' => 5]);
        prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID, 'no_of_bags' => 3]);
        $service = new PaymentReceivableService();
        $asOf    = Carbon::parse('2026-01-07'); // 3 chargeable days each

        $stats = $service->accrueAll($asOf);

        expect($stats['processed'])->toBe(2);
        expect($stats['accrued'])->toBe(2);
        // dispatch1: 5 bags × 10 × 3 days = 150, dispatch2: 3 bags × 10 × 3 days = 90 → total 240
        expect($stats['amount'])->toBe(240.0);
    });

    it('accrueAll skips paid dispatches', function () {
        prsUpdateSettings(dueDays: 3, dueAmount: 10);
        prsMakeDispatch(['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_PAID, 'no_of_bags' => 5]);
        $service = new PaymentReceivableService();

        $stats = $service->accrueAll(Carbon::parse('2026-01-07'));

        expect($stats['processed'])->toBe(0);
        expect($stats['accrued'])->toBe(0);
    });
});

// ─────────────────────────────────────────────

describe('summarize-order-pending-dispatches', function () {
    it('returns zero totals and has_pending false when no dispatches', function () {
        $service = new PaymentReceivableService();
        $result  = $service->summarizeOrderPendingDispatches([]);

        expect($result['total_late_fee'])->toBe(0.0)
            ->and($result['total_balance_due'])->toBe(0.0)
            ->and($result['has_pending'])->toBeFalse();
    });

    it('returns zero totals when all dispatches are paid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 0, 'status' => DispatchManagement::STATUS_PAID]);
        $service  = new PaymentReceivableService();

        $result = $service->summarizeOrderPendingDispatches([$dispatch]);

        expect($result['total_late_fee'])->toBe(0.0)
            ->and($result['total_balance_due'])->toBe(0.0)
            ->and($result['has_pending'])->toBeFalse();
    });

    it('returns correct totals for unpaid dispatch', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 50.0, 'status' => DispatchManagement::STATUS_UNPAID]);
        $service  = new PaymentReceivableService();

        $result = $service->summarizeOrderPendingDispatches([$dispatch]);

        // base=500, late_fee=50 → total=550, paid=0, balance=550
        expect($result['total_late_fee'])->toBe(50.0)
            ->and($result['total_balance_due'])->toBe(550.0)
            ->and($result['has_pending'])->toBeTrue();
    });

    it('sums totals across multiple pending dispatches', function () {
        $d1 = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 20.0, 'status' => DispatchManagement::STATUS_UNPAID]);
        $d2 = prsMakeDispatch(['no_of_bags' => 5, 'accrued_late_fee' => 30.0, 'status' => DispatchManagement::STATUS_PARTIAL, 'partial_paid_amount' => 100.0]);
        $service = new PaymentReceivableService();

        $result = $service->summarizeOrderPendingDispatches([$d1, $d2]);

        // d1: base=500, late=20, total=520, paid=0, balance=520
        // d2: base=500, late=30, total=530, paid=100, balance=430
        expect($result['total_late_fee'])->toBe(50.0)
            ->and($result['total_balance_due'])->toBe(950.0)
            ->and($result['has_pending'])->toBeTrue();
    });
});

// ─────────────────────────────────────────────

describe('format-receivable-cell', function () {
    it('returns dash span when both values are zero', function () {
        $html = (new PaymentReceivableService())->formatReceivableCell(0, 0);
        expect($html)->toContain('text-muted')
            ->and($html)->toContain('—');
    });

    it('returns late fee div when late fee is positive', function () {
        $html = (new PaymentReceivableService())->formatReceivableCell(100.0, 0);
        expect($html)->toContain('text-warning')
            ->and($html)->toContain('Late:')
            ->and($html)->toContain('100.00');
    });

    it('returns balance due div when balance due is positive', function () {
        $html = (new PaymentReceivableService())->formatReceivableCell(0, 200.0);
        expect($html)->toContain('fw-medium')
            ->and($html)->toContain('Due:')
            ->and($html)->toContain('200.00');
    });

    it('returns both divs when both values are positive', function () {
        $html = (new PaymentReceivableService())->formatReceivableCell(50.0, 300.0);
        expect($html)->toContain('Late:')
            ->and($html)->toContain('50.00')
            ->and($html)->toContain('Due:')
            ->and($html)->toContain('300.00');
    });
});

// ─────────────────────────────────────────────

describe('sync-order-payment-status', function () {
    it('does nothing when order has no dispatches', function () {
        $dispatch = prsMakeDispatch(['status' => DispatchManagement::STATUS_UNPAID]);
        $order    = $dispatch->order;
        $order->update(['payment_status' => 'unpaid']);

        // Delete the dispatch so order has no dispatches
        $dispatch->forceDelete();

        (new PaymentReceivableService())->syncOrderPaymentStatus($order->fresh());

        $order->refresh();
        expect($order->payment_status)->toBe('unpaid'); // unchanged
    });

    it('sets payment_status to unpaid when all dispatches are unpaid', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 5, 'status' => DispatchManagement::STATUS_UNPAID]);
        $order    = $dispatch->order;
        $order->update(['payment_status' => 'partial']); // set to something else first

        (new PaymentReceivableService())->syncOrderPaymentStatus($order->fresh());

        $order->refresh();
        expect($order->payment_status)->toBe('unpaid');
    });

    it('sets payment_status to partial when dispatch has partial payment', function () {
        $dispatch = prsMakeDispatch([
            'no_of_bags'          => 5,
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 200.0,
        ]);
        $order = $dispatch->order;

        (new PaymentReceivableService())->syncOrderPaymentStatus($order->fresh());

        $order->refresh();
        expect($order->payment_status)->toBe('partial')
            ->and((float) $order->partial_paid_amount)->toBe(200.0);
    });

    it('sets payment_status to paid when all dispatches are paid and order is fully dispatched', function () {
        $dispatch = prsMakeDispatch(['no_of_bags' => 10, 'status' => DispatchManagement::STATUS_PAID]);
        $order    = $dispatch->order;

        (new PaymentReceivableService())->syncOrderPaymentStatus($order->fresh());

        $order->refresh();
        expect($order->payment_status)->toBe('paid');
    });
});

// ─────────────────────────────────────────────

describe('summarize', function () {
    it('summarizeDispatch returns correct structure', function () {
        prsUpdateSettings(dueDays: 5, dueAmount: 10);
        $dispatch = prsMakeDispatch([
            'dispatch_date'    => '2026-01-01',
            'status'           => DispatchManagement::STATUS_UNPAID,
            'no_of_bags'       => 5,
            'accrued_late_fee' => 50.0,
        ]);
        $service = new PaymentReceivableService();
        $asOf    = Carbon::parse('2026-01-11'); // 10 days since, overdue 5

        $summary = $service->summarizeDispatch($dispatch, $asOf);

        expect($summary)->toHaveKeys(['base_amount', 'accrued_late_fee', 'total_receivable', 'amount_paid', 'balance_due', 'overdue_days', 'days_since_dispatch']);
        expect($summary['base_amount'])->toBe(500.0);
        expect($summary['accrued_late_fee'])->toBe(50.0);
        expect($summary['total_receivable'])->toBe(550.0);
        expect($summary['days_since_dispatch'])->toBe(10);
        expect($summary['overdue_days'])->toBe(5);
    });
});
