<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Create an active user with the given role and a Sanctum Bearer token.
 * Returns [$user, $token].
 */
function orderListUser(string $roleName): array
{
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create([
        'status'   => 1,
        'email'    => $roleName . '.order.' . uniqid() . '@example.com',
        'password' => Hash::make('password123'),
    ]);

    $user->assignRole($roleName);

    $token = $user->createToken('test-device')->plainTextToken;

    return [$user, $token];
}

/**
 * Seed a minimal DealerManagement row linked to the given User.
 * Returns the DealerManagement record.
 */
function seedDealer(User $user, ?int $brokerId = null, ?int $brandId = null): DealerManagement
{
    return DealerManagement::create([
        'user_id'           => $user->id,
        'broker_id'         => $brokerId ?? User::factory()->create()->id,
        'brand_id'          => $brandId ?? BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1])->id,
        'code_no'           => 'TST-' . uniqid(),
        'applicant_name'    => $user->name,
        'firm_shop_name'    => 'Test Firm ' . uniqid(),
        'firm_shop_address' => '123 Test Street',
        'mobile_no'         => '9876543210',
        'pancard'           => 'ABCDE1234F',
    ]);
}

/**
 * Create a minimal OrderManagement row.
 */
function seedOrder(array $attrs = []): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1]);

    return OrderManagement::create(array_merge([
        'unique_order_id'    => 'ORD/TEST/' . uniqid(),
        'broker_id'          => User::factory()->create()->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => 1, // overridden by callers
        'order_date'         => now()->toDateString(),
        'delivery_address'   => '456 Delivery Lane',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ], $attrs));
}

/**
 * Create an OrderItem row for the given order.
 */
function seedOrderItem(OrderManagement $order, array $attrs = []): OrderItem
{
    return OrderItem::create(array_merge([
        'order_id'    => $order->id,
        'product_id'  => \App\Models\Product::create([
            'name'     => 'Product ' . uniqid(),
            'unit'     => 'Bag',
            'price'    => 100.00,
            'status'   => 1,
            'brand_id' => $order->brand_id,
        ])->id,
        'qty'         => 10,
        'unit_price'  => 100.00,
        'total_price' => 1000.00,
    ], $attrs));
}

/**
 * Create a DispatchManagement row for the given order + item.
 */
function seedDispatch(OrderManagement $order, OrderItem $item, array $attrs = []): DispatchManagement
{
    return DispatchManagement::create(array_merge([
        'order_id'      => $order->id,
        'order_item_id' => $item->id,
        'product_id'    => $item->product_id,
        'no_of_bags'    => 5,
        'dispatch_date' => now()->toDateString(),
        'transport_id'  => User::factory()->create()->id,
        'truck_number'  => 'GJ01AB1234',
        'driver_contact'=> '9999999999',
        'status'        => DispatchManagement::STATUS_UNPAID,
    ], $attrs));
}

// ─── Role-based access ────────────────────────────────────────────────────────

describe('GET /api/v1/orders — access control', function () {

    it('returns 401 when no Bearer token is provided', function () {
        getJson('/api/v1/orders')->assertUnauthorized();
    });

    it('returns 401 when an invalid Bearer token is provided', function () {
        getJson('/api/v1/orders', ['Authorization' => 'Bearer invalid-token'])
            ->assertUnauthorized();
    });

    it('returns 403 for users with the admin role', function () {
        [, $token] = orderListUser('admin');

        getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    });
});

// ─── Dealer access ────────────────────────────────────────────────────────────

describe('GET /api/v1/orders — dealer sees only own orders', function () {

    it('returns 200 with orders belonging to the dealer', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        $order = seedOrder(['dealer_id' => $dealerRecord->id]);
        seedOrderItem($order);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Orders retrieved successfully.']);

        expect($response->json('data.orders'))->toHaveCount(1)
            ->and($response->json('data.orders.0.id'))->toBe($order->id);
    });

    it('does not return orders belonging to a different dealer', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        seedDealer($dealerUser); // dealer has no orders

        // Order for a completely different dealer
        $otherDealer = seedDealer(User::factory()->create(['status' => 1]));
        seedOrder(['dealer_id' => $otherDealer->id]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toBeEmpty();
    });

    it('returns correct order structure with items and dispatch data', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        $order = seedOrder(['dealer_id' => $dealerRecord->id, 'payment_status' => 'partial', 'partial_paid_amount' => 500.00]);
        $item  = seedOrderItem($order, ['qty' => 10]);
        seedDispatch($order, $item, ['no_of_bags' => 4, 'status' => DispatchManagement::STATUS_PAID]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $orderData = $response->json('data.orders.0');

        expect($orderData)->toHaveKey('order_number')
            ->and($orderData)->toHaveKey('order_date')
            ->and($orderData)->toHaveKey('broker')
            ->and($orderData)->toHaveKey('brand')
            ->and($orderData)->toHaveKey('dealer')
            ->and($orderData)->toHaveKey('dispatch_summary')
            ->and($orderData)->toHaveKey('items')
            ->and($orderData['payment_status'])->toBe('partial');

        $summary = $orderData['dispatch_summary'];
        expect($summary['ordered_qty'])->toBe(10)
            ->and($summary['dispatched_qty'])->toBe(4)
            ->and($summary['pending_qty'])->toBe(6)
            ->and($summary['dispatch_percent'])->toBe(40)
            ->and($summary['is_fully_dispatched'])->toBeFalse();

        $item = $orderData['items'][0];
        expect($item['qty'])->toBe(10)
            ->and($item['dispatched_qty'])->toBe(4)
            ->and($item['pending_qty'])->toBe(6)
            ->and($item['dispatch_status'])->toBe('partial')
            ->and($item['dispatches'])->toHaveCount(1)
            ->and($item['dispatches'][0]['payment_status'])->toBe('paid');
    });

    it('returns dispatch_status=not_dispatched when no dispatches exist', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        $order = seedOrder(['dealer_id' => $dealerRecord->id]);
        seedOrderItem($order, ['qty' => 5]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders.0.items.0.dispatch_status'))->toBe('not_dispatched')
            ->and($response->json('data.orders.0.items.0.dispatched_qty'))->toBe(0)
            ->and($response->json('data.orders.0.dispatch_summary.is_fully_dispatched'))->toBeFalse();
    });

    it('returns dispatch_status=fully_dispatched when all bags are dispatched', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        $order = seedOrder(['dealer_id' => $dealerRecord->id]);
        $item  = seedOrderItem($order, ['qty' => 5]);
        seedDispatch($order, $item, ['no_of_bags' => 5]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders.0.items.0.dispatch_status'))->toBe('fully_dispatched')
            ->and($response->json('data.orders.0.dispatch_summary.is_fully_dispatched'))->toBeTrue()
            ->and($response->json('data.orders.0.dispatch_summary.dispatch_percent'))->toBe(100);
    });
});

// ─── Broker access ────────────────────────────────────────────────────────────

describe('GET /api/v1/orders — broker sees only own orders', function () {

    it('returns 200 with orders where broker_id matches the authenticated user', function () {
        [$brokerUser, $token] = orderListUser('broker');

        // Create a dealer linked to this broker
        $brand  = BrandManagement::create(['name' => 'BrokerBrand ' . uniqid(), 'status' => 1]);
        $dealer = DealerManagement::create([
            'user_id'           => null,
            'broker_id'         => $brokerUser->id,
            'brand_id'          => $brand->id,
            'code_no'           => 'BRK-' . uniqid(),
            'applicant_name'    => 'Broker Dealer',
            'firm_shop_name'    => 'Broker Shop',
            'firm_shop_address' => '789 Broker Road',
            'mobile_no'         => '9876543210',
            'pancard'           => 'ABCDE1234F',
        ]);

        $order = seedOrder(['broker_id' => $brokerUser->id, 'dealer_id' => $dealer->id, 'brand_id' => $brand->id]);
        seedOrderItem($order);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toHaveCount(1)
            ->and($response->json('data.orders.0.broker.id'))->toBe($brokerUser->id);
    });

    it('does not return orders belonging to a different broker', function () {
        [$brokerUser, $token] = orderListUser('broker');

        // Order for a completely different broker
        $otherBroker = User::factory()->create(['status' => 1]);
        seedOrder(['broker_id' => $otherBroker->id]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toBeEmpty();
    });
});

// ─── Filters ─────────────────────────────────────────────────────────────────

describe('GET /api/v1/orders — filters', function () {

    it('filters by payment_status=paid', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);

        seedOrder(['dealer_id' => $dealerRecord->id, 'payment_status' => 'paid',   'unique_order_id' => 'ORD/PAID/' . uniqid()]);
        seedOrder(['dealer_id' => $dealerRecord->id, 'payment_status' => 'unpaid', 'unique_order_id' => 'ORD/UNPD/' . uniqid()]);

        $response = getJson('/api/v1/orders?payment_status=paid', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $orders = $response->json('data.orders');
        expect($orders)->toHaveCount(1)
            ->and($orders[0]['payment_status'])->toBe('paid');
    });

    it('filters by order_number (partial match)', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);

        seedOrder(['dealer_id' => $dealerRecord->id, 'unique_order_id' => 'ORD/2025/0001']);
        seedOrder(['dealer_id' => $dealerRecord->id, 'unique_order_id' => 'ORD/2025/0002']);

        $response = getJson('/api/v1/orders?order_number=0001', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toHaveCount(1)
            ->and($response->json('data.orders.0.order_number'))->toBe('ORD/2025/0001');
    });

    it('filters by date range', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);

        seedOrder(['dealer_id' => $dealerRecord->id, 'order_date' => '2025-01-15', 'unique_order_id' => 'ORD/JAN/' . uniqid()]);
        seedOrder(['dealer_id' => $dealerRecord->id, 'order_date' => '2025-03-10', 'unique_order_id' => 'ORD/MAR/' . uniqid()]);
        seedOrder(['dealer_id' => $dealerRecord->id, 'order_date' => '2025-05-20', 'unique_order_id' => 'ORD/MAY/' . uniqid()]);

        $response = getJson('/api/v1/orders?date_from=2025-02-01&date_to=2025-04-30', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toHaveCount(1)
            ->and($response->json('data.orders.0.order_date'))->toBe('2025-03-10');
    });

    it('ignores brand_id filter for dealer users (already scoped)', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        seedOrder(['dealer_id' => $dealerRecord->id]);

        // Passing brand_id for a dealer user should be silently ignored
        $response = getJson('/api/v1/orders?brand_id=999', ['Authorization' => "Bearer $token"])
            ->assertOk();

        // Order still returned — the invalid brand_id filter was not applied for dealers.
        expect($response->json('data.orders'))->toHaveCount(1);
    });
});

// ─── Pagination ───────────────────────────────────────────────────────────────

describe('GET /api/v1/orders — pagination', function () {

    it('returns pagination metadata in the response', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);
        seedOrder(['dealer_id' => $dealerRecord->id]);

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'orders',
                    'pagination' => [
                        'current_page', 'per_page', 'total',
                        'last_page', 'next_page_url', 'prev_page_url',
                    ],
                ],
            ]);

        expect($response->json('data.pagination.current_page'))->toBe(1)
            ->and($response->json('data.pagination.total'))->toBe(1);
    });

    it('respects per_page parameter', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        $dealerRecord = seedDealer($dealerUser);

        foreach (range(1, 5) as $i) {
            seedOrder(['dealer_id' => $dealerRecord->id, 'unique_order_id' => "ORD/PG/$i"]);
        }

        $response = getJson('/api/v1/orders?per_page=2', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toHaveCount(2)
            ->and($response->json('data.pagination.per_page'))->toBe(2)
            ->and($response->json('data.pagination.total'))->toBe(5)
            ->and($response->json('data.pagination.last_page'))->toBe(3)
            ->and($response->json('data.pagination.next_page_url'))->not->toBeNull();
    });

    it('returns an empty orders array with correct pagination when there are no results', function () {
        [$dealerUser, $token] = orderListUser('dealer');
        seedDealer($dealerUser); // dealer has no orders

        $response = getJson('/api/v1/orders', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.orders'))->toBeEmpty()
            ->and($response->json('data.pagination.total'))->toBe(0)
            ->and($response->json('data.pagination.last_page'))->toBe(1);
    });
});

// ─── Validation ───────────────────────────────────────────────────────────────

describe('GET /api/v1/orders — validation', function () {

    it('returns 422 when payment_status is invalid', function () {
        [, $token] = orderListUser('dealer');

        getJson('/api/v1/orders?payment_status=invalid', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation failed.'])
            ->assertJsonStructure(['data' => ['payment_status']]);
    });

    it('returns 422 when date_to is before date_from', function () {
        [, $token] = orderListUser('dealer');

        getJson('/api/v1/orders?date_from=2025-05-01&date_to=2025-04-01', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['date_to']]);
    });

    it('returns 422 when per_page exceeds 100', function () {
        [, $token] = orderListUser('dealer');

        getJson('/api/v1/orders?per_page=999', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['per_page']]);
    });
});
