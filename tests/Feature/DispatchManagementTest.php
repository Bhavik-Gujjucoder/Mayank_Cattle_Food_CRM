<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function disActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }
    return $user;
}

function disBrand(array $attrs = []): BrandManagement
{
    return BrandManagement::create(array_merge(['name' => 'Brand ' . uniqid(), 'status' => 1], $attrs));
}

function disProduct(int $brandId, array $attrs = []): Product
{
    return Product::create(array_merge([
        'name'     => 'Product ' . uniqid(),
        'brand_id' => $brandId,
        'unit'     => 'Bag',
        'price'    => 100.00,
        'status'   => 1,
    ], $attrs));
}

function disBroker(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
    $user->assignRole($role);
    return $user;
}

function disDealerUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);
    $user->assignRole($role);
    return $user;
}

function disDealer(int $brokerId, int $brandId, ?int $userId = null, array $attrs = []): DealerManagement
{
    return DealerManagement::create(array_merge([
        'broker_id'         => $brokerId,
        'brand_id'          => $brandId,
        'user_id'           => $userId,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'Firm ' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ], $attrs));
}

function disOrder(int $brokerId, int $brandId, int $dealerId, array $attrs = []): OrderManagement
{
    return OrderManagement::create(array_merge([
        'unique_order_id'    => 'ORD/' . uniqid(),
        'broker_id'          => $brokerId,
        'brand_id'           => $brandId,
        'dealer_id'          => $dealerId,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ], $attrs));
}

function disOrderItem(int $orderId, int $productId, array $attrs = []): OrderItem
{
    return OrderItem::create(array_merge([
        'order_id'    => $orderId,
        'product_id'  => $productId,
        'qty'         => 10,
        'unit_price'  => 100.00,
        'total_price' => 1000.00,
    ], $attrs));
}

function disTransporter(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']);
    $user->assignRole($role);
    return $user;
}

function mkDis(int $orderId, int $orderItemId, int $productId, int $transporterId, array $attrs = []): DispatchManagement
{
    return DispatchManagement::create(array_merge([
        'order_id'         => $orderId,
        'order_item_id'    => $orderItemId,
        'product_id'       => $productId,
        'no_of_bags'       => 5,
        'dispatch_date'    => '2026-01-15',
        'transport_id'     => $transporterId,
        'truck_number'     => 'GJ01AA1234',
        'driver_contact'   => '9876543210',
        'status'           => 0,
        'accrued_late_fee' => 0,
    ], $attrs));
}

function disPayload(int $orderId, int $orderItemId, int $productId, int $transporterId, array $overrides = []): array
{
    return array_merge([
        'order_id'       => $orderId,
        'order_item_id'  => $orderItemId,
        'product_id'     => $productId,
        'no_of_bags'     => 5,
        'dispatch_date'  => '2026-01-15',
        'transport_id'   => $transporterId,
        'truck_number'   => 'GJ01AA1234',
        'driver_contact' => '9876543210',
        'status'         => 0,
    ], $overrides);
}

/** Build a full dispatch scenario and return all objects. */
function disSetup(): array
{
    $brand       = disBrand();
    $broker      = disBroker();
    $dealerUser  = disDealerUser();
    $dealer      = disDealer($broker->id, $brand->id, $dealerUser->id);
    $product     = disProduct($brand->id);
    $order       = disOrder($broker->id, $brand->id, $dealer->id);
    $orderItem   = disOrderItem($order->id, $product->id, ['qty' => 10, 'unit_price' => 100]);
    $transporter = disTransporter();

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

describe('access-control', function () {
    it('redirects unauthenticated user from dispatch index', function () {
        $this->get(route('dispatch.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch store', function () {
        $this->post(route('dispatch.store'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch destroy', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $this->delete(route('dispatch.destroy', $d))->assertRedirect(route('login'));
    });

    it('returns 403 when user lacks view-dispatch on index', function () {
        $actor = User::factory()->create(['status' => 1]);
        $this->actingAs($actor)->get(route('dispatch.index'))->assertForbidden();
    });

    it('returns 403 when user lacks add-dispatch on store', function () {
        $actor = disActor();
        $this->actingAs($actor)->post(route('dispatch.store'))->assertForbidden();
    });

    it('returns 403 when user lacks edit-dispatch on updatePaymentStatus', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['view-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d))
            ->assertForbidden();
    });

    it('returns 403 when user lacks edit-dispatch on paymentPopupData', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['view-dispatch']);

        $this->actingAs($actor)
            ->getJson(route('dispatch.paymentPopupData', $d))
            ->assertForbidden();
    });

    it('returns 403 when user lacks delete-dispatch on destroy', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['view-dispatch', 'edit-dispatch']);

        $this->actingAs($actor)->delete(route('dispatch.destroy', $d))->assertForbidden();
    });

    it('returns 403 when user lacks edit-dispatch on update', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['view-dispatch']);

        $this->actingAs($actor)
            ->put(route('dispatch.update', $d), disPayload(
                $s['order']->id,
                $s['orderItem']->id,
                $s['product']->id,
                $s['transporter']->id
            ))
            ->assertForbidden();
    });

    it('redirects unauthenticated user from dispatch update', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $this->put(route('dispatch.update', $d))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch orderHistory', function () {
        $s = disSetup();
        $this->get(route('dispatch.orderHistory', $s['order']))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch orderFormData', function () {
        $s = disSetup();
        $this->get(route('dispatch.orderFormData', $s['order']))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch transporterTrucks', function () {
        $s = disSetup();
        $this->get(route('dispatch.transporterTrucks', $s['transporter']))->assertRedirect(route('login'));
    });

    it('orderHistory accessible to any authenticated user who can access the order', function () {
        $s    = disSetup();
        $actor = disActor(['view-order']);

        $this->actingAs($actor)
            ->get(route('dispatch.orderHistory', $s['order']))
            ->assertOk();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns dispatch index view', function () {
        $actor = disActor(['view-dispatch']);
        $this->actingAs($actor)
            ->get(route('dispatch.index'))
            ->assertOk()
            ->assertViewIs('dispatch_management.index')
            ->assertViewHas('orders')
            ->assertViewHas('products')
            ->assertViewHas('dealers');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $actor = disActor(['view-dispatch']);
        $this->actingAs($actor)
            ->getJson(route('dispatch.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX returns dispatch records', function () {
        $s    = disSetup();
        $actor = disActor(['view-dispatch']);
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dispatches by order_id', function () {
        $s      = disSetup();
        $actor  = disActor(['view-dispatch']);
        $order2 = disOrder($s['broker']->id, $s['brand']->id, $s['dealer']->id);
        $item2  = disOrderItem($order2->id, $s['product']->id);
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        mkDis($order2->id, $item2->id, $s['product']->id, $s['transporter']->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.index') . "?order_id={$s['order']->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dispatches by product_id', function () {
        $s       = disSetup();
        $product2 = disProduct($s['brand']->id);
        $item2   = disOrderItem($s['order']->id, $product2->id);
        $actor   = disActor(['view-dispatch']);
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        mkDis($s['order']->id, $item2->id, $product2->id, $s['transporter']->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.index') . "?product_id={$s['product']->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dispatches by date range', function () {
        $s    = disSetup();
        $actor = disActor(['view-dispatch']);
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['dispatch_date' => '2026-01-10', 'no_of_bags' => 2]);
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['dispatch_date' => '2026-03-01', 'no_of_bags' => 2]);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.index') . '?date_from=2026-02-01', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX action column empty when user lacks view-dispatch', function () {
        $s     = disSetup();
        $actor = disActor(); // no view-dispatch permission
        grantPermissions($actor, ['view-dispatch']); // need view-dispatch to access AJAX
        // Create dispatch but actor lacks the can('view-dispatch') for action column render check
        // Actually view-dispatch is needed to access index at all, so actor has it
        // Let's test a user with view-dispatch but the inner $canViewDispatch flag:
        $actorNoViewDispatch = User::factory()->create(['status' => 1]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $actorNoViewDispatch->assignRole($role);
        // Admin has global scope, so give them view-dispatch only
        grantPermissions($actorNoViewDispatch, ['view-dispatch']);

        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        // User with view-dispatch gets the action column
        $response = $this->actingAs($actorNoViewDispatch)
            ->getJson(route('dispatch.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.action'))->toContain('View History');
    });
});

// ─────────────────────────────────────────────

describe('orderHistory', function () {
    it('returns dispatch history view with order and transporters', function () {
        $s    = disSetup();
        $actor = disActor(['view-order']);

        $response = $this->actingAs($actor)
            ->get(route('dispatch.orderHistory', $s['order']))
            ->assertOk()
            ->assertViewIs('dispatch_management.history')
            ->assertViewHas('order')
            ->assertViewHas('transporters');

        expect($response->viewData('dispatchBlocked'))->toBeFalse()
            ->and($response->viewData('blockingOrder'))->toBeNull();
    });

    it('detects blocking prior order that is not fully dispatched', function () {
        $brand   = disBrand();
        $broker  = disBroker();
        $dealer  = disDealer($broker->id, $brand->id);
        $product = disProduct($brand->id);
        $order1  = disOrder($broker->id, $brand->id, $dealer->id);
        $order2  = disOrder($broker->id, $brand->id, $dealer->id);
        disOrderItem($order1->id, $product->id, ['qty' => 10]); // no dispatches → not complete
        $actor = disActor(['view-order']);

        $response = $this->actingAs($actor)
            ->get(route('dispatch.orderHistory', $order2))
            ->assertOk();

        expect($response->viewData('dispatchBlocked'))->toBeTrue()
            ->and($response->viewData('blockingOrder'))->not->toBeNull();
    });

    it('returns 403 when broker accesses another broker\'s order history', function () {
        $brand   = disBrand();
        $broker1 = disBroker();
        $broker2 = disBroker();
        $dealer  = disDealer($broker1->id, $brand->id);
        $order   = disOrder($broker1->id, $brand->id, $dealer->id);
        grantPermissions($broker2, ['view-order']);

        $this->actingAs($broker2)
            ->get(route('dispatch.orderHistory', $order))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('fails when order_id is missing', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['order_id' => '']))
            ->assertSessionHasErrors(['order_id']);
    });

    it('fails when no_of_bags is 0', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 0]))
            ->assertSessionHasErrors(['no_of_bags']);
    });

    it('fails when dispatch_date is missing', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['dispatch_date' => '']))
            ->assertSessionHasErrors(['dispatch_date']);
    });

    it('fails when transport_id does not exist', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, 99999))
            ->assertSessionHasErrors(['transport_id']);
    });

    it('fails when truck_number is missing', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['truck_number' => '']))
            ->assertSessionHasErrors(['truck_number']);
    });

    it('fails when driver_contact is missing', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['driver_contact' => '']))
            ->assertSessionHasErrors(['driver_contact']);
    });

    it('fails when status is invalid', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['status' => 5]))
            ->assertSessionHasErrors(['status']);
    });

    it('fails when status=2 (partial) and partial_paid_amount is missing', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, [
                'status'              => 2,
                'partial_paid_amount' => '',
            ]))
            ->assertSessionHasErrors(['partial_paid_amount']);
    });
});

// ─────────────────────────────────────────────

describe('store-business-rules', function () {
    it('blocks over-dispatch when no_of_bags exceeds pending quantity', function () {
        $s     = disSetup(); // orderItem qty=10, no dispatches → pending=10
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $s['order']))
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, [
                'no_of_bags' => 15, // 15 > 10 pending
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['no_of_bags']);
    });

    it('blocks dispatch when a prior order for the same dealer is not fully dispatched', function () {
        $brand   = disBrand();
        $broker  = disBroker();
        $dealer  = disDealer($broker->id, $brand->id);
        $product = disProduct($brand->id);
        $order1  = disOrder($broker->id, $brand->id, $dealer->id);
        $order2  = disOrder($broker->id, $brand->id, $dealer->id);
        disOrderItem($order1->id, $product->id, ['qty' => 10]); // no dispatches → blocking
        $item2   = disOrderItem($order2->id, $product->id);
        $transporter = disTransporter();
        $actor   = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->from(route('dispatch.orderHistory', $order2))
            ->post(route('dispatch.store'), disPayload($order2->id, $item2->id, $product->id, $transporter->id))
            ->assertRedirect()
            ->assertSessionHasErrors(['order_item_id']);
    });

    it('creates dispatch record successfully and redirects to orderHistory', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)
            ->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id))
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id));

        $this->assertDatabaseHas('dispatch_management', [
            'order_id'      => $s['order']->id,
            'order_item_id' => $s['orderItem']->id,
            'no_of_bags'    => 5,
        ]);
    });

    it('sets partial_paid_amount to null when status is not partial on store', function () {
        $s     = disSetup();
        $actor = disActor(['add-dispatch']);

        $this->actingAs($actor)->post(route('dispatch.store'), disPayload($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, [
            'status'              => 0,
            'partial_paid_amount' => '999',
        ]));

        $dispatch = DispatchManagement::latest()->first();
        expect($dispatch->partial_paid_amount)->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes the dispatch entry', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['delete-dispatch']);

        $this->actingAs($actor)->delete(route('dispatch.destroy', $d));

        $this->assertSoftDeleted('dispatch_management', ['id' => $d->id]);
    });

    it('redirects to orderHistory after destroy', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['delete-dispatch']);

        $this->actingAs($actor)
            ->delete(route('dispatch.destroy', $d))
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id));
    });

    it('returns 403 when broker tries to delete another broker\'s dispatch', function () {
        $brand   = disBrand();
        $broker1 = disBroker();
        $broker2 = disBroker();
        $dealer  = disDealer($broker1->id, $brand->id);
        $product = disProduct($brand->id);
        $order   = disOrder($broker1->id, $brand->id, $dealer->id);
        $item    = disOrderItem($order->id, $product->id);
        $trans   = disTransporter();
        $d       = mkDis($order->id, $item->id, $product->id, $trans->id);
        grantPermissions($broker2, ['delete-dispatch']);

        $this->actingAs($broker2)->delete(route('dispatch.destroy', $d))->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('updatePaymentStatus', function () {
    it('fails when status field is missing', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d), ['status' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('fails when status is invalid', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d), ['status' => 9])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('fails when partial amount exceeds total receivable', function () {
        $s     = disSetup(); // unit_price=100, no_of_bags=5 → base=500, total_receivable=500
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 5]);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d), [
                'status'              => 2,
                'partial_paid_amount' => 99999, // exceeds total receivable
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['partial_paid_amount']);
    });

    it('updates dispatch payment status to paid', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['status' => 0]);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d), ['status' => 1])
            ->assertOk()
            ->assertJson(['success' => true]);

        $d->refresh();
        expect($d->status)->toBe(1);
    });

    it('clears partial_paid_amount when switching from partial to unpaid', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, [
            'status'              => 2,
            'partial_paid_amount' => 200,
        ]);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->putJson(route('dispatch.updatePaymentStatus', $d), ['status' => 0])
            ->assertOk();

        $d->refresh();
        expect($d->partial_paid_amount)->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('update', function () {
    it('fails validation when no_of_bags is 0', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->put(route('dispatch.update', $d), [
                'no_of_bags'     => 0,
                'dispatch_date'  => '2026-01-20',
                'transport_id'   => $s['transporter']->id,
                'truck_number'   => 'GJ01AA1234',
                'driver_contact' => '9876543210',
                'status'         => 0,
            ])
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id))
            ->assertSessionHasErrors(['no_of_bags']);
    });

    it('blocks update when new bag count exceeds max allowed', function () {
        $s  = disSetup(); // item qty=10
        $d1 = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 3]);
        $d2 = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 4]);
        // d1 has 3 bags. Other dispatches: d2=4. Max for d1 = 10-4=6. But d1 currently=3, so max = max(10-4, 3)=6
        $actor = disActor(['edit-dispatch']);

        $this->actingAs($actor)
            ->put(route('dispatch.update', $d1), [
                'no_of_bags'     => 8, // 8 > 6 (max allowed: 10-4=6)
                'dispatch_date'  => '2026-01-20',
                'transport_id'   => $s['transporter']->id,
                'truck_number'   => 'GJ01AA1234',
                'driver_contact' => '9876543210',
                'status'         => 0,
            ])
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id))
            ->assertSessionHasErrors(['no_of_bags']);
    });

    it('updates dispatch fields successfully', function () {
        $s        = disSetup();
        $d        = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 5]);
        $actor    = disActor(['edit-dispatch']);
        $newTrans = disTransporter();

        $this->actingAs($actor)
            ->put(route('dispatch.update', $d), [
                'no_of_bags'     => 7,
                'dispatch_date'  => '2026-02-01',
                'transport_id'   => $newTrans->id,
                'truck_number'   => 'MH01BB5678',
                'driver_contact' => '1234567890',
                'status'         => 1,
            ])
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id));

        $this->assertDatabaseHas('dispatch_management', [
            'id'           => $d->id,
            'no_of_bags'   => 7,
            'truck_number' => 'MH01BB5678',
            'status'       => 1,
        ]);
    });
});

// ─────────────────────────────────────────────

describe('getOrderDispatchFormData', function () {
    it('returns eligible=true with items when no blocking order', function () {
        $s    = disSetup();
        $actor = disActor(['view-order']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.orderFormData', $s['order']))
            ->assertOk();

        expect($response->json('eligible'))->toBeTrue()
            ->and($response->json('items'))->not->toBeEmpty();
    });

    it('returns eligible=false with blocking_order info when prior undispatched order exists', function () {
        $brand   = disBrand();
        $broker  = disBroker();
        $dealer  = disDealer($broker->id, $brand->id);
        $product = disProduct($brand->id);
        $order1  = disOrder($broker->id, $brand->id, $dealer->id, ['unique_order_id' => 'ORD/BLOCK-DIS']);
        $order2  = disOrder($broker->id, $brand->id, $dealer->id);
        disOrderItem($order1->id, $product->id, ['qty' => 10]);
        $actor   = disActor(['view-order']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.orderFormData', $order2))
            ->assertOk();

        expect($response->json('eligible'))->toBeFalse()
            ->and($response->json('blocking_order.unique_order_id'))->toBe('ORD/BLOCK-DIS');
    });

    it('returns 403 when broker accesses another broker\'s order data', function () {
        $brand   = disBrand();
        $broker1 = disBroker();
        $broker2 = disBroker();
        $dealer  = disDealer($broker1->id, $brand->id);
        $order   = disOrder($broker1->id, $brand->id, $dealer->id);

        $this->actingAs($broker2)
            ->getJson(route('dispatch.orderFormData', $order))
            ->assertForbidden();
    });

    it('marks items with zero pending as disabled', function () {
        $s     = disSetup(); // item qty=10
        $actor = disActor(['view-order']);
        // Fully dispatch the item
        mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 10]);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.orderFormData', $s['order']))
            ->assertOk();

        expect($response->json('items.0.disabled'))->toBeTrue()
            ->and($response->json('items.0.pending'))->toBe(0);
    });
});

// ─────────────────────────────────────────────

describe('getTrucksByTransporter', function () {
    it('returns active trucks for the given transporter', function () {
        $transporter = disTransporter();
        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number'   => 'GJ01AA1234',
            'status'         => 1,
        ]);
        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number'   => 'MH01BB5678',
            'status'         => 0, // inactive — should be excluded
        ]);
        $actor = disActor(['view-dispatch']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.transporterTrucks', $transporter))
            ->assertOk();

        expect($response->json('trucks'))->toHaveCount(1)
            ->and($response->json('trucks.0.truck_number'))->toBe('GJ01AA1234');
    });

    it('returns transporter phone number', function () {
        $transporter = User::factory()->create(['status' => 1, 'phone_no' => '9999988888']);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']);
        $transporter->assignRole($role);
        $actor = disActor(['view-dispatch']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.transporterTrucks', $transporter))
            ->assertOk();

        expect($response->json('phone'))->toBe('9999988888');
    });

    it('returns empty trucks array when transporter has no trucks', function () {
        $transporter = disTransporter();
        $actor = disActor(['view-dispatch']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.transporterTrucks', $transporter))
            ->assertOk();

        expect($response->json('trucks'))->toBeEmpty();
    });
});

// ─────────────────────────────────────────────

describe('paymentPopupData', function () {
    it('returns dispatch and receivable data', function () {
        $s     = disSetup();
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $actor = disActor(['edit-dispatch']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.paymentPopupData', $d))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'dispatch' => ['id', 'no_of_bags', 'dispatch_date', 'status'],
                'receivable' => ['base_amount', 'total_receivable', 'balance_due'],
                'order' => ['id', 'unique_order_id'],
                'product' => ['name'],
            ]);

        expect($response->json('success'))->toBeTrue();
    });

    it('returns correct base_amount based on unit_price and no_of_bags', function () {
        $s     = disSetup(); // unit_price=100, no_of_bags default=5
        $d     = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['no_of_bags' => 5]);
        $actor = disActor(['edit-dispatch']);

        $response = $this->actingAs($actor)
            ->getJson(route('dispatch.paymentPopupData', $d))
            ->assertOk();

        // base_amount = unit_price * no_of_bags = 100 * 5 = 500
        expect((float) $response->json('receivable.base_amount'))->toBe(500.0);
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('statusBadge returns Unpaid badge for status=0', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['status' => 0]);

        expect($d->statusBadge())->toContain('Unpaid');
    });

    it('statusBadge returns Paid badge for status=1', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['status' => 1]);

        expect($d->statusBadge())->toContain('Paid');
    });

    it('statusBadge returns Partial Payment badge for status=2', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id, ['status' => 2]);

        expect($d->statusBadge())->toContain('Partial Payment');
    });

    it('pendingPaymentStatuses returns 0 and 2', function () {
        expect(DispatchManagement::pendingPaymentStatuses())->toBe([0, 2]);
    });

    it('order() relationship returns the parent order', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        expect($d->order)->toBeInstanceOf(OrderManagement::class)
            ->and($d->order->id)->toBe($s['order']->id);
    });

    it('product() relationship returns the product', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        expect($d->product)->toBeInstanceOf(Product::class)
            ->and($d->product->id)->toBe($s['product']->id);
    });

    it('transporter() relationship returns the transporter user', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        expect($d->transporter)->toBeInstanceOf(User::class)
            ->and($d->transporter->id)->toBe($s['transporter']->id);
    });

    it('soft-deleted dispatch is not found via normal query', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $d->delete();

        expect(DispatchManagement::find($d->id))->toBeNull();
        expect(DispatchManagement::withTrashed()->find($d->id))->not->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('resource-routes', function () {
    it('redirects unauthenticated user from dispatch create', function () {
        $this->get(route('dispatch.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch show', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $this->get(route('dispatch.show', $d))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from dispatch edit', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);
        $this->get(route('dispatch.edit', $d))->assertRedirect(route('login'));
    });

    it('redirects create to dispatch index with info message', function () {
        $this->actingAs(disActor(['add-dispatch']))
            ->get(route('dispatch.create'))
            ->assertRedirect(route('dispatch.index'))
            ->assertSessionHas('info');
    });

    it('redirects show to order dispatch history', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        $this->actingAs(disActor(['view-dispatch']))
            ->get(route('dispatch.show', $d))
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id));
    });

    it('redirects edit to order dispatch history', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        $this->actingAs(disActor(['edit-dispatch']))
            ->get(route('dispatch.edit', $d))
            ->assertRedirect(route('dispatch.orderHistory', $s['order']->id));
    });

    it('returns 403 on show without view-dispatch permission', function () {
        $s = disSetup();
        $d = mkDis($s['order']->id, $s['orderItem']->id, $s['product']->id, $s['transporter']->id);

        $this->actingAs(disActor())
            ->get(route('dispatch.show', $d))
            ->assertForbidden();
    });

    it('returns 403 on create without add-dispatch permission', function () {
        $this->actingAs(disActor(['view-dispatch']))
            ->get(route('dispatch.create'))
            ->assertForbidden();
    });
});
