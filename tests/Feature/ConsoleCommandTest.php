<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────

describe('raw-material:recalculate-freight', function () {
    it('outputs success with zero items when no order items exist', function () {
        $this->artisan('raw-material:recalculate-freight')
            ->expectsOutputToContain('Recalculated freight for 0 order item(s)')
            ->assertExitCode(0);
    });

    it('reports count of recalculated order items', function () {
        $broker   = SupplierBroker::create(['name' => 'SB-CMD-' . uniqid(), 'status' => 1]);
        $supplier = Supplier::create([
            'supplier_broker_id' => $broker->id,
            'name'               => 'Sup-CMD-' . uniqid(),
            'email'              => uniqid() . '@cmd.test',
            'status'             => 1,
        ]);
        $category = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-CMD-' . uniqid(),
            'name'               => 'CmdCat-' . uniqid(),
            'status'             => 1,
        ]);
        $material = RawMaterial::create([
            'raw_material_unique_id'   => 'RM-CMD-' . uniqid(),
            'raw_material_category_id' => $category->id,
            'name'                     => 'CmdMat-' . uniqid(),
            'unit'                     => 'Ton',
            'status'                   => 1,
        ]);
        $order = RawMaterialOrder::create([
            'order_unique_id'    => 'ORD-CMD-' . uniqid(),
            'supplier_broker_id' => $broker->id,
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'price_basis'        => 'FOR + GST',
            'status'             => 0,
        ]);

        // Observer fires on create — skip it with DB facade for item to have a known state
        $itemId = DB::table('raw_material_order_items')->insertGetId([
            'raw_material_order_id' => $order->id,
            'raw_material_id'       => $material->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 5,
            'received_qty'          => 5,
            'pending_price'         => 2500000,
            'received_price'        => 2500000,
            'total_price'           => 5000000,
            'total_freight'         => 999, // intentionally wrong
            'price_avg'             => 0,
            'status'                => 1,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        // Create receive with status=1 (freight=100, qty=5 → correct freight = 500)
        DB::table('raw_material_receives')->insert([
            'raw_material_order_id'      => $order->id,
            'raw_material_order_item_id' => $itemId,
            'raw_material_id'            => $material->id,
            'qty'                        => 5,
            'freight'                    => 100,
            'status'                     => 1,
            'received_date'              => now()->toDateString(),
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        $this->artisan('raw-material:recalculate-freight')
            ->expectsOutputToContain('Recalculated freight for 1 order item(s)')
            ->assertExitCode(0);

        $item = RawMaterialOrderItem::find($itemId);
        // 100 (freight/ton) × 5 (qty) = 500
        expect((float) $item->total_freight)->toBe(500.0);
    });

    it('sets total_freight to zero when no approved receives exist', function () {
        $broker   = SupplierBroker::create(['name' => 'SB-CMD2-' . uniqid(), 'status' => 1]);
        $supplier = Supplier::create([
            'supplier_broker_id' => $broker->id,
            'name'               => 'Sup-CMD2-' . uniqid(),
            'email'              => uniqid() . '@cmd2.test',
            'status'             => 1,
        ]);
        $category = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-CMD2-' . uniqid(),
            'name'               => 'CmdCat2-' . uniqid(),
            'status'             => 1,
        ]);
        $material = RawMaterial::create([
            'raw_material_unique_id'   => 'RM-CMD2-' . uniqid(),
            'raw_material_category_id' => $category->id,
            'name'                     => 'CmdMat2-' . uniqid(),
            'unit'                     => 'Ton',
            'status'                   => 1,
        ]);
        $order = RawMaterialOrder::create([
            'order_unique_id'    => 'ORD-CMD2-' . uniqid(),
            'supplier_broker_id' => $broker->id,
            'supplier_id'        => $supplier->id,
            'order_date'         => now()->toDateString(),
            'price_basis'        => 'FOR + GST',
            'status'             => 0,
        ]);

        $itemId = DB::table('raw_material_order_items')->insertGetId([
            'raw_material_order_id' => $order->id,
            'raw_material_id'       => $material->id,
            'total_qty'             => 10,
            'price'                 => 500,
            'other_expense'         => 0,
            'pending_qty'           => 10,
            'received_qty'          => 0,
            'pending_price'         => 5000000,
            'received_price'        => 0,
            'total_price'           => 5000000,
            'total_freight'         => 777, // wrong value with no receives
            'price_avg'             => 0,
            'status'                => 0,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $this->artisan('raw-material:recalculate-freight')
            ->assertExitCode(0);

        $item = RawMaterialOrderItem::find($itemId);
        expect((float) $item->total_freight)->toBe(0.0);
    });
});

// ─────────────────────────────────────────────

describe('payment:accrue-late-fees', function () {
    beforeEach(function () {
        DB::table('general_settings')->insert([
            ['key' => 'payment_due_days',   'value' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_due_amount', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ]);
    });

    it('skips accrual and outputs skip message when late fee is disabled', function () {
        $this->artisan('payment:accrue-late-fees')
            ->expectsOutputToContain('Late fee accrual skipped')
            ->assertExitCode(0);
    });

    it('outputs processed stats when late fee is enabled but no dispatches exist', function () {
        DB::table('general_settings')->where('key', 'payment_due_days')->update(['value' => '3']);
        DB::table('general_settings')->where('key', 'payment_due_amount')->update(['value' => '10']);

        $this->artisan('payment:accrue-late-fees')
            ->expectsOutputToContain('Processed 0 dispatch(es)')
            ->assertExitCode(0);
    });

    it('accrues fees and outputs stats for eligible dispatches', function () {
        Mail::fake();

        DB::table('general_settings')->where('key', 'payment_due_days')->update(['value' => '3']);
        DB::table('general_settings')->where('key', 'payment_due_amount')->update(['value' => '10']);

        foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter'] as $r) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $brand  = \App\Models\BrandManagement::create(['name' => 'Brand-Cmd-' . uniqid(), 'status' => 1]);
        $broker = \App\Models\User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

        $dealerUser = \App\Models\User::factory()->create(['status' => 1]);
        $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

        $dealer = \App\Models\DealerManagement::create([
            'broker_id'         => $broker->id,
            'brand_id'          => $brand->id,
            'user_id'           => $dealerUser->id,
            'code_no'           => 'D-CMD-' . uniqid(),
            'firm_shop_name'    => 'Firm-CMD-' . uniqid(),
            'firm_shop_address' => 'Test Address',
        ]);
        $product = \App\Models\Product::create([
            'name'     => 'Prod-CMD-' . uniqid(),
            'brand_id' => $brand->id,
            'unit'     => 'Bag',
            'price'    => 100,
            'status'   => 1,
        ]);
        $order = \App\Models\OrderManagement::create([
            'unique_order_id'    => 'ORD-CMD-' . uniqid(),
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
        $orderItem = \App\Models\OrderItem::create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'qty'         => 10,
            'unit_price'  => 100,
            'total_price' => 1000,
        ]);
        $transporter = \App\Models\User::factory()->create(['status' => 1]);
        $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

        \App\Models\DispatchManagement::create([
            'order_id'         => $order->id,
            'order_item_id'    => $orderItem->id,
            'product_id'       => $product->id,
            'no_of_bags'       => 5,
            'dispatch_date'    => '2026-01-01',
            'transport_id'     => $transporter->id,
            'truck_number'     => 'GJ01AA0001',
            'driver_contact'   => '9876543210',
            'status'           => \App\Models\DispatchManagement::STATUS_UNPAID,
            'accrued_late_fee' => 0,
        ]);

        // Run on Jan 7 — first charge date is Jan 5 (0+3+1=4th), 3 chargeable days (5,6,7)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-01-07'));

        $this->artisan('payment:accrue-late-fees')
            ->expectsOutputToContain('Processed 1 dispatch(es)')
            ->assertExitCode(0);

        \Carbon\Carbon::setTestNow(); // reset

        // Verify the actual accrual happened in DB
        $dispatch = \App\Models\DispatchManagement::first();
        $dispatch->refresh();
        expect((float) $dispatch->accrued_late_fee)->toBeGreaterThan(0);
    });
});

describe('utility routes', function () {
    it('redirects guest from cache clear route', function () {
        $this->get('/clear')->assertRedirect(route('login'));
    });

    it('allows authenticated user to clear caches', function () {
        $user = authUser();

        $this->actingAs($user)
            ->get('/clear')
            ->assertOk()
            ->assertSee('All cache cleared successfully');
    });
});
