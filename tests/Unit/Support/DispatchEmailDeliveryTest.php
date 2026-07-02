<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Support\DispatchEmailDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function dedSetup(): array
{
    $brand = BrandManagement::create(['name' => 'Brand-DED-' . uniqid(), 'status' => 1]);

    $broker = User::factory()->create(['status' => 1]);
    $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

    $dealerUser = User::factory()->create(['status' => 1, 'email' => uniqid() . '@dealer.test']);
    $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-DED-' . uniqid(),
        'firm_shop_name'    => 'Firm-DED-' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);

    $product = Product::create([
        'name'     => 'Prod-DED-' . uniqid(),
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => 100,
        'status'   => 1,
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD-DED-' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000,
        'grand_total'        => 1000,
        'status'             => 1,
    ]);

    $orderItem = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => 10,
        'unit_price'  => 100,
        'total_price' => 1000,
    ]);

    $transporter = User::factory()->create(['status' => 1]);
    $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

    $dispatch = DispatchManagement::create([
        'order_id'       => $order->id,
        'order_item_id'  => $orderItem->id,
        'product_id'     => $product->id,
        'no_of_bags'     => 5,
        'dispatch_date'  => '2026-01-15',
        'transport_id'   => $transporter->id,
        'truck_number'   => 'GJ01DED1234',
        'driver_contact' => '9876543210',
        'status'         => DispatchManagement::STATUS_UNPAID,
    ]);

    return compact('brand', 'broker', 'dealerUser', 'dealer', 'product', 'order', 'orderItem', 'transporter', 'dispatch');
}

// ─────────────────────────────────────────────

beforeEach(function () {
    Mail::fake();
    foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
    DB::table('general_settings')->insert([
        ['key' => 'payment_due_days',   'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'payment_due_amount', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'company_email',      'value' => '', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// ─────────────────────────────────────────────

describe('queueCreated', function () {
    it('queues DispatchCreatedMail when dealer has an email address', function () {
        $s = dedSetup();

        // Reset fake after setup (observer may have already queued something)
        Mail::fake();

        DispatchEmailDelivery::queueCreated($s['dispatch']);

        Mail::assertQueued(\App\Mail\DispatchCreatedMail::class);
    });

    it('does not queue mail when dealer user has no email', function () {
        $s = dedSetup();

        // Clear the dealer user's email
        $s['dealerUser']->update(['email' => '']);

        // Reload dispatch so loadMissing() re-fetches the updated user email
        $dispatch = \App\Models\DispatchManagement::find($s['dispatch']->id);

        Mail::fake();

        DispatchEmailDelivery::queueCreated($dispatch);

        Mail::assertNotQueued(\App\Mail\DispatchCreatedMail::class);
    });
});

// ─────────────────────────────────────────────

describe('queuePaymentChanged', function () {
    it('queues DispatchPaymentStatusChangedMail when dealer has email', function () {
        $s = dedSetup();

        Mail::fake();

        DispatchEmailDelivery::queuePaymentChanged($s['dispatch']);

        Mail::assertQueued(\App\Mail\DispatchPaymentStatusChangedMail::class);
    });

    it('does not queue mail when dealer user has no email', function () {
        $s = dedSetup();
        $s['dealerUser']->update(['email' => '']);

        // Reload dispatch so loadMissing() re-fetches the updated user email
        $dispatch = \App\Models\DispatchManagement::find($s['dispatch']->id);

        Mail::fake();

        DispatchEmailDelivery::queuePaymentChanged($dispatch);

        Mail::assertNotQueued(\App\Mail\DispatchPaymentStatusChangedMail::class);
    });
});

// ─────────────────────────────────────────────

describe('queuePaymentPendingReminder', function () {
    it('queues DispatchPaymentPendingReminderMail when dealer has email', function () {
        $s = dedSetup();

        Mail::fake();

        DispatchEmailDelivery::queuePaymentPendingReminder($s['dispatch'], 50.0);

        Mail::assertQueued(\App\Mail\DispatchPaymentPendingReminderMail::class);
    });

    it('does not queue reminder mail when dealer email is empty', function () {
        $s = dedSetup();
        $s['dealerUser']->update(['email' => '']);

        // Reload dispatch so loadMissing() re-fetches the updated user email
        $dispatch = \App\Models\DispatchManagement::find($s['dispatch']->id);

        Mail::fake();

        DispatchEmailDelivery::queuePaymentPendingReminder($dispatch, 50.0);

        Mail::assertNotQueued(\App\Mail\DispatchPaymentPendingReminderMail::class);
    });

    it('queues reminder to company email when dealer has no email but company_email is set', function () {
        $s = dedSetup();
        $s['dealerUser']->update(['email' => '']);
        DB::table('general_settings')->where('key', 'company_email')->update(['value' => 'admin@company.test']);

        Mail::fake();

        DispatchEmailDelivery::queuePaymentPendingReminder($s['dispatch'], 50.0);

        Mail::assertQueued(\App\Mail\DispatchPaymentPendingReminderMail::class);
    });
});
