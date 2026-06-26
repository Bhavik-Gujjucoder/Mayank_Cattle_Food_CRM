<?php

use App\Models\BrandManagement;
use App\Models\CityManagement;
use App\Models\DealerManagement;
use App\Models\StateManagement;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function dealerActor(array $permissions = []): User
{
    $user = User::factory()->create(['status' => 1]);
    grantPermissions($user, $permissions);
    return $user;
}

function makeBroker(array $attrs = []): User
{
    Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
    $broker = User::factory()->create(array_merge(['status' => 1], $attrs));
    $broker->assignRole('broker');
    return $broker;
}

function makeDealerBrand(array $attrs = []): BrandManagement
{
    return BrandManagement::create(array_merge([
        'name'   => 'Brand ' . uniqid(),
        'status' => 1,
    ], $attrs));
}

function makeDealerState(array $attrs = []): StateManagement
{
    return StateManagement::create(array_merge([
        'state_name' => 'State ' . uniqid(),
        'status'     => 1,
    ], $attrs));
}

function makeDealerCity(int $stateId, array $attrs = []): CityManagement
{
    return CityManagement::create(array_merge([
        'state_id'  => $stateId,
        'city_name' => 'City ' . uniqid(),
        'status'    => 1,
    ], $attrs));
}

function makeDealer(int $brokerId, int $brandId, int $stateId, int $cityId, array $attrs = []): DealerManagement
{
    Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);
    $dealerUser = User::factory()->create(['status' => 1]);
    $dealerUser->assignRole('dealer');

    return DealerManagement::create(array_merge([
        'user_id'           => $dealerUser->id,
        'broker_id'         => $brokerId,
        'brand_id'          => $brandId,
        'code_no'           => 'MCF' . substr(strtoupper(md5(uniqid())), 0, 6),
        'firm_shop_name'    => 'Test Firm',
        'firm_shop_address' => '123 Test Street',
        'state_id'          => $stateId,
        'city_id'           => $cityId,
    ], $attrs));
}

function dealerPayload(int $brokerId, int $brandId, int $stateId, int $cityId, array $overrides = []): array
{
    return array_merge([
        'broker_id'             => $brokerId,
        'brand_id'              => $brandId,
        'code_no'               => 'MCFTEST01',
        'applicant_name'        => 'Test Dealer',
        'firm_shop_name'        => 'Test Firm',
        'firm_shop_address'     => '123 Test Street',
        'mobile_no'             => '9876543210',
        'state_id'              => $stateId,
        'city_id'               => $cityId,
        'email'                 => 'dealer.' . uniqid() . '@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ], $overrides);
}

// ─────────────────────────────────────────────
//  Global beforeEach — ensure required roles exist
// ─────────────────────────────────────────────

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);
});

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from index', function () {
        $this->get(route('dealer.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from create', function () {
        $this->get(route('dealer.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $this->post(route('dealer.store'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from show', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->get(route('dealer.show', $dealer))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->get(route('dealer.edit', $dealer))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->put(route('dealer.update', $dealer))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->delete(route('dealer.destroy', $dealer))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from quickCreateForm', function () {
        $this->get(route('dealer.quickCreateForm'))->assertRedirect(route('login'));
    });

    it('returns 403 when authenticated user lacks add-dealer for store', function () {
        $actor = dealerActor(); // no permissions
        $this->actingAs($actor)->post(route('dealer.store'))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks edit-dealer for update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);
        $actor  = dealerActor();

        $this->actingAs($actor)->put(route('dealer.update', $dealer))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks delete-dealer for destroy', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);
        $actor  = dealerActor();

        $this->actingAs($actor)->delete(route('dealer.destroy', $dealer))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks add-dealer for quickCreateForm', function () {
        $actor = dealerActor();
        $this->actingAs($actor)->get(route('dealer.quickCreateForm'))->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns dealer index view for authenticated user', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->get(route('dealer.index'))
            ->assertOk()
            ->assertViewIs('dealer.index');
    });

    it('passes brokers and brands to the view', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->get(route('dealer.index'))
            ->assertOk()
            ->assertViewHas('brokers')
            ->assertViewHas('brands');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->getJson(route('dealer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX response includes dealer records', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dealers by active broker_id', function () {
        $state   = makeDealerState();
        $city    = makeDealerCity($state->id);
        $broker1 = makeBroker();
        $broker2 = makeBroker();
        $brand   = makeDealerBrand();
        $actor   = dealerActor();
        makeDealer($broker1->id, $brand->id, $state->id, $city->id);
        makeDealer($broker2->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index') . "?broker_id={$broker1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX skips broker filter when broker_id is all', function () {
        $state   = makeDealerState();
        $city    = makeDealerCity($state->id);
        $broker1 = makeBroker();
        $broker2 = makeBroker();
        $brand   = makeDealerBrand();
        $actor   = dealerActor();
        makeDealer($broker1->id, $brand->id, $state->id, $city->id);
        makeDealer($broker2->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index') . '?broker_id=all', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(2);
    });

    it('AJAX filters dealers by active brand_id', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand1 = makeDealerBrand();
        $brand2 = makeDealerBrand();
        $actor  = dealerActor();
        makeDealer($broker->id, $brand1->id, $state->id, $city->id);
        makeDealer($broker->id, $brand2->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index') . "?brand_id={$brand1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dealers by start_date', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();

        $old = makeDealer($broker->id, $brand->id, $state->id, $city->id);
        $old->created_at = now()->subDays(10);
        $old->save();

        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $start    = now()->subDays(2)->format('Y-m-d');
        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index') . "?start_date={$start}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters dealers by end_date', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();

        $old = makeDealer($broker->id, $brand->id, $state->id, $city->id);
        $old->created_at = now()->subDays(10);
        $old->save();

        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $end      = now()->subDays(5)->format('Y-m-d');
        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index') . "?end_date={$end}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX action column hides edit button when user lacks edit-dealer', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['delete-dealer']); // no edit-dealer
        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $html = $response->json('data.0.action');
        // edit icon absent, delete icon present
        expect($html)->not->toContain('ti-edit')
            ->and($html)->toContain('delete-dealer-btn');
    });

    it('AJAX action column hides delete button when user lacks delete-dealer', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']); // no delete-dealer
        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('dealer.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $html = $response->json('data.0.action');
        // edit icon present, delete icon absent
        expect($html)->toContain('ti-edit')
            ->and($html)->not->toContain('delete-dealer-btn');
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('returns create view with required data', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->get(route('dealer.create'))
            ->assertOk()
            ->assertViewIs('dealer.create')
            ->assertViewHas('brokers')
            ->assertViewHas('brands')
            ->assertViewHas('states')
            ->assertViewHas('code_no');
    });

    it('auto-generated code_no follows MCF + 6-digit zero-padded format', function () {
        $actor = dealerActor();
        $response = $this->actingAs($actor)->get(route('dealer.create'));
        expect($response->viewData('code_no'))->toMatch('/^MCF\d{6}$/');
    });
});

// ─────────────────────────────────────────────

describe('quickCreateForm', function () {
    it('requires add-dealer permission', function () {
        $actor = dealerActor(); // no add-dealer
        $this->actingAs($actor)
            ->get(route('dealer.quickCreateForm'))
            ->assertForbidden();
    });

    it('returns 422 when broker_id is not an active broker', function () {
        $brand = makeDealerBrand();
        $actor = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->getJson(route('dealer.quickCreateForm') . "?broker_id=99999&brand_id={$brand->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['broker_id']);
    });

    it('returns 422 when brand_id is not an active brand', function () {
        $broker = makeBroker();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->getJson(route('dealer.quickCreateForm') . "?broker_id={$broker->id}&brand_id=99999", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id']);
    });

    it('returns form view with locked broker and brand for authorized non-broker user', function () {
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->get(route('dealer.quickCreateForm') . "?broker_id={$broker->id}&brand_id={$brand->id}")
            ->assertOk()
            ->assertViewIs('dealer.partials.quick-create-form')
            ->assertViewHas('lockedBrokerId', $broker->id)
            ->assertViewHas('lockedBrandId', $brand->id);
    });

    it('returns 403 when broker user tries to use another brokers id', function () {
        $brand       = makeDealerBrand();
        $otherBroker = makeBroker();
        $actor       = makeBroker(); // logged-in user is also a broker
        grantPermissions($actor, ['add-dealer']);

        $this->actingAs($actor)
            ->get(route('dealer.quickCreateForm') . "?broker_id={$otherBroker->id}&brand_id={$brand->id}")
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('fails when broker_id is missing', function () {
        $state = makeDealerState();
        $city  = makeDealerCity($state->id);
        $brand = makeDealerBrand();
        $actor = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload(0, $brand->id, $state->id, $city->id, ['broker_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['broker_id']);
    });

    it('fails when broker_id does not belong to an active broker', function () {
        $state = makeDealerState();
        $city  = makeDealerCity($state->id);
        $brand = makeDealerBrand();
        $actor = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload(99999, $brand->id, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['broker_id']);
    });

    it('fails when broker is inactive', function () {
        $state          = makeDealerState();
        $city           = makeDealerCity($state->id);
        $brand          = makeDealerBrand();
        $actor          = dealerActor(['add-dealer']);
        $inactiveBroker = makeBroker(['status' => 0]);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($inactiveBroker->id, $brand->id, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['broker_id']);
    });

    it('fails when brand_id is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, 0, $state->id, $city->id, ['brand_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id']);
    });

    it('fails when brand is inactive', function () {
        $state         = makeDealerState();
        $city          = makeDealerCity($state->id);
        $broker        = makeBroker();
        $actor         = dealerActor(['add-dealer']);
        $inactiveBrand = makeDealerBrand(['status' => 0]);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $inactiveBrand->id, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id']);
    });

    it('fails when code_no is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['code_no' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code_no']);
    });

    it('fails when code_no is already taken', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);
        makeDealer($broker->id, $brand->id, $state->id, $city->id, ['code_no' => 'DUPCODE01']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['code_no' => 'DUPCODE01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code_no']);
    });

    it('fails when applicant_name is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['applicant_name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['applicant_name']);
    });

    it('fails when firm_shop_name is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['firm_shop_name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firm_shop_name']);
    });

    it('fails when firm_shop_address is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['firm_shop_address' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['firm_shop_address']);
    });

    it('fails when mobile_no is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['mobile_no' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mobile_no']);
    });

    it('fails when mobile_no is not exactly 10 digits', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['mobile_no' => '98765']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mobile_no']);
    });

    it('fails when pancard format is invalid', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        // INVALID123 has 7 letters then 3 digits — does not match [A-Z]{5}[0-9]{4}[A-Z]
        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['pancard' => 'INVALID123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pancard']);
    });

    it('fails when gstin is not exactly 15 characters', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['gstin' => 'SHORTGSTIN']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gstin']);
    });

    it('fails when aadhar_card is not exactly 12 digits', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['aadhar_card' => '12345']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['aadhar_card']);
    });

    it('fails when email format is invalid', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['email' => 'not-an-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is already taken by another user', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when password is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'password'              => '',
                'password_confirmation' => '',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when password confirmation does not match', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'password_confirmation' => 'different_password',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('fails when state_id is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['state_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when city_id is missing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, ['city_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates a User record with the submitted data', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);
        $data   = dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'applicant_name' => 'Jane Doe',
            'mobile_no'      => '9988776655',
            'email'          => 'jane@example.com',
        ]);

        $this->actingAs($actor)->postJson(route('dealer.store'), $data);

        $this->assertDatabaseHas('users', [
            'name'     => 'Jane Doe',
            'phone_no' => '9988776655',
            'email'    => 'jane@example.com',
            'status'   => 1,
        ]);
    });

    it('creates a DealerManagement record linked to the new user', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);
        $data   = dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'code_no'        => 'MCFPERSIST',
            'firm_shop_name' => 'Doe Traders',
        ]);

        $this->actingAs($actor)->postJson(route('dealer.store'), $data);

        $this->assertDatabaseHas('dealer_management', [
            'broker_id'      => $broker->id,
            'brand_id'       => $brand->id,
            'code_no'        => 'MCFPERSIST',
            'firm_shop_name' => 'Doe Traders',
        ]);
    });

    it('assigns the dealer role to the created user', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id));

        $dealer = DealerManagement::with('user')->latest()->first();
        expect($dealer->user->hasRole('dealer'))->toBeTrue();
    });

    it('uppercases pancard before storing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'pancard' => 'abcde1234f',
        ]));

        $this->assertDatabaseHas('dealer_management', ['pancard' => 'ABCDE1234F']);
    });

    it('uppercases gstin before storing', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'gstin' => '22abcde1234f1z5',
        ]));

        $this->assertDatabaseHas('dealer_management', ['gstin' => '22ABCDE1234F1Z5']);
    });

    it('returns JSON response when request expects JSON', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->postJson(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id))
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'dealer' => ['id', 'name', 'firm_shop_name', 'firm_shop_address']]);
    });

    it('redirects to dealer index on regular form submit', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);

        $this->actingAs($actor)
            ->post(route('dealer.store'), dealerPayload($broker->id, $brand->id, $state->id, $city->id))
            ->assertRedirect(route('dealer.index'));
    });

    it('stores null email when email is not provided', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['add-dealer']);
        $data   = dealerPayload($broker->id, $brand->id, $state->id, $city->id);
        unset($data['email']);

        $this->actingAs($actor)->postJson(route('dealer.store'), $data);

        $dealer = DealerManagement::with('user')->latest()->first();
        expect($dealer->user->email)->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('returns JSON with html key containing rendered view', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->getJson(route('dealer.show', $dealer))
            ->assertOk()
            ->assertJsonStructure(['html']);
    });

    it('returns 404 for a non-existent dealer', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->getJson(route('dealer.show', 99999))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('returns edit view for an authenticated user', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->get(route('dealer.edit', $dealer))
            ->assertOk()
            ->assertViewIs('dealer.edit');
    });

    it('edit view receives dealer with user relationship loaded', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $response    = $this->actingAs($actor)->get(route('dealer.edit', $dealer));
        $viewDealer  = $response->viewData('dealer');

        expect($viewDealer->relationLoaded('user'))->toBeTrue()
            ->and($viewDealer->user->id)->toBe($dealer->user_id);
    });

    it('edit view includes brokers brands states and cities', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->get(route('dealer.edit', $dealer))
            ->assertOk()
            ->assertViewHas('brokers')
            ->assertViewHas('brands')
            ->assertViewHas('states')
            ->assertViewHas('cities');
    });

    it('returns 404 for a non-existent dealer', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->get(route('dealer.edit', 99999))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('allows keeping the same code_no on self-update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id, ['code_no' => 'SAME001']);

        $this->actingAs($actor)
            ->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'code_no'               => 'SAME001',
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('dealer.index'));
    });

    it('fails when code_no is already taken by a different dealer', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        makeDealer($broker->id, $brand->id, $state->id, $city->id, ['code_no' => 'TAKEN001']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id, ['code_no' => 'MINE001']);

        $this->actingAs($actor)
            ->putJson(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'code_no'               => 'TAKEN001',
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code_no']);
    });

    it('allows null password on update (password is optional)', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('dealer.index'));
    });

    it('fails when applicant_name is empty on update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'applicant_name'        => '',
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['applicant_name']);
    });

    it('fails when mobile_no is not 10 digits on update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'mobile_no'             => '12345',
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mobile_no']);
    });

    it('fails when broker_id is inactive on update', function () {
        $state          = makeDealerState();
        $city           = makeDealerCity($state->id);
        $broker         = makeBroker();
        $brand          = makeDealerBrand();
        $actor          = dealerActor(['edit-dealer']);
        $inactiveBroker = makeBroker(['status' => 0]);
        $dealer         = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('dealer.update', $dealer), dealerPayload($inactiveBroker->id, $brand->id, $state->id, $city->id, [
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['broker_id']);
    });

    it('fails when brand_id is inactive on update', function () {
        $state         = makeDealerState();
        $city          = makeDealerCity($state->id);
        $broker        = makeBroker();
        $brand         = makeDealerBrand();
        $actor         = dealerActor(['edit-dealer']);
        $inactiveBrand = makeDealerBrand(['status' => 0]);
        $dealer        = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('dealer.update', $dealer), dealerPayload($broker->id, $inactiveBrand->id, $state->id, $city->id, [
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['brand_id']);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates the User record with new applicant data', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'applicant_name'        => 'Updated Name',
            'mobile_no'             => '1112223334',
            'password'              => null,
            'password_confirmation' => null,
        ]));

        $this->assertDatabaseHas('users', [
            'id'       => $dealer->user_id,
            'name'     => 'Updated Name',
            'phone_no' => '1112223334',
        ]);
    });

    it('updates the DealerManagement record', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'firm_shop_name'        => 'New Firm Name',
            'code_no'               => $dealer->code_no,
            'password'              => null,
            'password_confirmation' => null,
        ]));

        $this->assertDatabaseHas('dealer_management', [
            'id'             => $dealer->id,
            'firm_shop_name' => 'New Firm Name',
        ]);
    });

    it('uppercases pancard when updating', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'pancard'               => 'abcde1234f',
            'password'              => null,
            'password_confirmation' => null,
        ]));

        $this->assertDatabaseHas('dealer_management', [
            'id'      => $dealer->id,
            'pancard' => 'ABCDE1234F',
        ]);
    });

    it('updates password when a new password is provided', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
            'password'              => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]));

        $dealer->user->refresh();
        expect(Hash::check('newpassword', $dealer->user->password))->toBeTrue();
    });

    it('redirects to dealer index on successful update', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['edit-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->put(route('dealer.update', $dealer), dealerPayload($broker->id, $brand->id, $state->id, $city->id, [
                'password'              => null,
                'password_confirmation' => null,
            ]))
            ->assertRedirect(route('dealer.index'));
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('hard-deletes the DealerManagement record', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['delete-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)->delete(route('dealer.destroy', $dealer));

        $this->assertDatabaseMissing('dealer_management', ['id' => $dealer->id]);
    });

    it('hard-deletes the associated User record', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['delete-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);
        $userId = $dealer->user_id;

        $this->actingAs($actor)->delete(route('dealer.destroy', $dealer));

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        expect(User::find($userId))->toBeNull();
    });

    it('redirects to dealer index after successful destroy', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(['delete-dealer']);
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->delete(route('dealer.destroy', $dealer))
            ->assertRedirect(route('dealer.index'));
    });

    it('returns 403 when user lacks delete-dealer permission', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor(); // no delete-dealer
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->delete(route('dealer.destroy', $dealer))
            ->assertForbidden();
    });

    it('returns 404 when attempting to destroy a non-existent dealer', function () {
        $actor = dealerActor(['delete-dealer']);
        $this->actingAs($actor)
            ->delete(route('dealer.destroy', 99999))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('getCitiesByState', function () {
    it('returns active cities for the given state', function () {
        $state = makeDealerState();
        makeDealerCity($state->id, ['city_name' => 'CityAlpha', 'status' => 1]);
        makeDealerCity($state->id, ['city_name' => 'CityBeta',  'status' => 1]);
        $actor = dealerActor();

        $response = $this->actingAs($actor)
            ->postJson(route('get.cities'), ['state_id' => $state->id])
            ->assertOk();

        $cityNames = collect($response->json())->pluck('city_name');
        expect($cityNames)->toContain('CityAlpha')
            ->and($cityNames)->toContain('CityBeta');
    });

    it('excludes inactive cities from the response', function () {
        $state = makeDealerState();
        makeDealerCity($state->id, ['city_name' => 'ActiveCity',   'status' => 1]);
        makeDealerCity($state->id, ['city_name' => 'InactiveCity', 'status' => 0]);
        $actor = dealerActor();

        $response = $this->actingAs($actor)
            ->postJson(route('get.cities'), ['state_id' => $state->id])
            ->assertOk();

        $cityNames = collect($response->json())->pluck('city_name');
        expect($cityNames)->toContain('ActiveCity')
            ->and($cityNames)->not->toContain('InactiveCity');
    });

    it('returns empty array when state has no active cities', function () {
        $state = makeDealerState();
        $actor = dealerActor();

        $this->actingAs($actor)
            ->postJson(route('get.cities'), ['state_id' => $state->id])
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns empty array for a non-existent state_id', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->postJson(route('get.cities'), ['state_id' => 99999])
            ->assertOk()
            ->assertExactJson([]);
    });
});

// ─────────────────────────────────────────────

describe('getDealersByBrokerBrand', function () {
    it('returns empty array when broker_id is invalid', function () {
        $brand = makeDealerBrand();
        $actor = dealerActor();

        $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id=99999&brand_id={$brand->id}")
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns empty array when brand_id is invalid', function () {
        $broker = makeBroker();
        $actor  = dealerActor();

        $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id={$broker->id}&brand_id=99999")
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns empty array when the broker is inactive', function () {
        $brand          = makeDealerBrand();
        $actor          = dealerActor();
        $inactiveBroker = makeBroker(['status' => 0]);

        $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id={$inactiveBroker->id}&brand_id={$brand->id}")
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns empty array when no dealers match the broker and brand', function () {
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();

        $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id={$broker->id}&brand_id={$brand->id}")
            ->assertOk()
            ->assertExactJson([]);
    });

    it('returns dealers for a valid active broker and active brand', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id={$broker->id}&brand_id={$brand->id}")
            ->assertOk();

        expect($response->json())->not->toBeEmpty()
            ->and($response->json('0.id'))->toBe($dealer->id);
    });

    it('returns dealer response with expected JSON structure', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $actor  = dealerActor();
        makeDealer($broker->id, $brand->id, $state->id, $city->id, ['firm_shop_name' => 'TestFirm', 'firm_shop_address' => 'TestAddr']);

        $response = $this->actingAs($actor)
            ->getJson(route('get.dealers') . "?broker_id={$broker->id}&brand_id={$brand->id}")
            ->assertOk();

        expect($response->json('0'))->toMatchArray([
            'firm_shop_name'    => 'TestFirm',
            'firm_shop_address' => 'TestAddr',
        ]);
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('returns 403 without export-dealer permission', function () {
        $actor = dealerActor();
        $this->actingAs($actor)
            ->get(route('dealer.export'))
            ->assertForbidden();
    });

    it('returns excel download with export-dealer permission', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        makeDealer($broker->id, $brand->id, $state->id, $city->id);

        $actor = dealerActor(['export-dealer']);
        $this->actingAs($actor)
            ->get(route('dealer.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('redirects with error when no dealers match export filters', function () {
        $state   = makeDealerState();
        $city    = makeDealerCity($state->id);
        $broker  = makeBroker();
        $brandA  = makeDealerBrand();
        $brandB  = makeDealerBrand();
        makeDealer($broker->id, $brandA->id, $state->id, $city->id);

        $actor = dealerActor(['export-dealer']);
        $this->actingAs($actor)
            ->from(route('dealer.index'))
            ->get(route('dealer.export', ['brand_id' => $brandB->id]))
            ->assertRedirect(route('dealer.index'))
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('model-relationships', function () {
    it('broker() returns the associated broker User', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        expect($dealer->broker)->toBeInstanceOf(User::class)
            ->and($dealer->broker->id)->toBe($broker->id);
    });

    it('brand() returns the associated BrandManagement', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        expect($dealer->brand)->toBeInstanceOf(BrandManagement::class)
            ->and($dealer->brand->id)->toBe($brand->id);
    });

    it('user() returns the dealer User account', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        expect($dealer->user)->toBeInstanceOf(User::class)
            ->and($dealer->user->id)->toBe($dealer->user_id);
    });

    it('city() returns the associated CityManagement', function () {
        $state  = makeDealerState();
        $city   = makeDealerCity($state->id);
        $broker = makeBroker();
        $brand  = makeDealerBrand();
        $dealer = makeDealer($broker->id, $brand->id, $state->id, $city->id);

        expect($dealer->city)->toBeInstanceOf(CityManagement::class)
            ->and($dealer->city->id)->toBe($city->id);
    });
});
