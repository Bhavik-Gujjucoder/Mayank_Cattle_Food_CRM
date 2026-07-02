<?php

/**
 * Feature tests for API 10 — Dispatch Listing.
 *
 * Endpoint : GET /api/v1/dispatches
 * Controller: App\Http\Controllers\Api\V1\Dispatches\DispatchListingController
 *
 * These tests are standalone and independent. They cover:
 *   • Authentication (401 without token, 401 with bad token)
 *   • Authorization (403 for non-Dealer/Broker roles)
 *   • Role-based data visibility (Dealer isolation, Broker isolation)
 *   • Complete response structure and field values
 *   • All supported filters (dispatch_number, order_number, status,
 *     date_from, date_to, product_id, brand_id, dealer_id)
 *   • Pagination (per_page, page, metadata fields)
 *   • Input validation (per_page max, date range ordering, invalid status)
 *   • Payment receivable summary block
 *   • Delivery address in order object
 *   • Quantity context (ordered_qty, total_dispatched_qty, pending_qty)
 */

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

// ─── Test-data helpers ─────────────────────────────────────────────────────────
// Prefixed "listing" to avoid conflicts with helpers in other test files.

/**
 * Create a User with the given Spatie role and a fresh Sanctum token.
 *
 * @return array{0: User, 1: string}  [user, plainTextToken]
 */
function listingUser(string $roleName): array
{
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create([
        'status'   => 1,
        'email'    => $roleName . '.listing.' . uniqid() . '@example.com',
        'password' => Hash::make('password123'),
    ]);

    $user->assignRole($roleName);

    $token = $user->createToken('mobile-listing-test')->plainTextToken;

    return [$user, $token];
}

/**
 * Create a minimal DealerManagement record linked to the given User.
 */
function listingDealer(User $user, ?int $brokerId = null, ?int $brandId = null): DealerManagement
{
    return DealerManagement::create([
        'user_id'           => $user->id,
        'broker_id'         => $brokerId ?? User::factory()->create(['status' => 1])->id,
        'brand_id'          => $brandId  ?? BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1])->id,
        'code_no'           => 'LIST-' . uniqid(),
        'applicant_name'    => $user->name,
        'firm_shop_name'    => 'Listing Firm ' . uniqid(),
        'firm_shop_address' => '1 Test Street',
        'mobile_no'         => '9876543210',
        'pancard'           => 'ABCDE1234F',
    ]);
}

/**
 * Create a minimal OrderManagement row.
 *
 * @param  array<string, mixed>  $attrs
 */
function listingOrder(array $attrs = []): OrderManagement
{
    $brand = BrandManagement::create(['name' => 'Brand ' . uniqid(), 'status' => 1]);

    return OrderManagement::create(array_merge([
        'unique_order_id'    => 'ORD/LIST/' . uniqid(),
        'broker_id'          => User::factory()->create(['status' => 1])->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => 1,
        'order_date'         => now()->toDateString(),
        'delivery_address'   => '99 Test Lane, Surat',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ], $attrs));
}

/**
 * Create a minimal OrderItem for the given order.
 *
 * @param  array<string, mixed>  $attrs
 */
function listingItem(OrderManagement $order, array $attrs = []): OrderItem
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
 * Create a minimal DispatchManagement record.
 *
 * @param  array<string, mixed>  $attrs
 */
function listingDispatch(OrderManagement $order, OrderItem $item, array $attrs = []): DispatchManagement
{
    return DispatchManagement::create(array_merge([
        'order_id'       => $order->id,
        'order_item_id'  => $item->id,
        'product_id'     => $item->product_id,
        'no_of_bags'     => 5,
        'dispatch_date'  => now()->toDateString(),
        'transport_id'   => User::factory()->create(['status' => 1])->id,
        'truck_number'   => 'GJ01AB1234',
        'driver_contact' => '9999999999',
        'status'         => DispatchManagement::STATUS_UNPAID,
    ], $attrs));
}

// ─── 1. Authentication ────────────────────────────────────────────────────────

describe('Dispatch Listing — authentication', function () {

    it('returns 401 when no Bearer token is provided', function () {
        getJson('/api/v1/dispatches')->assertUnauthorized();
    });

    it('returns 401 when an invalid Bearer token is provided', function () {
        getJson('/api/v1/dispatches', ['Authorization' => 'Bearer invalid-token-here'])
            ->assertUnauthorized();
    });
});

// ─── 2. Authorization ─────────────────────────────────────────────────────────

describe('Dispatch Listing — authorization', function () {

    it('returns 403 for a user with the admin role', function () {
        [, $token] = listingUser('admin');

        getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    });

    it('returns 403 for a user with the staff role', function () {
        [, $token] = listingUser('staff');

        getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    });

    it('allows a user with the dealer role', function () {
        [$dealerUser, $token] = listingUser('dealer');
        listingDealer($dealerUser);

        getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('allows a user with the broker role', function () {
        [, $token] = listingUser('broker');

        getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ─── 3. Dealer data isolation ─────────────────────────────────────────────────

describe('Dispatch Listing — dealer sees only own records', function () {

    it('returns the authenticated dealer\'s dispatches', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer   = listingDealer($dealerUser);
        $order    = listingOrder(['dealer_id' => $dealer->id]);
        $item     = listingItem($order);
        $dispatch = listingDispatch($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Dispatch listing retrieved successfully.']);

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispatch->id);
    });

    it('does not return dispatches belonging to a different dealer', function () {
        [$dealerUser, $token] = listingUser('dealer');
        listingDealer($dealerUser); // this dealer has no orders

        // Another dealer's dispatch.
        $otherUser   = User::factory()->create(['status' => 1]);
        $otherDealer = listingDealer($otherUser);
        $order       = listingOrder(['dealer_id' => $otherDealer->id]);
        $item        = listingItem($order);
        listingDispatch($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});

// ─── 4. Broker data isolation ─────────────────────────────────────────────────

describe('Dispatch Listing — broker sees only own records', function () {

    it('returns dispatches for orders managed by the authenticated broker', function () {
        [$brokerUser, $token] = listingUser('broker');

        $order    = listingOrder(['broker_id' => $brokerUser->id]);
        $item     = listingItem($order);
        $dispatch = listingDispatch($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispatch->id);
    });

    it('does not return dispatches from another broker\'s orders', function () {
        [$brokerUser, $token] = listingUser('broker');

        // Dispatch for a different broker.
        $otherBroker = User::factory()->create(['status' => 1]);
        $order       = listingOrder(['broker_id' => $otherBroker->id]);
        $item        = listingItem($order);
        listingDispatch($order, $item);

        $response = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});

// ─── 5. Response structure ────────────────────────────────────────────────────

describe('Dispatch Listing — response structure', function () {

    it('returns all expected top-level dispatch fields', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order);
        listingDispatch($order, $item, [
            'no_of_bags'     => 6,
            'truck_number'   => 'MH12CD5678',
            'driver_contact' => '8888888888',
            'status'         => DispatchManagement::STATUS_PAID,
        ]);

        $d = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0');

        expect($d)->toHaveKeys([
            'id', 'dispatch_number', 'dispatch_date',
            'no_of_bags', 'ordered_qty', 'total_dispatched_qty', 'pending_qty', 'is_item_complete',
            'payment_status', 'partial_paid_amount', 'accrued_late_fee', 'late_fee_last_accrued_on',
            'payment_receivable',
            'truck_number', 'driver_contact',
            'order', 'product', 'transporter',
            'created_at', 'updated_at',
        ])
            ->and($d['no_of_bags'])->toBe(6)
            ->and($d['payment_status'])->toBe('paid')
            ->and($d['truck_number'])->toBe('MH12CD5678')
            ->and($d['driver_contact'])->toBe('8888888888');
    });

    it('formats dispatch_number as DISP-XXXXXX (6-digit padded)', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer   = listingDealer($dealerUser);
        $order    = listingOrder(['dealer_id' => $dealer->id]);
        $item     = listingItem($order);
        $dispatch = listingDispatch($order, $item);

        $number = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.dispatch_number');

        expect($number)->toBe('DISP-' . str_pad((string) $dispatch->id, 6, '0', STR_PAD_LEFT));
    });

    it('returns the correct nested order object', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder([
            'dealer_id'        => $dealer->id,
            'delivery_address' => '10 Farm Road, Ahmedabad',
        ]);
        $item = listingItem($order);
        listingDispatch($order, $item);

        $orderData = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.order');

        expect($orderData)->toHaveKeys(['id', 'order_number', 'order_date', 'delivery_address', 'broker', 'brand', 'dealer'])
            ->and($orderData['id'])->toBe($order->id)
            ->and($orderData['order_number'])->toBe($order->unique_order_id)
            ->and($orderData['delivery_address'])->toBe('10 Farm Road, Ahmedabad')
            ->and($orderData['brand'])->toHaveKeys(['id', 'name'])
            ->and($orderData['dealer'])->toHaveKeys(['id', 'firm_shop_name', 'user_name']);
    });

    it('returns broker info inside the order object for broker-owned orders', function () {
        [$brokerUser, $token] = listingUser('broker');

        $order = listingOrder(['broker_id' => $brokerUser->id]);
        $item  = listingItem($order);
        listingDispatch($order, $item);

        $broker = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.order.broker');

        expect($broker)->not->toBeNull()
            ->and($broker['id'])->toBe($brokerUser->id)
            ->and($broker['name'])->toBe($brokerUser->name);
    });

    it('returns product details including unit_price from the order item', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order, ['unit_price' => 175.50]);
        listingDispatch($order, $item);

        $product = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.product');

        expect($product)->toHaveKeys(['id', 'name', 'unit', 'unit_price'])
            ->and($product['unit_price'])->toBe('175.50');
    });

    it('returns transporter details', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order);
        listingDispatch($order, $item);

        $transporter = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.transporter');

        expect($transporter)->toHaveKeys(['id', 'name', 'phone_no']);
    });

    it('maps payment_status DB integers to human-readable strings', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o1, listingItem($o1), ['status' => DispatchManagement::STATUS_UNPAID]);

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2), ['status' => DispatchManagement::STATUS_PAID]);

        $o3 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o3, listingItem($o3), [
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 300.00,
        ]);

        $statuses = collect(
            getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
                ->assertOk()
                ->json('data.dispatches')
        )->pluck('payment_status')->sort()->values()->all();

        expect($statuses)->toContain('unpaid')->toContain('paid')->toContain('partial');
    });

    it('returns non-null updated_at on every dispatch record', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($order, listingItem($order));

        $updatedAt = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.updated_at');

        expect($updatedAt)->not->toBeNull();
    });
});

// ─── 6. Quantity context ──────────────────────────────────────────────────────

describe('Dispatch Listing — quantity context', function () {

    it('returns ordered_qty, total_dispatched_qty, pending_qty for a partially dispatched item', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order, ['qty' => 10]);
        listingDispatch($order, $item, ['no_of_bags' => 3]);

        $d = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0');

        expect($d['ordered_qty'])->toBe(10)
            ->and($d['total_dispatched_qty'])->toBe(3)
            ->and($d['pending_qty'])->toBe(7)
            ->and($d['is_item_complete'])->toBeFalse();
    });

    it('sums total_dispatched_qty across all dispatch events for the same order item', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order, ['qty' => 10]);

        // Two separate dispatch events for the same item: 3 + 4 = 7 bags.
        listingDispatch($order, $item, ['no_of_bags' => 3]);
        listingDispatch($order, $item, ['no_of_bags' => 4]);

        $dispatches = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches');

        expect($dispatches)->toHaveCount(2);

        // Both dispatch records report the same item-level totals.
        foreach ($dispatches as $d) {
            expect($d['ordered_qty'])->toBe(10)
                ->and($d['total_dispatched_qty'])->toBe(7)
                ->and($d['pending_qty'])->toBe(3);
        }
    });

    it('marks is_item_complete true when all ordered bags have been dispatched', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order, ['qty' => 5]);
        listingDispatch($order, $item, ['no_of_bags' => 5]);

        $d = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0');

        expect($d['is_item_complete'])->toBeTrue()
            ->and($d['pending_qty'])->toBe(0);
    });
});

// ─── 7. Payment receivable ────────────────────────────────────────────────────

describe('Dispatch Listing — payment_receivable block', function () {

    it('returns base_amount, total_receivable, amount_paid, balance_due', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        // 8 bags @ ₹150 = ₹1,200 base; unpaid → balance_due = 1200.
        $item = listingItem($order, ['qty' => 10, 'unit_price' => 150.00]);
        listingDispatch($order, $item, ['no_of_bags' => 8, 'status' => DispatchManagement::STATUS_UNPAID]);

        $pr = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.payment_receivable');

        expect($pr)->toHaveKeys(['base_amount', 'total_receivable', 'amount_paid', 'balance_due'])
            ->and($pr['base_amount'])->toBe('1200.00')
            ->and($pr['total_receivable'])->toBe('1200.00')
            ->and($pr['amount_paid'])->toBe('0.00')
            ->and($pr['balance_due'])->toBe('1200.00');
    });

    it('sets amount_paid = total_receivable and balance_due = 0.00 when fully paid', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        // 5 bags @ ₹200 = ₹1,000; fully paid.
        $item = listingItem($order, ['qty' => 5, 'unit_price' => 200.00]);
        listingDispatch($order, $item, ['no_of_bags' => 5, 'status' => DispatchManagement::STATUS_PAID]);

        $pr = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.payment_receivable');

        expect($pr['base_amount'])->toBe('1000.00')
            ->and($pr['amount_paid'])->toBe('1000.00')
            ->and($pr['balance_due'])->toBe('0.00');
    });

    it('sets amount_paid = partial_paid_amount for partial payment', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        $item   = listingItem($order, ['qty' => 10, 'unit_price' => 100.00]);
        listingDispatch($order, $item, [
            'no_of_bags'          => 10,
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 400.00,
        ]);

        $pr = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.dispatches.0.payment_receivable');

        // base = 10 × 100 = 1000; paid = 400; balance = 600.
        expect($pr['base_amount'])->toBe('1000.00')
            ->and($pr['amount_paid'])->toBe('400.00')
            ->and($pr['balance_due'])->toBe('600.00');
    });
});

// ─── 8. Filters ──────────────────────────────────────────────────────────────

describe('Dispatch Listing — filter: dispatch_number', function () {

    it('filters by dispatch_number in DISP-XXXXXX format', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        $d1 = listingDispatch($o1, listingItem($o1));

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2));

        $dispatchNumber = 'DISP-' . str_pad((string) $d1->id, 6, '0', STR_PAD_LEFT);

        $response = getJson("/api/v1/dispatches?dispatch_number=$dispatchNumber", ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });

    it('filters by dispatch_number as a plain integer string', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $o      = listingOrder(['dealer_id' => $dealer->id]);
        $d      = listingDispatch($o, listingItem($o));

        $response = getJson('/api/v1/dispatches?dispatch_number=' . $d->id, ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d->id);
    });

    it('returns empty list when dispatch_number does not match any record', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $o      = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o, listingItem($o));

        $response = getJson('/api/v1/dispatches?dispatch_number=DISP-999999', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toBeEmpty();
    });

    it('does not expose another dealer\'s dispatch even when the dispatch_number matches', function () {
        [$dealerUser, $token] = listingUser('dealer');
        listingDealer($dealerUser); // this dealer has no dispatches

        $other   = User::factory()->create(['status' => 1]);
        $oDlr    = listingDealer($other);
        $order   = listingOrder(['dealer_id' => $oDlr->id]);
        $d       = listingDispatch($order, listingItem($order));
        $num     = 'DISP-' . str_pad((string) $d->id, 6, '0', STR_PAD_LEFT);

        $response = getJson("/api/v1/dispatches?dispatch_number=$num", ['Authorization' => "Bearer $token"])
            ->assertOk();

        // Role scope must prevent cross-dealer lookup.
        expect($response->json('data.dispatches'))->toBeEmpty();
    });
});

describe('Dispatch Listing — filter: order_number', function () {

    it('filters by partial order_number match', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id, 'unique_order_id' => 'ORD/ALPHA/001']);
        listingDispatch($o1, listingItem($o1));

        $o2 = listingOrder(['dealer_id' => $dealer->id, 'unique_order_id' => 'ORD/BETA/002']);
        listingDispatch($o2, listingItem($o2));

        $response = getJson('/api/v1/dispatches?order_number=ALPHA', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.order.order_number'))->toBe('ORD/ALPHA/001');
    });
});

describe('Dispatch Listing — filter: status', function () {

    it('filters by status=paid and excludes unpaid dispatches', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o1, listingItem($o1), ['status' => DispatchManagement::STATUS_PAID]);

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2), ['status' => DispatchManagement::STATUS_UNPAID]);

        $response = getJson('/api/v1/dispatches?status=paid', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.payment_status'))->toBe('paid');
    });

    it('filters by status=partial', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o1, listingItem($o1), [
            'status'              => DispatchManagement::STATUS_PARTIAL,
            'partial_paid_amount' => 100.00,
        ]);

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2), ['status' => DispatchManagement::STATUS_UNPAID]);

        $response = getJson('/api/v1/dispatches?status=partial', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.payment_status'))->toBe('partial');
    });
});

describe('Dispatch Listing — filter: date range', function () {

    it('filters dispatches by date_from and date_to (inclusive)', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o1, listingItem($o1), ['dispatch_date' => '2025-01-15']);

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2), ['dispatch_date' => '2025-03-20']);

        $response = getJson(
            '/api/v1/dispatches?date_from=2025-03-01&date_to=2025-03-31',
            ['Authorization' => "Bearer $token"]
        )->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.dispatch_date'))->toBe('2025-03-20');
    });
});

describe('Dispatch Listing — filter: product_id', function () {

    it('filters by product_id', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        $o1 = listingOrder(['dealer_id' => $dealer->id]);
        $i1 = listingItem($o1);
        $d1 = listingDispatch($o1, $i1);

        $o2 = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($o2, listingItem($o2));

        $response = getJson("/api/v1/dispatches?product_id={$d1->product_id}", ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });
});

describe('Dispatch Listing — filter: brand_id (broker only)', function () {

    it('filters by brand_id for a broker', function () {
        [$brokerUser, $token] = listingUser('broker');

        $brand1 = BrandManagement::create(['name' => 'BrandP ' . uniqid(), 'status' => 1]);
        $brand2 = BrandManagement::create(['name' => 'BrandQ ' . uniqid(), 'status' => 1]);

        $o1 = listingOrder(['broker_id' => $brokerUser->id, 'brand_id' => $brand1->id]);
        $d1 = listingDispatch($o1, listingItem($o1));

        $o2 = listingOrder(['broker_id' => $brokerUser->id, 'brand_id' => $brand2->id]);
        listingDispatch($o2, listingItem($o2));

        $response = getJson("/api/v1/dispatches?brand_id={$brand1->id}", ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($d1->id);
    });

    it('silently ignores brand_id filter for dealer role (all own dispatches are returned)', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($order, listingItem($order));

        // Passing a non-existent brand_id must be silently ignored for dealers.
        $response = getJson('/api/v1/dispatches?brand_id=99999', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1);
    });
});

describe('Dispatch Listing — filter: dealer_id (broker only)', function () {

    it('filters by dealer_id for a broker', function () {
        [$brokerUser, $token] = listingUser('broker');

        $uA    = User::factory()->create(['status' => 1]);
        $dA    = listingDealer($uA, $brokerUser->id);
        $oA    = listingOrder(['broker_id' => $brokerUser->id, 'dealer_id' => $dA->id]);
        $dispA = listingDispatch($oA, listingItem($oA));

        $uB = User::factory()->create(['status' => 1]);
        $dB = listingDealer($uB, $brokerUser->id);
        $oB = listingOrder(['broker_id' => $brokerUser->id, 'dealer_id' => $dB->id]);
        listingDispatch($oB, listingItem($oB));

        $response = getJson("/api/v1/dispatches?dealer_id={$dA->id}", ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.dispatches.0.id'))->toBe($dispA->id);
    });

    it('silently ignores dealer_id filter for dealer role (all own dispatches are returned)', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($order, listingItem($order));

        $response = getJson('/api/v1/dispatches?dealer_id=99999', ['Authorization' => "Bearer $token"])
            ->assertOk();

        // Dealer's own dispatch still visible — the filter was silently ignored.
        expect($response->json('data.dispatches'))->toHaveCount(1);
    });
});

// ─── 9. Pagination ────────────────────────────────────────────────────────────

describe('Dispatch Listing — pagination', function () {

    it('returns complete pagination metadata', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);
        $order  = listingOrder(['dealer_id' => $dealer->id]);
        listingDispatch($order, listingItem($order));

        $pagination = getJson('/api/v1/dispatches', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->json('data.pagination');

        expect($pagination)->toHaveKeys([
            'current_page', 'per_page', 'total',
            'last_page', 'next_page_url', 'prev_page_url',
        ]);
    });

    it('respects the per_page parameter and returns correct total', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        for ($i = 0; $i < 5; $i++) {
            $o = listingOrder(['dealer_id' => $dealer->id]);
            listingDispatch($o, listingItem($o));
        }

        $response = getJson('/api/v1/dispatches?per_page=2', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(2)
            ->and($response->json('data.pagination.per_page'))->toBe(2)
            ->and($response->json('data.pagination.total'))->toBe(5)
            ->and($response->json('data.pagination.last_page'))->toBe(3);
    });

    it('returns the correct page when using the page parameter', function () {
        [$dealerUser, $token] = listingUser('dealer');
        $dealer = listingDealer($dealerUser);

        // 3 dispatches; per_page=2 → page 2 has 1 record.
        for ($i = 0; $i < 3; $i++) {
            $o = listingOrder(['dealer_id' => $dealer->id]);
            listingDispatch($o, listingItem($o));
        }

        $response = getJson('/api/v1/dispatches?per_page=2&page=2', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.dispatches'))->toHaveCount(1)
            ->and($response->json('data.pagination.current_page'))->toBe(2);
    });
});

// ─── 10. Input validation ─────────────────────────────────────────────────────

describe('Dispatch Listing — validation', function () {

    it('returns 422 when per_page exceeds 100', function () {
        [, $token] = listingUser('dealer');

        getJson('/api/v1/dispatches?per_page=999', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJson(['success' => false, 'message' => 'Validation failed.'])
            ->assertJsonStructure(['data' => ['per_page']]);
    });

    it('returns 422 when date_to is before date_from', function () {
        [, $token] = listingUser('dealer');

        getJson(
            '/api/v1/dispatches?date_from=2025-03-20&date_to=2025-03-01',
            ['Authorization' => "Bearer $token"]
        )
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['date_to']]);
    });

    it('returns 422 for an invalid status value', function () {
        [, $token] = listingUser('dealer');

        getJson('/api/v1/dispatches?status=unknown', ['Authorization' => "Bearer $token"])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['status']]);
    });
});
