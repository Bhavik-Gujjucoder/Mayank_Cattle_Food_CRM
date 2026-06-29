<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function dppActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }
    return $user;
}

/** Build an unpaid dispatch scenario for the pending payments report. */
function dppSetup(): array
{
    $brand       = BrandManagement::create(['name' => 'DPP Brand ' . uniqid(), 'status' => 1]);
    $broker      = User::factory()->create(['status' => 1]);
    $brokerRole  = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
    $broker->assignRole($brokerRole);

    $dealerUser = User::factory()->create(['status' => 1]);
    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'DPP Firm ' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);

    $product = Product::create([
        'name'     => 'DPP Product ' . uniqid(),
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => 100.00,
        'status'   => 1,
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD/DPP/' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000.00,
        'grand_total'        => 1000.00,
        'status'             => 1,
    ]);

    $orderItem = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => 10,
        'unit_price'  => 100.00,
        'total_price' => 1000.00,
    ]);

    $transporter = User::factory()->create(['status' => 1]);

    // Create an UNPAID dispatch (status=0) — should appear in pending report
    $dispatch = DispatchManagement::create([
        'order_id'         => $order->id,
        'order_item_id'    => $orderItem->id,
        'product_id'       => $product->id,
        'no_of_bags'       => 5,
        'dispatch_date'    => now()->subDays(3)->toDateString(),
        'transport_id'     => $transporter->id,
        'truck_number'     => 'GJ01AA1234',
        'driver_contact'   => '9876543210',
        'status'           => 0, // unpaid
        'accrued_late_fee' => 0,
    ]);

    return compact('brand', 'broker', 'dealerUser', 'dealer', 'product', 'order', 'orderItem', 'transporter', 'dispatch');
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
    it('redirects unauthenticated user from delivery pending payments index', function () {
        $this->get(route('delivery-pending-payments.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from delivery pending payments export', function () {
        $this->get(route('delivery-pending-payments.export'))->assertRedirect(route('login'));
    });

    it('returns 403 when user lacks view-dispatch-pending-payments on index', function () {
        $actor = dppActor(); // admin role but no specific permission
        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'))
            ->assertForbidden();
    });

    it('returns 403 when user lacks view-dispatch-pending-payments on export', function () {
        $actor = dppActor();
        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export'))
            ->assertForbidden();
    });

    it('returns 200 on index when user has view-dispatch-pending-payments', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);
        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'))
            ->assertOk();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns the delivery pending payments view with correct data', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'))
            ->assertOk()
            ->assertViewIs('delivery_pending_payments.index')
            ->assertViewHas('brands')
            ->assertViewHas('brandSections')
            ->assertViewHas('brandFilter')
            ->assertViewHas('paymentDueDays');
    });

    it('default brandFilter is "all" when no brand_id query param', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('brandFilter'))->toBe('all');
    });

    it('accepts a valid active brand filter', function () {
        $brand = BrandManagement::create(['name' => 'Test Filter Brand', 'status' => 1]);
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index') . "?brand_id={$brand->id}");

        expect($response->viewData('brandFilter'))->toBe((string) $brand->id);
    });

    it('resets brandFilter to "all" when an inactive brand_id is passed', function () {
        $brand = BrandManagement::create(['name' => 'Inactive Brand', 'status' => 0]);
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index') . "?brand_id={$brand->id}");

        expect($response->viewData('brandFilter'))->toBe('all');
    });

    it('resets brandFilter to "all" when a non-existent brand_id is passed', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index') . '?brand_id=99999');

        expect($response->viewData('brandFilter'))->toBe('all');
    });

    it('brand sections contain unpaid dispatch entries', function () {
        dppSetup(); // creates brand + unpaid dispatch
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        $brandSections = $response->viewData('brandSections');
        expect($brandSections)->not->toBeEmpty();
    });

    it('brand sections are empty when all dispatches are paid', function () {
        $s     = dppSetup();
        $actor = dppActor(['view-dispatch-pending-payments']);

        // Mark the dispatch as paid
        $s['dispatch']->update(['status' => 1]);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        $brandSections = $response->viewData('brandSections');
        expect($brandSections)->toBeEmpty();
    });

    it('brand sections filtered by valid brand_id show only that brand', function () {
        $s1    = dppSetup(); // brand 1
        $s2    = dppSetup(); // brand 2
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index') . "?brand_id={$s1['brand']->id}");

        $brandSections = $response->viewData('brandSections');
        expect($brandSections)->toHaveCount(1)
            ->and((int) $brandSections->first()['brand_id'])->toBe($s1['brand']->id);
    });

    it('canLinkOrder is true when user has add-dispatch permission', function () {
        $actor = dppActor(['view-dispatch-pending-payments', 'add-dispatch']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('canLinkOrder'))->toBeTrue();
    });

    it('canLinkOrder is false when user has no dispatch permissions', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('canLinkOrder'))->toBeFalse();
    });

    it('canUpdateDispatchPayment is true when user has edit-dispatch permission', function () {
        $actor = dppActor(['view-dispatch-pending-payments', 'edit-dispatch']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('canUpdateDispatchPayment'))->toBeTrue();
    });

    it('canUpdateDispatchPayment is false when user lacks edit-dispatch', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('canUpdateDispatchPayment'))->toBeFalse();
    });

    it('brands dropdown only includes active brands', function () {
        BrandManagement::create(['name' => 'Active Brand', 'status' => 1]);
        BrandManagement::create(['name' => 'Inactive Brand', 'status' => 0]);
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        $brands = $response->viewData('brands');
        expect($brands)->toHaveCount(1)
            ->and($brands->first()->name)->toBe('Active Brand');
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('redirects with error when there are no pending dispatch records', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export'))
            ->assertRedirect(route('delivery-pending-payments.index'));
    });

    it('passes error flash when there are no records to export', function () {
        $actor = dppActor(['view-dispatch-pending-payments']);

        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export'))
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('returns a downloadable response when unpaid dispatches exist', function () {
        dppSetup(); // creates an unpaid dispatch
        $actor = dppActor(['view-dispatch-pending-payments']);

        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('export with valid brand filter only exports that brand\'s records', function () {
        $s1    = dppSetup();
        dppSetup(); // second brand with pending dispatch
        $actor = dppActor(['view-dispatch-pending-payments']);

        // Only export brand 1 — should still download (not redirect)
        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export') . "?brand_id={$s1['brand']->id}")
            ->assertOk();
    });

    it('redirects when brand filter returns no records', function () {
        dppSetup(); // unpaid dispatch for brand A
        $brandB = BrandManagement::create(['name' => 'Brand B', 'status' => 1]);
        $actor  = dppActor(['view-dispatch-pending-payments']);

        // Export brand B which has no dispatches
        $this->actingAs($actor)
            ->get(route('delivery-pending-payments.export') . "?brand_id={$brandB->id}")
            ->assertRedirect();
    });
});

// ─────────────────────────────────────────────

describe('report-service-logic', function () {
    it('partial-payment dispatches also appear in the pending report', function () {
        $s = dppSetup();
        $s['dispatch']->update(['status' => 2, 'partial_paid_amount' => 200]); // partial
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('brandSections'))->not->toBeEmpty();
    });

    it('soft-deleted orders do not appear in the pending report', function () {
        $s = dppSetup();
        $s['order']->delete(); // soft-delete the order
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        expect($response->viewData('brandSections'))->toBeEmpty();
    });

    it('brand sections are grouped by brand', function () {
        dppSetup(); // brand 1
        dppSetup(); // brand 2
        $actor = dppActor(['view-dispatch-pending-payments']);

        $response = $this->actingAs($actor)
            ->get(route('delivery-pending-payments.index'));

        $sections = $response->viewData('brandSections');
        // Should have 2 separate brand sections
        expect($sections)->toHaveCount(2);
    });
});
