<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Support\SalesScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function ssAdmin(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    return $user;
}

function ssBroker(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));
    return $user;
}

function ssDealer(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));
    return $user;
}

function ssOrder(User $broker, ?User $dealerUser = null, array $attrs = []): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'Brand-SS-' . uniqid(), 'status' => 1]);

    if ($dealerUser === null) {
        $dealerUser = ssDealer();
    }
    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-SS-' . uniqid(),
        'firm_shop_name'    => 'Firm-SS-' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);

    return OrderManagement::create(array_merge([
        'unique_order_id'    => 'ORD-SS-' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000,
        'grand_total'        => 1000,
        'status'             => 1,
    ], $attrs));
}

// ─────────────────────────────────────────────

beforeEach(function () {
    Mail::fake();
    foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter', 'staff'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
    DB::table('general_settings')->insert([
        ['key' => 'payment_due_days',   'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'payment_due_amount', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

// ─────────────────────────────────────────────

describe('hasGlobalAccess', function () {
    it('returns true for admin role', function () {
        expect(SalesScope::hasGlobalAccess(ssAdmin()))->toBeTrue();
    });

    it('returns true for super admin role', function () {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']));
        expect(SalesScope::hasGlobalAccess($user))->toBeTrue();
    });

    it('returns false for broker role', function () {
        expect(SalesScope::hasGlobalAccess(ssBroker()))->toBeFalse();
    });

    it('returns false for dealer role', function () {
        expect(SalesScope::hasGlobalAccess(ssDealer()))->toBeFalse();
    });

    it('returns false when user is null', function () {
        expect(SalesScope::hasGlobalAccess(null))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('isBroker and isDealer', function () {
    it('isBroker returns true for broker user', function () {
        expect(SalesScope::isBroker(ssBroker()))->toBeTrue();
    });

    it('isBroker returns false for admin user', function () {
        expect(SalesScope::isBroker(ssAdmin()))->toBeFalse();
    });

    it('isBroker returns false for dealer user', function () {
        expect(SalesScope::isBroker(ssDealer()))->toBeFalse();
    });

    it('isDealer returns true for dealer user', function () {
        expect(SalesScope::isDealer(ssDealer()))->toBeTrue();
    });

    it('isDealer returns false for broker user', function () {
        expect(SalesScope::isDealer(ssBroker()))->toBeFalse();
    });

    it('isDealer returns false for admin user', function () {
        expect(SalesScope::isDealer(ssAdmin()))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('filter visibility flags', function () {
    it('showBrokerFilter returns true for admin', function () {
        expect(SalesScope::showBrokerFilter(ssAdmin()))->toBeTrue();
    });

    it('showBrokerFilter returns false for broker', function () {
        expect(SalesScope::showBrokerFilter(ssBroker()))->toBeFalse();
    });

    it('showBrokerFilter returns false for dealer', function () {
        expect(SalesScope::showBrokerFilter(ssDealer()))->toBeFalse();
    });

    it('showBrandFilter returns true for admin', function () {
        expect(SalesScope::showBrandFilter(ssAdmin()))->toBeTrue();
    });

    it('showBrandFilter returns false for dealer', function () {
        expect(SalesScope::showBrandFilter(ssDealer()))->toBeFalse();
    });

    it('showDealerFilter returns true for admin', function () {
        expect(SalesScope::showDealerFilter(ssAdmin()))->toBeTrue();
    });

    it('showDealerFilter returns false for dealer', function () {
        expect(SalesScope::showDealerFilter(ssDealer()))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('scopeOrders', function () {
    it('returns all orders for admin user', function () {
        $broker1 = ssBroker();
        $broker2 = ssBroker();
        ssOrder($broker1);
        ssOrder($broker2);

        $count = SalesScope::scopeOrders(OrderManagement::query(), ssAdmin())->count();

        expect($count)->toBe(2);
    });

    it('returns only broker orders for broker user', function () {
        $broker1 = ssBroker();
        $broker2 = ssBroker();
        ssOrder($broker1);
        ssOrder($broker2);

        $count = SalesScope::scopeOrders(OrderManagement::query(), $broker1)->count();

        expect($count)->toBe(1);
    });

    it('returns only dealer orders for dealer user', function () {
        $broker    = ssBroker();
        $dealer1   = ssDealer();
        $dealer2   = ssDealer();
        ssOrder($broker, $dealer1);
        ssOrder($broker, $dealer2);

        $count = SalesScope::scopeOrders(OrderManagement::query(), $dealer1)->count();

        expect($count)->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('scopeDispatches', function () {
    it('returns all dispatches for admin user', function () {
        $broker = ssBroker();
        $order  = ssOrder($broker);
        $brand  = BrandManagement::find($order->brand_id);
        $product = Product::create([
            'name' => 'Prod-SS-' . uniqid(), 'brand_id' => $brand->id,
            'unit' => 'Bag', 'price' => 100, 'status' => 1,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'qty' => 5, 'unit_price' => 100, 'total_price' => 500,
        ]);
        $transporter = User::factory()->create(['status' => 1]);
        $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

        Mail::fake();
        DispatchManagement::create([
            'order_id' => $order->id, 'order_item_id' => $orderItem->id,
            'product_id' => $product->id, 'no_of_bags' => 5,
            'dispatch_date' => '2026-01-01', 'transport_id' => $transporter->id,
            'truck_number' => 'GJ01SS0001', 'driver_contact' => '9999999999',
            'status' => DispatchManagement::STATUS_UNPAID,
        ]);

        $count = SalesScope::scopeDispatches(DispatchManagement::query(), ssAdmin())->count();
        expect($count)->toBe(1);
    });

    it('returns only broker dispatches for broker user', function () {
        $broker1 = ssBroker();
        $broker2 = ssBroker();
        $order1  = ssOrder($broker1);
        $order2  = ssOrder($broker2);

        $product1 = Product::create([
            'name' => 'Prod-SS1-' . uniqid(), 'brand_id' => $order1->brand_id,
            'unit' => 'Bag', 'price' => 100, 'status' => 1,
        ]);
        $product2 = Product::create([
            'name' => 'Prod-SS2-' . uniqid(), 'brand_id' => $order2->brand_id,
            'unit' => 'Bag', 'price' => 100, 'status' => 1,
        ]);
        $item1 = OrderItem::create([
            'order_id' => $order1->id, 'product_id' => $product1->id,
            'qty' => 5, 'unit_price' => 100, 'total_price' => 500,
        ]);
        $item2 = OrderItem::create([
            'order_id' => $order2->id, 'product_id' => $product2->id,
            'qty' => 5, 'unit_price' => 100, 'total_price' => 500,
        ]);
        $transporter = User::factory()->create(['status' => 1]);
        $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

        Mail::fake();
        DispatchManagement::create([
            'order_id' => $order1->id, 'order_item_id' => $item1->id,
            'product_id' => $product1->id, 'no_of_bags' => 5,
            'dispatch_date' => '2026-01-01', 'transport_id' => $transporter->id,
            'truck_number' => 'GJ01SS0002', 'driver_contact' => '9999999999',
            'status' => DispatchManagement::STATUS_UNPAID,
        ]);
        DispatchManagement::create([
            'order_id' => $order2->id, 'order_item_id' => $item2->id,
            'product_id' => $product2->id, 'no_of_bags' => 5,
            'dispatch_date' => '2026-01-01', 'transport_id' => $transporter->id,
            'truck_number' => 'GJ01SS0003', 'driver_contact' => '9999999999',
            'status' => DispatchManagement::STATUS_UNPAID,
        ]);

        $count = SalesScope::scopeDispatches(DispatchManagement::query(), $broker1)->count();
        expect($count)->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('userCanAccessOrder', function () {
    it('returns true for admin accessing any order', function () {
        $broker = ssBroker();
        $order  = ssOrder($broker);

        expect(SalesScope::userCanAccessOrder($order, ssAdmin()))->toBeTrue();
    });

    it('returns true for broker accessing their own order', function () {
        $broker = ssBroker();
        $order  = ssOrder($broker);

        expect(SalesScope::userCanAccessOrder($order, $broker))->toBeTrue();
    });

    it('returns false for broker accessing another broker order', function () {
        $broker1 = ssBroker();
        $broker2 = ssBroker();
        $order   = ssOrder($broker1);

        expect(SalesScope::userCanAccessOrder($order, $broker2))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('enforceOrderAssignment', function () {
    it('sets broker_id from authenticated broker user', function () {
        $broker   = ssBroker();
        Auth::login($broker);

        $validated = SalesScope::enforceOrderAssignment(['some_field' => 'value'], $broker);

        expect($validated['broker_id'])->toBe($broker->id);

        Auth::logout();
    });

    it('does not modify payload for admin user', function () {
        $admin     = ssAdmin();
        $validated = SalesScope::enforceOrderAssignment(['some_field' => 'value'], $admin);

        expect($validated)->not->toHaveKey('broker_id')
            ->and($validated)->not->toHaveKey('dealer_id');
    });
});
