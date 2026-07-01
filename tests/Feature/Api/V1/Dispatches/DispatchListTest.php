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

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Create an active user with the given role and a Sanctum Bearer token.
 * Uses a distinct name prefix to avoid redeclaration conflicts with other test files.
 *
 * @return array{0: User, 1: string}
 */
function dispatchUser(string $roleName): array
{
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create([
        'status'   => 1,
        'email'    => $roleName . '.disp.' . uniqid() . '@example.com',
        'password' => Hash::make('password123'),
    ]);

    $user->assignRole($roleName);

    $token = $user->createToken('test-device')->plainTextToken;

    return [$user, $token];
}

/**
 * Seed a minimal DealerManagement row linked to the given User.
 */
function dispatchDealer(User $user, ?int $brokerId = null, ?int $brandId = null): DealerManagement
{
    return DealerManagement::create([
        'user_id'           => $user->id,
        'broker_id'         => $brokerId ?? User::factory()->create()->id,
        'brand_id'          => $brandId  ?? BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1])->id,
        'code_no'           => 'TST-' . uniqid(),
        'applicant_name'    => $user->name,
        'firm_shop_name'    => 'Test Firm ' . uniqid(),
        'firm_shop_address' => '123 Test Street',
        'mobile_no'         => '9876543210',
        'pancard'           => 'ABCDE1234F',
    ]);
}

/**
 * Seed a minimal OrderManagement row.
 */
function dispatchOrder(array $attrs = []): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1]);

    return OrderManagement::create(array_merge([
        'unique_order_id'    => 'ORD/DISP/' . uniqid(),
        'broker_id'          => User::factory()->create()->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => 1,
        'order_date'         => now()->toDateString(),
        'delivery_address'   => '456 Delivery Lane',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ], $attrs));
}

/**
 * Seed a minimal OrderItem for the given order.
 */
function dispatchItem(OrderManagement $order, array $attrs = []): OrderItem
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
 * Seed a DispatchManagement record for the given order and item.
 */
function dispatchRecord(OrderManagement $order, OrderItem $item, array $attrs = []): DispatchManagement
{
    return DispatchManagement::create(array_merge([
        'order_id'       => $order->id,
        'order_item_id'  => $item->id,
        'product_id'     => $item->product_id,
        'no_of_bags'     => 5,
        'dispatch_date'  => now()->toDateString(),
        'transport_id'   => User::factory()->create()->id,
        'truck_number'   => 'GJ01AB1234',
        'driver_contact' => '9999999999',
        'status'         => DispatchManagement::STATUS_UNPAID,
    ], $attrs));
}

// ─── Access control ───────────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — access control', function () {

    it('returns 401 when no Bearer token is provided', function () {
        getJson('/api/v1/dispatches')->assertUnauthorized();
    });

    it('returns 401 when an invalid Bearer token is provided', function () {
        getJson('/api/v1/dispatches', ['Authorization' => 'Bearer bad-token'])
            ->assertUnauthorized();
    });

    it('returns 403 for a user with the admin role', function () {
        [, $token] = dispatchUser('admin');

        getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    });
});

// ─── Dealer visibility ────────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — dealer sees only own dispatches', function () {

    it('returns 200 with dispatches for the authenticated dealer', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        $dispatch     = dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Dispatches retrieved successfully.']);

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispatch->id);
    });

    it('does not return dispatches belonging to a different dealer', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        dispatchDealer($dealerUser); // dealer has no orders

        // Dispatch for a completely different dealer
        $otherUser   = User::factory()->create(['status' => 1]);
        $otherDealer = dispatchDealer($otherUser);
        $order       = dispatchOrder(['dealer_id' => $otherDealer->id]);
        $item        = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});

// ─── Broker visibility ────────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — broker sees only own dispatches', function () {

    it('returns dispatches for orders owned by the broker', function () {
        [$brokerUser, $token] = dispatchUser('broker');

        // Order where broker_id = this broker
        $order    = dispatchOrder(['broker_id' => $brokerUser->id]);
        $item     = dispatchItem($order);
        $dispatch = dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispatch->id);
    });

    it('does not return dispatches from another broker\'s orders', function () {
        [$brokerUser, $token] = dispatchUser('broker');

        // Order belonging to a different broker
        $otherBroker = User::factory()->create(['status' => 1]);
        $order       = dispatchOrder(['broker_id' => $otherBroker->id]);
        $item        = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});

// ─── Response structure ───────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — response structure', function () {

    it('returns expected dispatch fields', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        dispatchRecord($order, $item, [
            'no_of_bags'    => 7,
            'truck_number'  => 'MH12CD5678',
            'driver_contact'=> '8888888888',
            'status'        => DispatchManagement::STATUS_PAID,
        ]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $d = $response->json('data.dispatches.0');

        expect($d)->toHaveKeys([
            'id', 'dispatch_date', 'no_of_bags', 'payment_status',
            'partial_paid_amount', 'accrued_late_fee', 'late_fee_last_accrued_on',
            'truck_number', 'driver_contact',
            'order', 'product', 'transporter', 'created_at',
        ])
            ->and($d['no_of_bags'])->toBe(7)
            ->and($d['payment_status'])->toBe('paid')
            ->and($d['truck_number'])->toBe('MH12CD5678')
            ->and($d['driver_contact'])->toBe('8888888888');
    });

    it('returns correct nested order structure', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $orderData = $response->json('data.dispatches.0.order');

        expect($orderData)->toHaveKeys(['id', 'order_number', 'order_date', 'brand', 'dealer'])
            ->and($orderData['id'])->toBe($order->id)
            ->and($orderData['order_number'])->toBe($order->unique_order_id)
            ->and($orderData['brand'])->toHaveKeys(['id', 'name'])
            ->and($orderData['dealer'])->toHaveKeys(['id', 'firm_shop_name', 'user_name']);
    });

    it('returns correct product and transporter fields', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $d = $response->json('data.dispatches.0');

        expect($d['product'])->toHaveKeys(['id', 'name', 'unit'])
            ->and($d['transporter'])->toHaveKeys(['id', 'name', 'phone_no']);
    });

    it('maps payment_status integer to string correctly', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item1  = dispatchItem($order1);
        dispatchRecord($order1, $item1, ['status' => DispatchManagement::STATUS_UNPAID]);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2, ['status' => DispatchManagement::STATUS_PAID]);

        $order3 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item3  = dispatchItem($order3);
        dispatchRecord($order3, $item3, [
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 200.00,
        ]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $statuses = collect($response->json('data.dispatches'))->pluck('payment_status')->sort()->values()->all();

        expect($statuses)->toContain('unpaid')
            ->toContain('paid')
            ->toContain('partial');
    });

    it('returns pagination metadata', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.pagination'))->toHaveKeys([
            'current_page', 'per_page', 'total', 'last_page',
            'next_page_url', 'prev_page_url',
        ]);
    });
});

// ─── Filters ─────────────────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — filters', function () {

    it('filters by order_number (partial match)', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id, 'unique_order_id' => 'ORD/ALPHA/001']);
        $item1  = dispatchItem($order1);
        dispatchRecord($order1, $item1);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id, 'unique_order_id' => 'ORD/BETA/002']);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2);

        $response = getJson('/api/v1/dispatches?order_number=ALPHA', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.order.order_number'))->toBe('ORD/ALPHA/001');
    });

    it('filters by status=paid', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item1  = dispatchItem($order1);
        dispatchRecord($order1, $item1, ['status' => DispatchManagement::STATUS_PAID]);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2, ['status' => DispatchManagement::STATUS_UNPAID]);

        $response = getJson('/api/v1/dispatches?status=paid', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.payment_status'))->toBe('paid');
    });

    it('filters by date_from and date_to', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item1  = dispatchItem($order1);
        dispatchRecord($order1, $item1, ['dispatch_date' => '2025-01-15']);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2, ['dispatch_date' => '2025-03-20']);

        $response = getJson(
            '/api/v1/dispatches?date_from=2025-03-01&date_to=2025-03-31',
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.dispatch_date'))->toBe('2025-03-20');
    });

    it('filters by product_id', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item1  = dispatchItem($order1);
        $d1     = dispatchRecord($order1, $item1);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2);

        $response = getJson(
            '/api/v1/dispatches?product_id=' . $d1->product_id,
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });

    it('filters by dealer_id for a broker', function () {
        [$brokerUser, $token] = dispatchUser('broker');

        $dealerUserA = User::factory()->create(['status' => 1]);
        $dealerA     = dispatchDealer($dealerUserA, $brokerUser->id);
        $orderA      = dispatchOrder(['broker_id' => $brokerUser->id, 'dealer_id' => $dealerA->id]);
        $itemA       = dispatchItem($orderA);
        $dispatchA   = dispatchRecord($orderA, $itemA);

        $dealerUserB = User::factory()->create(['status' => 1]);
        $dealerB     = dispatchDealer($dealerUserB, $brokerUser->id);
        $orderB      = dispatchOrder(['broker_id' => $brokerUser->id, 'dealer_id' => $dealerB->id]);
        $itemB       = dispatchItem($orderB);
        dispatchRecord($orderB, $itemB);

        $response = getJson(
            '/api/v1/dispatches?dealer_id=' . $dealerA->id,
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispatchA->id);
    });

    it('ignores dealer_id filter for dealer role', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item  = dispatchItem($order);
        dispatchRecord($order, $item);

        // Passing a non-existent dealer_id should be silently ignored for dealers
        $response = getJson('/api/v1/dispatches?dealer_id=99999', ['Authorization' => "Bearer $token"])
            ->assertOk();

        // Dealer's own dispatch still visible — the filter was ignored
        expect($response->json('data.dispatches'))->toHaveCount(1);
    });

    it('filters by brand_id for a broker', function () {
        [$brokerUser, $token] = dispatchUser('broker');

        $brand1 = BrandManagement::create(['name' => 'Brand X ' . uniqid(), 'status' => 1]);
        $brand2 = BrandManagement::create(['name' => 'Brand Y ' . uniqid(), 'status' => 1]);

        $order1 = dispatchOrder(['broker_id' => $brokerUser->id, 'brand_id' => $brand1->id]);
        $item1  = dispatchItem($order1);
        $d1     = dispatchRecord($order1, $item1);

        $order2 = dispatchOrder(['broker_id' => $brokerUser->id, 'brand_id' => $brand2->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2);

        $response = getJson(
            '/api/v1/dispatches?brand_id=' . $brand1->id,
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });
});

// ─── Pagination & validation ──────────────────────────────────────────────────

describe('GET /api/v1/dispatches — pagination & validation', function () {

    it('respects the per_page parameter', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        for ($i = 0; $i < 5; $i++) {
            $order = dispatchOrder(['dealer_id' => $dealerRecord->id]);
            $item  = dispatchItem($order);
            dispatchRecord($order, $item);
        }

        $response = getJson('/api/v1/dispatches?per_page=2', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(2)
            ->and($response->json('data.pagination.per_page'))->toBe(2)
            ->and($response->json('data.pagination.total'))->toBe(5);
    });

    it('returns 422 when per_page exceeds 100', function () {
        [, $token] = dispatchUser('dealer');

        getJson('/api/v1/dispatches?per_page=999', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation failed.'])
            ->assertJsonStructure(['data' => ['per_page']]);
    });

    it('returns 422 when date_to is before date_from', function () {
        [, $token] = dispatchUser('dealer');

        getJson(
            '/api/v1/dispatches?date_from=2025-03-20&date_to=2025-03-01',
            ['Authorization' => "Bearer $token"]
        )
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['date_to']]);
    });

    it('returns 422 for invalid status value', function () {
        [, $token] = dispatchUser('dealer');

        getJson('/api/v1/dispatches?status=invalid', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['status']]);
    });
});

// ─── API 9 enhanced fields ────────────────────────────────────────────────────

describe('GET /api/v1/dispatches — API 9 enhanced fields', function () {

    it('returns dispatch_number formatted as DISP-XXXXXX', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        $d            = dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $dispatchNumber = $response->json('data.dispatches.0.dispatch_number');
        expect($dispatchNumber)->toBe('DISP-' . str_pad((string) $d->id, 6, '0', STR_PAD_LEFT));
    });

    it('returns broker info nested inside the order object', function () {
        [$brokerUser, $token] = dispatchUser('broker');

        $order = dispatchOrder(['broker_id' => $brokerUser->id]);
        $item  = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $broker = $response->json('data.dispatches.0.order.broker');

        expect($broker)->not->toBeNull()
            ->and($broker['id'])->toBe($brokerUser->id)
            ->and($broker['name'])->toBe($brokerUser->name);
    });

    it('returns ordered_qty, total_dispatched_qty, and pending_qty for an incomplete item', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);

        // Order item with qty=10; dispatch only 3 bags so 7 remain pending.
        $item = dispatchItem($order, ['qty' => 10]);
        dispatchRecord($order, $item, ['no_of_bags' => 3]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $d = $response->json('data.dispatches.0');

        expect($d['ordered_qty'])->toBe(10)
            ->and($d['total_dispatched_qty'])->toBe(3)
            ->and($d['pending_qty'])->toBe(7)
            ->and($d['is_item_complete'])->toBeFalse();
    });

    it('sums total_dispatched_qty across all dispatch events for the same order item', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);

        // Same item dispatched in two separate events (3 + 4 = 7 bags total).
        $item = dispatchItem($order, ['qty' => 10]);
        dispatchRecord($order, $item, ['no_of_bags' => 3]);
        dispatchRecord($order, $item, ['no_of_bags' => 4]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $dispatches = $response->json('data.dispatches');
        expect($dispatches)->toHaveCount(2);

        // Both dispatch records see the same item-level totals.
        foreach ($dispatches as $d) {
            expect($d['ordered_qty'])->toBe(10)
                ->and($d['total_dispatched_qty'])->toBe(7)
                ->and($d['pending_qty'])->toBe(3);
        }
    });

    it('marks is_item_complete true when all bags are dispatched', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);

        // Dispatch exactly the ordered qty.
        $item = dispatchItem($order, ['qty' => 5]);
        dispatchRecord($order, $item, ['no_of_bags' => 5]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches.0.is_item_complete'))->toBeTrue()
            ->and($response->json('data.dispatches.0.pending_qty'))->toBe(0);
    });

    it('returns unit_price inside the product object', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order, ['unit_price' => 250.00]);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches.0.product.unit_price'))->toBe('250.00');
    });

    it('returns updated_at field on each dispatch record', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item         = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches.0.updated_at'))->not->toBeNull();
    });

    it('returns delivery_address inside the order object', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder([
            'dealer_id'        => $dealerRecord->id,
            'delivery_address' => '42 Farm Road, Ahmedabad',
        ]);
        $item = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches.0.order.delivery_address'))
            ->toBe('42 Farm Road, Ahmedabad');
    });

    it('returns payment_receivable block with correct financial summary', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);

        // 8 bags @ ₹150 each → base_amount = 1200, status unpaid → balance_due = 1200.
        $item = dispatchItem($order, ['qty' => 10, 'unit_price' => 150.00]);
        dispatchRecord($order, $item, ['no_of_bags' => 8, 'status' => DispatchManagement::STATUS_UNPAID]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $pr = $response->json('data.dispatches.0.payment_receivable');

        expect($pr)->toHaveKeys(['base_amount', 'total_receivable', 'amount_paid', 'balance_due'])
            ->and($pr['base_amount'])->toBe('1200.00')
            ->and($pr['total_receivable'])->toBe('1200.00')
            ->and($pr['amount_paid'])->toBe('0.00')
            ->and($pr['balance_due'])->toBe('1200.00');
    });

    it('sets amount_paid equal to total_receivable and balance_due to 0.00 for a fully paid dispatch', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);
        $order        = dispatchOrder(['dealer_id' => $dealerRecord->id]);

        $item = dispatchItem($order, ['qty' => 5, 'unit_price' => 200.00]);
        dispatchRecord($order, $item, ['no_of_bags' => 5, 'status' => DispatchManagement::STATUS_PAID]);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $pr = $response->json('data.dispatches.0.payment_receivable');

        // base = 5 × 200 = 1000; paid in full → amount_paid = 1000, balance = 0.
        expect($pr['base_amount'])->toBe('1000.00')
            ->and($pr['amount_paid'])->toBe('1000.00')
            ->and($pr['balance_due'])->toBe('0.00');
    });
});

// ─── Dispatch number filter ───────────────────────────────────────────────────

describe('GET /api/v1/dispatches — dispatch_number filter', function () {

    it('filters by dispatch_number in DISP-XXXXXX format', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order1 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item1  = dispatchItem($order1);
        $d1     = dispatchRecord($order1, $item1);

        $order2 = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item2  = dispatchItem($order2);
        dispatchRecord($order2, $item2);

        $dispatchNumber = 'DISP-' . str_pad((string) $d1->id, 6, '0', STR_PAD_LEFT);

        $response = getJson(
            '/api/v1/dispatches?dispatch_number=' . $dispatchNumber,
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });

    it('filters by dispatch_number as a plain integer string', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item  = dispatchItem($order);
        $d     = dispatchRecord($order, $item);

        $response = getJson(
            '/api/v1/dispatches?dispatch_number=' . $d->id,
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d->id);
    });

    it('returns empty list when dispatch_number matches no record', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        $dealerRecord = dispatchDealer($dealerUser);

        $order = dispatchOrder(['dealer_id' => $dealerRecord->id]);
        $item  = dispatchItem($order);
        dispatchRecord($order, $item);

        $response = getJson(
            '/api/v1/dispatches?dispatch_number=DISP-999999',
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });

    it('does not expose another dealer\'s dispatch even when dispatch_number matches', function () {
        [$dealerUser, $token] = dispatchUser('dealer');
        dispatchDealer($dealerUser); // this dealer has no dispatches

        // Dispatch belonging to a different dealer.
        $otherUser   = User::factory()->create(['status' => 1]);
        $otherDealer = dispatchDealer($otherUser);
        $order       = dispatchOrder(['dealer_id' => $otherDealer->id]);
        $item        = dispatchItem($order);
        $d           = dispatchRecord($order, $item);

        $response = getJson(
            '/api/v1/dispatches?dispatch_number=DISP-' . str_pad((string) $d->id, 6, '0', STR_PAD_LEFT),
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        // The scoped query must return nothing — the ID exists but belongs to another dealer.
        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});
