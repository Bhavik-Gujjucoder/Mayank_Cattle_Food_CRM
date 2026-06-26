<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function ordGrant(User $user, array $permissionNames): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ($permissionNames as $name) {
        Permission::firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
            ['type' => 'test']
        );
    }

    $user->givePermissionTo($permissionNames);

    return $user;
}

function ordActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    if (! empty($perms)) {
        ordGrant($user, $perms);
    }

    return $user;
}

function mkOrdBrand(array $attrs = []): BrandManagement
{
    return BrandManagement::create(array_merge(['name' => 'Brand '.uniqid(), 'status' => 1], $attrs));
}

function mkOrdProduct(int $brandId, array $attrs = []): Product
{
    return Product::create(array_merge([
        'name' => 'Product '.uniqid(),
        'brand_id' => $brandId,
        'unit' => 'Bag',
        'price' => 100.00,
        'status' => 1,
    ], $attrs));
}

function mkOrdBroker(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $role = Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
    $user->assignRole($role);

    return $user;
}

function mkOrdDealerUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $role = Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);
    $user->assignRole($role);

    return $user;
}

function mkOrdDealer(int $brokerId, int $brandId, ?int $userId = null, array $attrs = []): DealerManagement
{
    return DealerManagement::create(array_merge([
        'broker_id' => $brokerId,
        'brand_id' => $brandId,
        'user_id' => $userId,
        'code_no' => 'D-'.uniqid(),
        'firm_shop_name' => 'Firm '.uniqid(),
        'firm_shop_address' => 'Test Address',
    ], $attrs));
}

function mkOrder(int $brokerId, int $brandId, int $dealerId, array $attrs = []): OrderManagement
{
    return OrderManagement::create(array_merge([
        'unique_order_id' => 'ORD/'.uniqid(),
        'broker_id' => $brokerId,
        'brand_id' => $brandId,
        'dealer_id' => $dealerId,
        'order_date' => '2026-01-01',
        'delivery_address' => 'Test Delivery Address',
        'payment_status' => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total' => 1000.00,
        'status' => 1,
    ], $attrs));
}

function mkOrderItem(int $orderId, int $productId, array $attrs = []): OrderItem
{
    return OrderItem::create(array_merge([
        'order_id' => $orderId,
        'product_id' => $productId,
        'qty' => 10,
        'unit_price' => 100.00,
        'total_price' => 1000.00,
    ], $attrs));
}

function ordPayload(int $brokerId, int $brandId, int $dealerId, int $productId, array $overrides = []): array
{
    return array_merge([
        'unique_order_id' => 'ORD/'.uniqid(),
        'broker_id' => $brokerId,
        'brand_id' => $brandId,
        'dealer_id' => $dealerId,
        'order_date' => '2026-01-01',
        'delivery_address' => 'Test Delivery Address',
        'payment_status' => 'unpaid',
        'product_id' => [$productId],
        'qty' => [10],
        'price' => ['100.00'],
    ], $overrides);
}

// ─────────────────────────────────────────────

beforeEach(function () {
    foreach (['super admin', 'admin', 'broker', 'dealer'] as $r) {
        Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
    DB::table('general_settings')->insert([
        ['key' => 'payment_due_days',   'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'payment_due_amount', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from order index', function () {
        get(route('order.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order create', function () {
        get(route('order.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order store', function () {
        post(route('order.store'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order destroy', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        delete(route('order.destroy', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order show', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        get(route('order.show', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order edit', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        get(route('order.edit', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order update', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        put(route('order.update', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order bulkDelete', function () {
        post(route('order.bulkDelete'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order deleteCheck', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        get(route('order.deleteCheck', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order dispatchCheck', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        get(route('order.dispatchCheck', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order listItemsDetail', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        get(route('order.listItemsDetail', $order))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from order lastItemPrice', function () {
        get(route('order.lastItemPrice'))->assertRedirect(route('login'));
    });

    it('returns 403 when user lacks view-order on show', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = User::factory()->create(['status' => 1]);
        actingAs($actor)->get(route('order.show', $order))->assertForbidden();
    });

    it('returns 403 when user lacks view-order on index', function () {
        $actor = User::factory()->create(['status' => 1]);
        actingAs($actor)->get(route('order.index'))->assertForbidden();
    });

    it('returns 403 when user lacks add-order on store', function () {
        $actor = ordActor(); // no extra perms beyond admin role
        actingAs($actor)->post(route('order.store'))->assertForbidden();
    });

    it('returns 403 when user lacks add-order on create view', function () {
        $actor = ordActor(['view-order']);
        actingAs($actor)->get(route('order.create'))->assertForbidden();
    });

    it('returns 403 when user lacks edit-order on edit view', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order']);
        actingAs($actor)->get(route('order.edit', $order))->assertForbidden();
    });

    it('returns 403 when user lacks edit-order on update', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order']);
        actingAs($actor)->put(route('order.update', $order))->assertForbidden();
    });

    it('returns 403 when user lacks delete-order on destroy', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order', 'edit-order']);
        actingAs($actor)->delete(route('order.destroy', $order))->assertForbidden();
    });

    it('returns 403 when user lacks delete-order on bulkDelete', function () {
        $actor = ordActor(['view-order']);
        actingAs($actor)->post(route('order.bulkDelete'))->assertForbidden();
    });

    it('returns 403 when user lacks view-order on listItemsDetail', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['edit-order']);
        actingAs($actor)->get(route('order.listItemsDetail', $order))->assertForbidden();
    });

    it('deleteCheck accessible to any authenticated user with access to the order', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order']);

        actingAs($actor)->getJson(route('order.deleteCheck', $order))->assertOk();
    });

    it('dispatchCheck accessible to any authenticated user with access to the order', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order']);

        actingAs($actor)->getJson(route('order.dispatchCheck', $order))->assertOk();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns order index view', function () {
        $actor = ordActor(['view-order']);
        actingAs($actor)
            ->get(route('order.index'))
            ->assertOk()
            ->assertViewIs('order_management.index')
            ->assertViewHas('brokers')
            ->assertViewHas('brands')
            ->assertViewHas('dealers');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $actor = ordActor(['view-order']);
        actingAs($actor)
            ->getJson(route('order.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX returns order records', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order']);
        mkOrder($broker->id, $brand->id, $dealer->id);

        $response = actingAs($actor)
            ->getJson(route('order.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters orders by brand_id', function () {
        $brand1 = mkOrdBrand();
        $brand2 = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer1 = mkOrdDealer($broker->id, $brand1->id);
        $dealer2 = mkOrdDealer($broker->id, $brand2->id);
        $actor = ordActor(['view-order']);
        mkOrder($broker->id, $brand1->id, $dealer1->id);
        mkOrder($broker->id, $brand2->id, $dealer2->id);

        $response = actingAs($actor)
            ->getJson(route('order.index')."?brand_id={$brand1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters orders by dealer_id', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer1 = mkOrdDealer($broker->id, $brand->id);
        $dealer2 = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order']);
        mkOrder($broker->id, $brand->id, $dealer1->id);
        mkOrder($broker->id, $brand->id, $dealer2->id);

        $response = actingAs($actor)
            ->getJson(route('order.index')."?dealer_id={$dealer1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters orders by date_from', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order']);
        mkOrder($broker->id, $brand->id, $dealer->id, ['order_date' => '2026-01-01']);
        mkOrder($broker->id, $brand->id, $dealer->id, ['order_date' => '2026-03-01']);

        $response = actingAs($actor)
            ->getJson(route('order.index').'?date_from=2026-02-01', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters orders by date_to', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order']);
        mkOrder($broker->id, $brand->id, $dealer->id, ['order_date' => '2026-01-01']);
        mkOrder($broker->id, $brand->id, $dealer->id, ['order_date' => '2026-03-01']);

        $response = actingAs($actor)
            ->getJson(route('order.index').'?date_to=2026-02-01', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX action column shows delete button when user has delete-order', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order', 'delete-order']);
        mkOrder($broker->id, $brand->id, $dealer->id);

        $response = actingAs($actor)
            ->getJson(route('order.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.action'))->toContain('deleteOrder');
    });

    it('AJAX action column hides delete button when user lacks delete-order', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['view-order', 'edit-order']);
        mkOrder($broker->id, $brand->id, $dealer->id);

        $response = actingAs($actor)
            ->getJson(route('order.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.action'))->not->toContain('deleteOrder');
    });

    it('broker role only sees their own orders in AJAX', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer1 = mkOrdDealer($broker1->id, $brand->id);
        $dealer2 = mkOrdDealer($broker2->id, $brand->id);
        mkOrder($broker1->id, $brand->id, $dealer1->id);
        mkOrder($broker2->id, $brand->id, $dealer2->id);
        ordGrant($broker1, ['view-order']);

        $response = actingAs($broker1)
            ->getJson(route('order.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('returns create view with brands, brokers, and products', function () {
        $actor = ordActor(['add-order']);
        actingAs($actor)
            ->get(route('order.create'))
            ->assertOk()
            ->assertViewIs('order_management.create')
            ->assertViewHas('brands')
            ->assertViewHas('brokers')
            ->assertViewHas('products')
            ->assertViewHas('order_id');
    });

    it('auto-generated order_id has correct financial-year prefix', function () {
        $actor = ordActor(['add-order']);
        $response = actingAs($actor)->get(route('order.create'));
        $orderId = $response->viewData('order_id');

        expect($orderId)->toStartWith('ORD/');
    });

    it('passes locked_dealer for dealer-role user', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealerUser = mkOrdDealerUser();
        mkOrdDealer($broker->id, $brand->id, $dealerUser->id);
        ordGrant($dealerUser, ['add-order']);

        $response = actingAs($dealerUser)->get(route('order.create'));
        expect($response->viewData('locked_dealer'))->not->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('fails when unique_order_id is missing', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['unique_order_id' => '']))
            ->assertSessionHasErrors(['unique_order_id']);
    });

    it('fails when unique_order_id is already taken', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);
        mkOrder($broker->id, $brand->id, $dealer->id, ['unique_order_id' => 'ORD/DUPLICATE']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['unique_order_id' => 'ORD/DUPLICATE']))
            ->assertSessionHasErrors(['unique_order_id']);
    });

    it('fails when broker_id is not an active broker', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker(['status' => 0]); // inactive
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id))
            ->assertSessionHasErrors(['broker_id']);
    });

    it('fails when brand_id is inactive', function () {
        $brand = mkOrdBrand(['status' => 0]); // inactive
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id))
            ->assertSessionHasErrors(['brand_id']);
    });

    it('fails when dealer_id does not exist', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, 99999, $product->id))
            ->assertSessionHasErrors(['dealer_id']);
    });

    it('fails when order_date is missing', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['order_date' => '']))
            ->assertSessionHasErrors(['order_date']);
    });

    it('fails when delivery_address is missing', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['delivery_address' => '']))
            ->assertSessionHasErrors(['delivery_address']);
    });

    it('fails when payment_status is invalid', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['payment_status' => 'invalid']))
            ->assertSessionHasErrors(['payment_status']);
    });

    it('fails when payment_status=partial and partial_paid_amount is missing', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
                'payment_status' => 'partial',
                'partial_paid_amount' => '',
            ]))
            ->assertSessionHasErrors(['partial_paid_amount']);
    });

    it('fails when product_id array is empty', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, 0, [
                'product_id' => [],
                'qty' => [],
                'price' => [],
            ]))
            ->assertSessionHasErrors(['product_id']);
    });

    it('fails when product_id does not exist', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, 99999))
            ->assertSessionHasErrors(['product_id.0']);
    });

    it('fails when qty is less than 1', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['qty' => [0]]))
            ->assertSessionHasErrors(['qty.0']);
    });

    it('fails when price is negative', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, ['price' => [-10]]))
            ->assertSessionHasErrors(['price.0']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates an order record in the database', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => 'ORD/2025-26/0001',
            'delivery_address' => 'Warehouse 1',
        ]));

        assertDatabaseHas('order_management', [
            'unique_order_id' => 'ORD/2025-26/0001',
            'broker_id' => $broker->id,
            'brand_id' => $brand->id,
            'dealer_id' => $dealer->id,
            'delivery_address' => 'Warehouse 1',
        ]);
    });

    it('creates order items for each product line', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product1 = mkOrdProduct($brand->id);
        $product2 = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product1->id, [
            'product_id' => [$product1->id, $product2->id],
            'qty' => [5, 8],
            'price' => ['100.00', '200.00'],
        ]));

        $order = OrderManagement::latest()->first();
        expect($order->items)->toHaveCount(2);
    });

    it('calculates grand_total correctly from line items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        // qty=5, price=200 → total=1000
        actingAs($actor)->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'qty' => [5],
            'price' => ['200.00'],
        ]));

        $order = OrderManagement::latest()->first();
        expect((float) $order->grand_total)->toBe(1000.0);
    });

    it('stores partial_paid_amount when payment_status=partial', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'payment_status' => 'partial',
            'partial_paid_amount' => '500.00',
        ]));

        $order = OrderManagement::latest()->first();
        expect((float) $order->partial_paid_amount)->toBe(500.0);
    });

    it('sets partial_paid_amount to null when payment_status is not partial', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'payment_status' => 'unpaid',
            'partial_paid_amount' => '500.00',
        ]));

        $order = OrderManagement::latest()->first();
        expect($order->partial_paid_amount)->toBeNull();
    });

    it('redirects to order.index after successful store', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['add-order']);

        actingAs($actor)
            ->post(route('order.store'), ordPayload($broker->id, $brand->id, $dealer->id, $product->id))
            ->assertRedirect(route('order.index'));
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('returns show view with order details', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);
        $actor = ordActor(['view-order']);

        actingAs($actor)
            ->get(route('order.show', $order))
            ->assertOk()
            ->assertViewIs('order_management.show')
            ->assertViewHas('order', fn ($o) => $o->id === $order->id);
    });

    it('returns 403 when broker tries to view another broker\'s order', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer = mkOrdDealer($broker1->id, $brand->id);
        $order = mkOrder($broker1->id, $brand->id, $dealer->id);
        ordGrant($broker2, ['view-order']);

        actingAs($broker2)
            ->get(route('order.show', $order))
            ->assertForbidden();
    });

    it('eager loads broker, brand, dealer, and items on show', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);

        $response = actingAs(ordActor(['view-order']))
            ->get(route('order.show', $order));

        $loaded = $response->viewData('order');
        expect($loaded->relationLoaded('broker'))->toBeTrue()
            ->and($loaded->relationLoaded('brand'))->toBeTrue()
            ->and($loaded->relationLoaded('dealer'))->toBeTrue()
            ->and($loaded->relationLoaded('items'))->toBeTrue();
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('returns edit view with order data', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);
        $actor = ordActor(['edit-order']);

        actingAs($actor)
            ->get(route('order.edit', $order))
            ->assertOk()
            ->assertViewIs('order_management.edit')
            ->assertViewHas('order')
            ->assertViewHas('brands')
            ->assertViewHas('dealers');
    });

    it('returns 403 when broker tries to edit another broker\'s order', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer = mkOrdDealer($broker1->id, $brand->id);
        $order = mkOrder($broker1->id, $brand->id, $dealer->id);
        ordGrant($broker2, ['edit-order']);

        actingAs($broker2)
            ->get(route('order.edit', $order))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('allows same unique_order_id on self-update', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id, ['unique_order_id' => 'ORD/MINE']);
        $item = mkOrderItem($order->id, $product->id);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => 'ORD/MINE',
            'item_id' => [$item->id],
        ]);

        actingAs($actor)
            ->put(route('order.update', $order), $payload)
            ->assertRedirect(route('order.index'));
    });

    it('fails when trying to change broker on order that has dispatched items', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer = mkOrdDealer($broker1->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker1->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 5,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker2->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item->id],
        ]);

        actingAs($actor)
            ->put(route('order.update', $order), $payload)
            ->assertSessionHasErrors(['broker_id']);
    });

    it('fails when trying to reduce qty below dispatched qty', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 10]);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 8, // dispatched 8 of 10
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item->id],
            'qty' => [3], // 3 < 8 dispatched
        ]);

        actingAs($actor)
            ->put(route('order.update', $order), $payload)
            ->assertSessionHasErrors(['qty']);
    });

    it('fails when trying to remove a dispatched item', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product1 = mkOrdProduct($brand->id);
        $product2 = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item1 = mkOrderItem($order->id, $product1->id);
        $item2 = mkOrderItem($order->id, $product2->id);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item2->id,
            'product_id' => $product2->id,
            'no_of_bags' => 3,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['edit-order']);

        // Submitting only item1 (item2 is omitted → would be deleted)
        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product1->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item1->id], // item2 not in list → wants to delete
            'product_id' => [$product1->id],
            'qty' => [10],
            'price' => ['100.00'],
        ]);

        actingAs($actor)
            ->put(route('order.update', $order), $payload)
            ->assertSessionHasErrors(['product_id']);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates order fields', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'delivery_address' => 'Updated Warehouse',
            'payment_status' => 'paid',
            'item_id' => [$item->id],
        ]);

        actingAs($actor)->put(route('order.update', $order), $payload);

        assertDatabaseHas('order_management', [
            'id' => $order->id,
            'delivery_address' => 'Updated Warehouse',
            'payment_status' => 'paid',
        ]);
    });

    it('updates existing order item quantities and price', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 10, 'unit_price' => 100]);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item->id],
            'qty' => [20],
            'price' => ['150.00'],
        ]);

        actingAs($actor)->put(route('order.update', $order), $payload);

        assertDatabaseHas('order_items', [
            'id' => $item->id,
            'qty' => 20,
            'unit_price' => 150.00,
        ]);
    });

    it('recalculates grand_total on update', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 10, 'unit_price' => 100]);
        $actor = ordActor(['edit-order']);

        // Update: qty=5, price=300 → total=1500
        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item->id],
            'qty' => [5],
            'price' => ['300.00'],
        ]);

        actingAs($actor)->put(route('order.update', $order), $payload);

        $order->refresh();
        expect((float) $order->grand_total)->toBe(1500.0);
    });

    it('removes un-submitted item from order (hard-delete)', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product1 = mkOrdProduct($brand->id);
        $product2 = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item1 = mkOrderItem($order->id, $product1->id);
        $item2 = mkOrderItem($order->id, $product2->id); // will be removed
        $actor = ordActor(['edit-order']);

        // Only submit item1 (item2 is not in item_id array)
        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product1->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item1->id],
            'product_id' => [$product1->id],
            'qty' => [10],
            'price' => ['100.00'],
        ]);

        actingAs($actor)->put(route('order.update', $order), $payload);

        assertDatabaseMissing('order_items', ['id' => $item2->id]);
    });

    it('redirects to order.index after successful update', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id);
        $actor = ordActor(['edit-order']);

        $payload = ordPayload($broker->id, $brand->id, $dealer->id, $product->id, [
            'unique_order_id' => $order->unique_order_id,
            'item_id' => [$item->id],
        ]);

        actingAs($actor)
            ->put(route('order.update', $order), $payload)
            ->assertRedirect(route('order.index'));
    });
});

// ─────────────────────────────────────────────

describe('deleteCheck', function () {
    it('returns can_delete=true when no dispatches exist', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.deleteCheck', $order))
            ->assertOk();

        expect($response->json('can_delete'))->toBeTrue()
            ->and($response->json('dispatched_items'))->toBeEmpty();
    });

    it('returns can_delete=false with dispatched_items when dispatches exist', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 10]);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 5,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.deleteCheck', $order))
            ->assertOk();

        expect($response->json('can_delete'))->toBeFalse()
            ->and($response->json('dispatched_items'))->toHaveCount(1);
    });

    it('returns 403 when broker tries to check another broker\'s order', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer = mkOrdDealer($broker1->id, $brand->id);
        $order = mkOrder($broker1->id, $brand->id, $dealer->id);

        actingAs($broker2)
            ->getJson(route('order.deleteCheck', $order))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes the order', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);
        $actor = ordActor(['delete-order']);

        actingAs($actor)->delete(route('order.destroy', $order));

        assertSoftDeleted('order_management', ['id' => $order->id]);
    });

    it('hard-deletes the order items when order is destroyed', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id);
        $actor = ordActor(['delete-order']);

        actingAs($actor)->delete(route('order.destroy', $order));

        assertDatabaseMissing('order_items', ['id' => $item->id]);
    });

    it('blocks deletion when order has dispatched items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 3,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['delete-order']);

        actingAs($actor)
            ->delete(route('order.destroy', $order))
            ->assertRedirect(route('order.index'));

        // Order should NOT be deleted
        expect(OrderManagement::find($order->id))->not->toBeNull();
    });

    it('redirects to order.index with success message after destroy', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['delete-order']);

        actingAs($actor)
            ->delete(route('order.destroy', $order))
            ->assertRedirect(route('order.index'));
    });
});

// ─────────────────────────────────────────────

describe('bulkDelete', function () {
    it('returns 400 when ids array is empty', function () {
        $actor = ordActor(['delete-order']);
        actingAs($actor)
            ->postJson(route('order.bulkDelete'), ['ids' => null])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('soft-deletes selected orders', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order1 = mkOrder($broker->id, $brand->id, $dealer->id);
        $order2 = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['delete-order']);

        actingAs($actor)
            ->postJson(route('order.bulkDelete'), ['ids' => [$order1->id, $order2->id]])
            ->assertOk();

        assertSoftDeleted('order_management', ['id' => $order1->id]);
        assertSoftDeleted('order_management', ['id' => $order2->id]);
    });

    it('returns 422 and blocks if any order has dispatched items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order1 = mkOrder($broker->id, $brand->id, $dealer->id, ['unique_order_id' => 'ORD/BLOCKED']);
        $item = mkOrderItem($order1->id, $product->id);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order1->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 5,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['delete-order']);

        $response = actingAs($actor)
            ->postJson(route('order.bulkDelete'), ['ids' => [$order1->id]])
            ->assertStatus(422);

        expect($response->json('blocked'))->toBeTrue();
    });

    it('returns success message on successful bulk delete', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['delete-order']);

        actingAs($actor)
            ->postJson(route('order.bulkDelete'), ['ids' => [$order->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected orders deleted successfully.']);
    });
});

// ─────────────────────────────────────────────

describe('checkDispatchEligibility', function () {
    it('returns eligible=true when no prior orders exist', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.dispatchCheck', $order))
            ->assertOk();

        expect($response->json('eligible'))->toBeTrue();
    });

    it('returns eligible=true when all prior orders are fully dispatched', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order1 = mkOrder($broker->id, $brand->id, $dealer->id);
        $order2 = mkOrder($broker->id, $brand->id, $dealer->id);
        $item1 = mkOrderItem($order1->id, $product->id, ['qty' => 5]);
        $transporter = User::factory()->create(['status' => 1]);
        // Fully dispatch order1
        DispatchManagement::create([
            'order_id' => $order1->id,
            'order_item_id' => $item1->id,
            'product_id' => $product->id,
            'no_of_bags' => 5, // fully dispatched
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.dispatchCheck', $order2))
            ->assertOk();

        expect($response->json('eligible'))->toBeTrue();
    });

    it('returns eligible=false with blocking_order when prior order not fully dispatched', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order1 = mkOrder($broker->id, $brand->id, $dealer->id, ['unique_order_id' => 'ORD/BLOCKER']);
        $order2 = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order1->id, $product->id, ['qty' => 10]); // no dispatches → not fully dispatched
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.dispatchCheck', $order2))
            ->assertOk();

        expect($response->json('eligible'))->toBeFalse()
            ->and($response->json('blocking_order.unique_order_id'))->toBe('ORD/BLOCKER');
    });
});

// ─────────────────────────────────────────────

describe('listItemsDetail', function () {
    it('returns JSON with html for order items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.listItemsDetail', $order))
            ->assertOk();

        expect($response->json('html'))->not->toBeNull();
    });

    it('returns 404 when broker accesses another broker\'s order items (scoped out)', function () {
        $brand = mkOrdBrand();
        $broker1 = mkOrdBroker();
        $broker2 = mkOrdBroker();
        $dealer = mkOrdDealer($broker1->id, $brand->id);
        $order = mkOrder($broker1->id, $brand->id, $dealer->id);
        ordGrant($broker2, ['view-order']);

        // listItemsDetail uses scopeOrders + firstOrFail → 404 when order not in scope
        actingAs($broker2)
            ->getJson(route('order.listItemsDetail', $order))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('lastItemPrice', function () {
    it('returns null when dealer_id or product_id is missing', function () {
        $actor = ordActor(['view-order']);

        actingAs($actor)
            ->getJson(route('order.lastItemPrice'))
            ->assertOk()
            ->assertJson(['price' => null]);
    });

    it('returns null when no prior order items exist for that dealer/product', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.lastItemPrice')."?dealer_id={$dealer->id}&product_id={$product->id}")
            ->assertOk();

        expect($response->json('price'))->toBeNull();
    });

    it('returns unit_price from the most recent order item for dealer/product', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id, ['unit_price' => 250.00, 'total_price' => 2500.00]);
        $actor = ordActor(['view-order']);

        $response = actingAs($actor)
            ->getJson(route('order.lastItemPrice')."?dealer_id={$dealer->id}&product_id={$product->id}")
            ->assertOk();

        expect($response->json('price'))->toBe('250.00');
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('isFullyDispatched returns true when all items dispatched', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 5]);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 5,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);

        $order = OrderManagement::with('items.dispatches')->find($order->id);
        expect($order->isFullyDispatched())->toBeTrue();
    });

    it('isFullyDispatched returns false when items not fully dispatched', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id, ['qty' => 10]);

        $order = OrderManagement::with('items.dispatches')->find($order->id);
        expect($order->isFullyDispatched())->toBeFalse();
    });

    it('isFullyDispatched returns true for an order with no items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);

        $order = OrderManagement::with('items.dispatches')->find($order->id);
        expect($order->isFullyDispatched())->toBeTrue();
    });

    it('totalOrderedQty sums all item quantities', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        mkOrderItem($order->id, $product->id, ['qty' => 5]);
        mkOrderItem($order->id, $product->id, ['qty' => 8]);

        $order = OrderManagement::with('items')->find($order->id);
        expect($order->totalOrderedQty())->toBe(13);
    });

    it('dispatchPercent returns 0 when no items', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);

        $order = OrderManagement::with('items.dispatches')->find($order->id);
        expect($order->dispatchPercent())->toBe(0);
    });

    it('dispatchPercent returns correct percentage when partially dispatched', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        $order = mkOrder($broker->id, $brand->id, $dealer->id);
        $item = mkOrderItem($order->id, $product->id, ['qty' => 10]);
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'no_of_bags' => 5,
            'dispatch_date' => '2026-01-15',
            'transport_id' => $transporter->id,
            'truck_number' => 'GJ01AA1234',
            'driver_contact' => '9876543210',
            'status' => 0,
            'accrued_late_fee' => 0,
        ]);

        $order = OrderManagement::with('items.dispatches')->find($order->id);
        expect($order->dispatchPercent())->toBe(50);
    });

    it('paymentBadge returns correct badge for each status', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);

        $unpaid = mkOrder($broker->id, $brand->id, $dealer->id, ['payment_status' => 'unpaid']);
        $paid = mkOrder($broker->id, $brand->id, $dealer->id, ['payment_status' => 'paid']);
        $partial = mkOrder($broker->id, $brand->id, $dealer->id, ['payment_status' => 'partial']);

        expect($unpaid->paymentBadge())->toContain('Unpaid')
            ->and($paid->paymentBadge())->toContain('Paid')
            ->and($partial->paymentBadge())->toContain('Partial');
    });

    it('weightedAvgUnitPrice divides grand_total by total ordered qty', function () {
        $brand = mkOrdBrand();
        $broker = mkOrdBroker();
        $dealer = mkOrdDealer($broker->id, $brand->id);
        $product = mkOrdProduct($brand->id);
        // grand_total=1000, qty=10 → avg=100
        $order = mkOrder($broker->id, $brand->id, $dealer->id, ['grand_total' => 1000]);
        mkOrderItem($order->id, $product->id, ['qty' => 10]);

        $order = OrderManagement::with('items')->find($order->id);
        expect($order->weightedAvgUnitPrice())->toBe(100.0);
    });
});
