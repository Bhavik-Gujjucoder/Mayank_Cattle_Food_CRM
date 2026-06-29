<?php

use App\Models\BrandManagement;
use App\Models\CityManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\StateManagement;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function dashActor(array $roleNames = ['admin']): User
{
    $user = User::factory()->create(['status' => 1]);
    foreach ($roleNames as $r) {
        $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']));
    }

    return $user;
}

function dashSetupOrder(User $broker, ?DealerManagement $dealer = null, int $itemQty = 5): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'DashB-' . uniqid(), 'status' => 1]);
    $dealer ??= DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'DashFirm',
        'firm_shop_address' => 'Addr',
    ]);
    $product = Product::create(['name' => 'DP-' . uniqid(), 'brand_id' => $brand->id, 'unit' => 'Bag', 'price' => 100, 'status' => 1]);
    $order   = OrderManagement::create([
        'unique_order_id'    => 'ORD/DASH/' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Addr',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000,
        'grand_total'        => 1000,
        'status'             => 1,
    ]);
    OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => $itemQty,
        'unit_price'  => 100,
        'total_price' => 100 * $itemQty,
    ]);

    return $order->load('items');
}

function dashSetupOrderForDealerUser(User $dealerUser, User $broker): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'DashB-' . uniqid(), 'status' => 1]);
    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'DashDealerFirm',
        'firm_shop_address' => 'Addr',
    ]);

    return dashSetupOrder($broker, $dealer);
}

function dashCreateDispatch(OrderManagement $order, int $bags): void
{
    $item = $order->items->first();
    $transporter = User::factory()->create(['status' => 1]);

    DispatchManagement::create([
        'order_id'         => $order->id,
        'order_item_id'    => $item->id,
        'product_id'       => $item->product_id,
        'no_of_bags'       => $bags,
        'dispatch_date'    => '2026-01-15',
        'transport_id'     => $transporter->id,
        'truck_number'     => 'GJ01AA1234',
        'driver_contact'   => '9876543210',
        'status'           => 0,
        'accrued_late_fee' => 0,
    ]);
}

function dashRmSummaryFixture(): array
{
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'RMC-DASH-' . uniqid(),
        'name'               => 'Dashboard Summary Category',
        'status'             => 1,
    ]);

    $material = RawMaterial::create([
        'raw_material_unique_id'   => 'RM-DASH-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name'                     => 'Soda Material',
        'unit'                     => 'ton',
        'status'                   => 1,
    ]);

    $state = StateManagement::create([
        'state_name' => 'Dash State ' . uniqid(),
        'status'     => 1,
    ]);

    $city = CityManagement::create([
        'state_id'  => $state->id,
        'city_name' => 'Gokak',
        'status'    => 1,
    ]);

    $supplierBroker = SupplierBroker::create([
        'name'   => 'Nesheil Broker',
        'status' => 1,
    ]);

    $supplier = Supplier::create([
        'name'    => 'Roquette Supplier',
        'city_id' => $city->id,
        'status'  => 1,
    ]);

    $order = RawMaterialOrder::create([
        'order_unique_id'      => 'RMO-DASH-' . uniqid(),
        'supplier_id'          => $supplier->id,
        'supplier_broker_id'   => $supplierBroker->id,
        'supplier_order_id'    => 'SO-100',
        'order_date'           => now()->subDays(5)->toDateString(),
        'status'               => 0,
    ]);

    RawMaterialOrderItem::create([
        'raw_material_id'         => $material->id,
        'raw_material_order_id'   => $order->id,
        'total_qty'               => 200,
        'price'                   => 65,
    ]);

    return compact('material', 'supplier', 'supplierBroker', 'order');
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

describe('access-control', function () {
    it('redirects unauthenticated user to login', function () {
        get(route('dashboard'))->assertRedirect(route('login'));
    });

    it('returns 200 for authenticated admin', function () {
        actingAs(dashActor())->get(route('dashboard'))->assertOk();
    });

    it('returns 200 for authenticated broker', function () {
        actingAs(dashActor(['broker']))->get(route('dashboard'))->assertOk();
    });

    it('returns 200 for authenticated dealer', function () {
        actingAs(dashActor(['dealer']))->get(route('dashboard'))->assertOk();
    });

    it('returns 200 for authenticated transporter', function () {
        actingAs(dashActor(['transporter']))->get(route('dashboard'))->assertOk();
    });
});

// ─────────────────────────────────────────────

describe('view data', function () {
    it('renders dashboard view with core variables', function () {
        actingAs(dashActor())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('login_user')
            ->assertViewHas('role')
            ->assertViewHas('user_name')
            ->assertViewHas('page_title')
            ->assertViewHas('total_dealers')
            ->assertViewHas('total_broker')
            ->assertViewHas('total_soda_order')
            ->assertViewHas('total_dispatch_order')
            ->assertViewHas('total_raw_materials')
            ->assertViewHas('total_raw_material_orders')
            ->assertViewHas('transporters')
            ->assertViewHas('rm_daily_summary')
            ->assertViewHas('rm_summary_materials')
            ->assertViewHas('rm_material_filter')
            ->assertViewHas('rm_date_from')
            ->assertViewHas('rm_date_to');
    });

    it('sets login_user and user_name from the authenticated user', function () {
        $actor = dashActor(['admin']);

        $response = actingAs($actor)->get(route('dashboard'));

        expect($response->viewData('login_user')->id)->toBe($actor->id)
            ->and($response->viewData('user_name'))->toBe($actor->name);
    });

    it('sets page_title from the user role', function () {
        $response = actingAs(dashActor(['broker']))->get(route('dashboard'));

        expect($response->viewData('page_title'))->toBe('Broker Dashboard');
    });

    it('sets role from the user\'s first assigned role', function () {
        $response = actingAs(dashActor(['admin']))->get(route('dashboard'));
        expect($response->viewData('role'))->toBe('admin');
    });

    it('sets role correctly for broker user', function () {
        $response = actingAs(dashActor(['broker']))->get(route('dashboard'));
        expect($response->viewData('role'))->toBe('broker');
    });

    it('dispatch form orders endpoint returns 403 without add-dispatch permission', function () {
        actingAs(dashActor())
            ->getJson(route('dashboard.data.dispatch-form-orders'))
            ->assertForbidden();
    });

    it('dispatch form orders endpoint is populated when user has add-dispatch and orders exist', function () {
        $actor  = dashActor();
        grantPermissions($actor, ['add-dispatch']);
        $broker = User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
        dashSetupOrder($broker);

        actingAs($actor)
            ->getJson(route('dashboard.data.dispatch-form-orders'))
            ->assertOk()
            ->assertJsonPath('orders.0.id', fn ($id) => $id > 0);
    });

    it('dispatch form orders endpoint excludes fully dispatched orders', function () {
        $actor  = dashActor();
        grantPermissions($actor, ['add-dispatch']);
        $broker = User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

        $openOrder = dashSetupOrder($broker, null, 10);
        $closedOrder = dashSetupOrder($broker, null, 5);
        dashCreateDispatch($closedOrder, 5);

        $response = actingAs($actor)->getJson(route('dashboard.data.dispatch-form-orders'));
        $orderIds = collect($response->json('orders'))->pluck('id')->all();

        expect($orderIds)->toContain($openOrder->id)
            ->and($orderIds)->not->toContain($closedOrder->id);
    });

    it('rm_daily_summary is null when user lacks raw-material-daily-summary permission', function () {
        $response = actingAs(dashActor())->get(route('dashboard'));
        expect($response->viewData('rm_daily_summary'))->toBeNull();
    });

    it('rm_daily_summary is populated when user has raw-material-daily-summary permission', function () {
        $actor = dashActor();
        grantPermissions($actor, ['raw-material-daily-summary']);
        dashRmSummaryFixture();

        $response = actingAs($actor)->get(route('dashboard'));

        expect($response->viewData('rm_daily_summary'))->not->toBeNull()
            ->and($response->viewData('rm_summary_materials'))->not->toBeEmpty()
            ->and($response->viewData('rm_material_filter'))->toBe('all')
            ->and($response->viewData('rm_date_from'))->toBeNull()
            ->and($response->viewData('rm_date_to'))->toBeNull();
    });

    it('rm daily summary respects material and date query filters', function () {
        $actor   = dashActor();
        $fixture = dashRmSummaryFixture();
        grantPermissions($actor, ['raw-material-daily-summary']);

        $dateFrom = now()->subDays(10)->toDateString();
        $dateTo   = now()->toDateString();

        $response = actingAs($actor)->get(route('dashboard', [
            'rm_material_id' => $fixture['material']->id,
            'rm_date_from'   => $dateFrom,
            'rm_date_to'     => $dateTo,
        ]));

        expect($response->viewData('rm_material_filter'))->toBe((string) $fixture['material']->id)
            ->and($response->viewData('rm_date_from'))->toBe($dateFrom)
            ->and($response->viewData('rm_date_to'))->toBe($dateTo);

        actingAs($actor)
            ->getJson(route('dashboard.data.rm-daily-summary', [
                'rm_material_id' => $fixture['material']->id,
                'rm_date_from'   => $dateFrom,
                'rm_date_to'     => $dateTo,
            ]))
            ->assertOk()
            ->assertJsonStructure(['data', 'totals']);
    });

    it('shows daily summary widget for permitted users', function () {
        $actor = dashActor();
        grantPermissions($actor, ['raw-material-daily-summary']);
        dashRmSummaryFixture();

        actingAs($actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Daily Raw Material Summary')
            ->assertSee('id="rm_daily_summary_table"', false);

        actingAs($actor)
            ->getJson(route('dashboard.data.rm-daily-summary'))
            ->assertOk()
            ->assertJsonPath('data.0.party_name', 'Roquette Supplier - Gokak')
            ->assertJsonPath('data.0.supplier_broker_name', 'Nesheil Broker');
    });

    it('hides daily summary widget without permission', function () {
        dashRmSummaryFixture();

        actingAs(dashActor())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Daily Raw Material Summary');
    });

    it('total_dealers reflects correct count', function () {
        $broker     = User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
        $brand      = BrandManagement::create(['name' => 'DashBrand2', 'status' => 1]);
        $dealerUser = User::factory()->create(['status' => 1]);
        DealerManagement::create([
            'broker_id'         => $broker->id,
            'brand_id'          => $brand->id,
            'user_id'           => $dealerUser->id,
            'code_no'           => 'D-DT',
            'firm_shop_name'    => 'Firm',
            'firm_shop_address' => 'Addr',
        ]);

        $response = actingAs(dashActor())->get(route('dashboard'));
        expect($response->viewData('total_dealers'))->toBeGreaterThanOrEqual(1);
    });

    it('admin sees all orders (global SalesScope)', function () {
        $broker = User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
        dashSetupOrder($broker);

        $response = actingAs(dashActor(['admin']))->get(route('dashboard'));
        expect($response->viewData('total_soda_order'))->toBeGreaterThanOrEqual(1);
    });

    it('broker sees own order count when orders exist', function () {
        $broker = dashActor(['broker']);
        dashSetupOrder($broker);

        $response = actingAs($broker)->get(route('dashboard'));
        expect($response->viewData('total_soda_order'))->toBe(1);
    });

    it('broker only sees own orders (SalesScope)', function () {
        $broker1 = dashActor(['broker']);
        $broker2 = User::factory()->create(['status' => 1]);
        $broker2->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
        dashSetupOrder($broker2);

        $response = actingAs($broker1)->get(route('dashboard'));
        expect($response->viewData('total_soda_order'))->toBe(0);
    });

    it('broker only sees own dispatches (SalesScope)', function () {
        $broker1 = dashActor(['broker']);
        $broker2 = User::factory()->create(['status' => 1]);
        $broker2->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
        dashCreateDispatch(dashSetupOrder($broker2), 3);

        expect(actingAs($broker1)->get(route('dashboard'))->viewData('total_dispatch_order'))->toBe(0);
        expect(actingAs($broker2)->get(route('dashboard'))->viewData('total_dispatch_order'))->toBe(1);
    });

    it('dealer only sees orders linked to their dealer profile (SalesScope)', function () {
        $broker = User::factory()->create(['status' => 1]);
        $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

        $dealerUser = dashActor(['dealer']);
        $otherDealer = dashActor(['dealer']);
        dashSetupOrderForDealerUser($dealerUser, $broker);

        expect(actingAs($dealerUser)->get(route('dashboard'))->viewData('total_soda_order'))->toBe(1);
        expect(actingAs($otherDealer)->get(route('dashboard'))->viewData('total_soda_order'))->toBe(0);
    });
});

// ─────────────────────────────────────────────

describe('export raw-material daily summary', function () {
    it('redirects guests to login', function () {
        get(route('dashboard.raw-material-daily-summary.export'))
            ->assertRedirect(route('login'));
    });

    it('returns 403 when user lacks export-raw-material-purchas-order permission', function () {
        actingAs(dashActor())
            ->get(route('dashboard.raw-material-daily-summary.export'))
            ->assertForbidden();
    });

    it('redirects with error when no summary data exists', function () {
        $actor = dashActor();
        grantPermissions($actor, ['export-raw-material-purchas-order']);

        actingAs($actor)
            ->get(route('dashboard.raw-material-daily-summary.export'))
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('returns spreadsheet download when summary data exists', function () {
        $actor = dashActor();
        grantPermissions($actor, ['export-raw-material-purchas-order']);
        dashRmSummaryFixture();

        actingAs($actor)
            ->get(route('dashboard.raw-material-daily-summary.export'))
            ->assertOk()
            ->assertDownload('raw-material-daily-summary-' . now()->format('Y-m-d') . '.xlsx');
    });
});
