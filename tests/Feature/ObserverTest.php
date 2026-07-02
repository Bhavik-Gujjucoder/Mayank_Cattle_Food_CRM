<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────
//  Shared helpers
// ─────────────────────────────────────────────

function obsRawSetup(): array
{
    $broker   = SupplierBroker::create(['name' => 'SB-OBS-' . uniqid(), 'status' => 1]);
    $supplier = Supplier::create([
        'supplier_broker_id' => $broker->id,
        'name'               => 'Sup-OBS-' . uniqid(),
        'email'              => uniqid() . '@obs.test',
        'status'             => 1,
    ]);
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'CAT-OBS-' . uniqid(),
        'name'               => 'ObsCat-' . uniqid(),
        'status'             => 1,
    ]);
    $material = RawMaterial::create([
        'raw_material_unique_id'   => 'RM-OBS-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name'                     => 'ObsMat-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ]);
    $order = RawMaterialOrder::create([
        'order_unique_id'    => 'ORD-OBS-' . uniqid(),
        'supplier_broker_id' => $broker->id,
        'supplier_id'        => $supplier->id,
        'order_date'         => now()->toDateString(),
        'price_basis'        => 'FOR + GST',
        'status'             => 0,
    ]);

    return compact('broker', 'supplier', 'category', 'material', 'order');
}

function obsDisSetup(): array
{
    $brand = BrandManagement::create(['name' => 'Brand-OBS-' . uniqid(), 'status' => 1]);
    $broker = User::factory()->create(['status' => 1]);
    $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
    $dealerUser = User::factory()->create(['status' => 1]);
    $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));
    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-OBS-' . uniqid(),
        'firm_shop_name'    => 'Firm-OBS-' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);
    $product = Product::create([
        'name'     => 'Prod-OBS-' . uniqid(),
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => 100,
        'status'   => 1,
    ]);
    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD-OBS-' . uniqid(),
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

    return compact('brand', 'broker', 'dealerUser', 'dealer', 'product', 'order', 'orderItem', 'transporter');
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

describe('DispatchManagementObserver', function () {
    it('queues created email when dispatch is created with dealer email', function () {
        Mail::fake();

        $s = obsDisSetup();
        DispatchManagement::create([
            'order_id'       => $s['order']->id,
            'order_item_id'  => $s['orderItem']->id,
            'product_id'     => $s['product']->id,
            'no_of_bags'     => 5,
            'dispatch_date'  => '2026-01-15',
            'transport_id'   => $s['transporter']->id,
            'truck_number'   => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status'         => DispatchManagement::STATUS_UNPAID,
        ]);

        Mail::assertQueued(\App\Mail\DispatchCreatedMail::class);
    });

    it('queues payment changed email when dispatch status is updated', function () {
        Mail::fake();

        $s = obsDisSetup();
        $dispatch = DispatchManagement::create([
            'order_id'       => $s['order']->id,
            'order_item_id'  => $s['orderItem']->id,
            'product_id'     => $s['product']->id,
            'no_of_bags'     => 5,
            'dispatch_date'  => '2026-01-15',
            'transport_id'   => $s['transporter']->id,
            'truck_number'   => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status'         => DispatchManagement::STATUS_UNPAID,
        ]);

        Mail::fake(); // reset after create so we only count update mails

        // Reload from DB so wasRecentlyCreated is false, otherwise observer skips the update
        $dispatch = DispatchManagement::find($dispatch->id);
        $dispatch->update(['status' => DispatchManagement::STATUS_PAID]);

        Mail::assertQueued(\App\Mail\DispatchPaymentStatusChangedMail::class);
    });

    it('does not queue payment changed email when non-payment fields are updated', function () {
        Mail::fake();

        $s = obsDisSetup();
        $dispatch = DispatchManagement::create([
            'order_id'       => $s['order']->id,
            'order_item_id'  => $s['orderItem']->id,
            'product_id'     => $s['product']->id,
            'no_of_bags'     => 5,
            'dispatch_date'  => '2026-01-15',
            'transport_id'   => $s['transporter']->id,
            'truck_number'   => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status'         => DispatchManagement::STATUS_UNPAID,
        ]);

        Mail::fake();

        $dispatch->update(['truck_number' => 'GJ01ZZ9999']);

        Mail::assertNotQueued(\App\Mail\DispatchPaymentStatusChangedMail::class);
    });
});

// ─────────────────────────────────────────────

describe('RawMaterialReceiveObserver', function () {
    it('applies receive and updates order item when status changes from on-road to received', function () {
        $s    = obsRawSetup();
        $item = RawMaterialOrderItem::create([
            'raw_material_order_id' => $s['order']->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        // Create receive as on-road (status=0)
        $receive = RawMaterialReceive::create([
            'raw_material_order_id'      => $s['order']->id,
            'raw_material_order_item_id' => $item->id,
            'raw_material_id'            => $s['material']->id,
            'qty'                        => 5,
            'freight'                    => 100,
            'status'                     => 0,
            'received_date'              => now()->toDateString(),
        ]);

        $item->refresh();
        $receivedBefore = (int) $item->received_qty;

        // Approve the receive (status 0→1) — observer should call applyReceive
        $receive->update(['status' => 1]);

        $item->refresh();
        expect((int) $item->received_qty)->toBe($receivedBefore + 5);
        expect((float) $item->total_freight)->toBeGreaterThan(0);
    });

    it('reverses receive when status changes from received back to on-road', function () {
        $s    = obsRawSetup();
        $item = RawMaterialOrderItem::create([
            'raw_material_order_id' => $s['order']->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        // Start with approved receive
        $receive = RawMaterialReceive::create([
            'raw_material_order_id'      => $s['order']->id,
            'raw_material_order_item_id' => $item->id,
            'raw_material_id'            => $s['material']->id,
            'qty'                        => 5,
            'freight'                    => 100,
            'status'                     => 1, // already received, observer applies it
            'received_date'              => now()->toDateString(),
        ]);

        $item->refresh();
        $receivedAfterApply = (int) $item->received_qty;

        // Revert to on-road (status 1→0) — observer should call reverseReceive
        $receive->update(['status' => 0]);

        $item->refresh();
        expect((int) $item->received_qty)->toBe($receivedAfterApply - 5);
    });
});

// ─────────────────────────────────────────────

describe('RawMaterialOrderItemObserver', function () {
    it('initializes item fields when creating', function () {
        $s    = obsRawSetup();
        $item = RawMaterialOrderItem::create([
            'raw_material_order_id' => $s['order']->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 2,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        // initializeOrderItem sets these values
        expect((int) $item->received_qty)->toBe(0);
        expect((float) $item->total_freight)->toBe(0.0);
        expect((float) $item->price_avg)->toBe(0.0);
        // total_price = total_qty * 1000 * price = 10 * 1000 * 500 = 5,000,000
        expect((float) $item->total_price)->toBe(5_000_000.0);
    });

    it('recalculates order totals after item is created', function () {
        $s     = obsRawSetup();
        $order = $s['order'];

        RawMaterialOrderItem::create([
            'raw_material_order_id' => $order->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        $order->refresh();
        expect((int) $order->total_qty)->toBe(10);
    });

    it('recalculates order totals after item is updated', function () {
        $s    = obsRawSetup();
        $item = RawMaterialOrderItem::create([
            'raw_material_order_id' => $s['order']->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        $item->update(['total_qty' => 20, 'pending_qty' => 20]);

        $s['order']->refresh();
        expect((int) $s['order']->total_qty)->toBe(20);
    });

    it('recalculates order totals after item is deleted', function () {
        $s     = obsRawSetup();
        $order = $s['order'];

        $category2 = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-OBS2-' . uniqid(),
            'name'               => 'ObsCat2-' . uniqid(),
            'status'             => 1,
        ]);
        $material2 = RawMaterial::create([
            'raw_material_unique_id'   => 'RM-OBS2-' . uniqid(),
            'raw_material_category_id' => $category2->id,
            'name'                     => 'ObsMat2-' . uniqid(),
            'unit'                     => 'Ton',
            'status'                   => 1,
        ]);

        $item1 = RawMaterialOrderItem::create([
            'raw_material_order_id' => $order->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);
        RawMaterialOrderItem::create([
            'raw_material_order_id' => $order->id,
            'raw_material_id'       => $material2->id,
            'total_qty'             => 5,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 5,
            'status'                => 0,
        ]);

        $order->refresh();
        $totalBefore = (int) $order->total_qty; // 15

        $item1->delete();

        $order->refresh();
        expect((int) $order->total_qty)->toBe($totalBefore - 10); // 5
    });

    it('updates material last_purchase_price after item is created', function () {
        $s    = obsRawSetup();
        $item = RawMaterialOrderItem::create([
            'raw_material_order_id' => $s['order']->id,
            'raw_material_id'       => $s['material']->id,
            'total_qty'             => 10,
            'price'                 => 750,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'status'                => 0,
        ]);

        $s['material']->refresh();
        expect((float) $s['material']->last_purchase_price)->toBe(750.0);
    });
});
