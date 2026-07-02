<?php

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function sbActor(array $permissions = []): User
{
    $user = User::factory()->create(['status' => 1]);
    grantPermissions($user, $permissions);
    return $user;
}

function mkSbState(array $attrs = []): StateManagement
{
    return StateManagement::create(array_merge([
        'state_name' => 'State ' . uniqid(),
        'status'     => 1,
    ], $attrs));
}

function mkSbCity(int $stateId, array $attrs = []): CityManagement
{
    return CityManagement::create(array_merge([
        'state_id'  => $stateId,
        'city_name' => 'City ' . uniqid(),
        'status'    => 1,
    ], $attrs));
}

function mkSb(int $stateId, int $cityId, array $attrs = []): SupplierBroker
{
    return SupplierBroker::create(array_merge([
        'name'            => 'Broker ' . uniqid(),
        'state_id'        => $stateId,
        'city_id'         => $cityId,
        'opening_balance' => 0,
        'status'          => 1,
    ], $attrs));
}

function sbPayload(int $stateId, int $cityId, array $overrides = []): array
{
    return array_merge([
        'name'     => 'Test Supplier Broker',
        'state_id' => $stateId,
        'city_id'  => $cityId,
    ], $overrides);
}

// ─────────────────────────────────────────────
//  Global beforeEach — flush permission cache
// ─────────────────────────────────────────────

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from index', function () {
        $this->get(route('supplier-broker.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $this->post(route('supplier-broker.store'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        $this->get(route('supplier-broker.edit', $sb))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        $this->put(route('supplier-broker.update', $sb))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        $this->delete(route('supplier-broker.destroy', $sb))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from bulk-delete', function () {
        $this->post(route('supplier-broker.bulkDelete'))->assertRedirect(route('login'));
    });

    it('returns 403 when authenticated user lacks view-supplier-broker for index', function () {
        $actor = sbActor(); // no permissions
        $this->actingAs($actor)->get(route('supplier-broker.index'))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks add-supplier-broker for store', function () {
        $actor = sbActor(['view-supplier-broker']); // no add permission
        $this->actingAs($actor)->post(route('supplier-broker.store'))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks edit-supplier-broker for update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);
        $actor = sbActor(['view-supplier-broker']);

        $this->actingAs($actor)->put(route('supplier-broker.update', $sb))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks delete-supplier-broker for destroy', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);
        $actor = sbActor(['view-supplier-broker']);

        $this->actingAs($actor)->delete(route('supplier-broker.destroy', $sb))->assertForbidden();
    });

    it('returns 403 when authenticated user lacks delete-supplier-broker for bulk-delete', function () {
        $actor = sbActor(['view-supplier-broker']);
        $this->actingAs($actor)->post(route('supplier-broker.bulkDelete'))->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns supplier-broker index view for authenticated user with permission', function () {
        $actor = sbActor(['view-supplier-broker']);
        $this->actingAs($actor)
            ->get(route('supplier-broker.index'))
            ->assertOk()
            ->assertViewIs('supplier_broker.index');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $actor = sbActor(['view-supplier-broker']);
        $this->actingAs($actor)
            ->getJson(route('supplier-broker.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX response includes supplier broker records', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by status=1 (active)', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city->id, ['status' => 1]);
        mkSb($state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index') . '?status=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by status=0 (inactive)', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city->id, ['status' => 1]);
        mkSb($state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index') . '?status=0', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX returns all records when status=all', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city->id, ['status' => 1]);
        mkSb($state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index') . '?status=all', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(2);
    });

    it('AJAX filters by state_id', function () {
        $state1 = mkSbState();
        $state2 = mkSbState();
        $city1  = mkSbCity($state1->id);
        $city2  = mkSbCity($state2->id);
        $actor  = sbActor(['view-supplier-broker']);
        mkSb($state1->id, $city1->id);
        mkSb($state2->id, $city2->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index') . "?state_id={$state1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by city_id', function () {
        $state = mkSbState();
        $city1 = mkSbCity($state->id);
        $city2 = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city1->id);
        mkSb($state->id, $city2->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index') . "?city_id={$city1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX action column hides edit button when user lacks edit-supplier-broker', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker', 'delete-supplier-broker']); // no edit
        mkSb($state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $html = $response->json('data.0.action');
        expect($html)->not->toContain('edit-supplier-broker-btn')
            ->and($html)->toContain('delete-supplier-broker-btn');
    });

    it('AJAX action column hides delete button when user lacks delete-supplier-broker', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker', 'edit-supplier-broker']); // no delete
        mkSb($state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $html = $response->json('data.0.action');
        expect($html)->toContain('edit-supplier-broker-btn')
            ->and($html)->not->toContain('delete-supplier-broker-btn');
    });

    it('AJAX response includes city_name virtual column', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id, ['city_name' => 'TestCityName']);
        $actor = sbActor(['view-supplier-broker']);
        mkSb($state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $row = $response->json('data.0');
        expect($row)->toHaveKey('city_name');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('fails when name is missing', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when name exceeds 255 characters', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['name' => str_repeat('A', 256)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when state_id is missing', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['state_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when state_id does not exist in state_management', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload(99999, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when city_id is missing', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['city_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when city_id does not exist in city_management', function () {
        $state = mkSbState();
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, 99999))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when email format is invalid', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['email' => 'not-an-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is already taken by another non-deleted supplier broker', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);
        mkSb($state->id, $city->id, ['email' => 'taken@example.com']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows reusing email of a soft-deleted supplier broker on store', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['email' => 'recycled@example.com']);
        $sb->delete(); // soft delete

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['email' => 'recycled@example.com']))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails when mobile exceeds 20 characters', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['mobile' => str_repeat('1', 21)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mobile']);
    });

    it('fails when opening_balance is negative', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['opening_balance' => -1]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_balance']);
    });

    it('fails when opening_balance is not numeric', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['opening_balance' => 'abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_balance']);
    });

    it('fails when status is not 0 or 1', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['status' => 2]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('fails with city_id validation error when city does not belong to selected state', function () {
        $state1 = mkSbState();
        $state2 = mkSbState();
        $city   = mkSbCity($state1->id); // city belongs to state1
        $actor  = sbActor(['add-supplier-broker']);

        // Submit with state2 but city from state1
        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state2->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates a supplier broker record in the database', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, [
                'name'     => 'Persistent Broker',
                'state_id' => $state->id,
                'city_id'  => $city->id,
            ]));

        $this->assertDatabaseHas('supplier_brokers', [
            'name'     => 'Persistent Broker',
            'state_id' => $state->id,
            'city_id'  => $city->id,
        ]);
    });

    it('returns JSON with success and message on successful store', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Supplier broker created successfully.',
            ]);
    });

    it('stores null for empty string mobile', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['mobile' => '']));

        $sb = SupplierBroker::latest()->first();
        expect($sb->mobile)->toBeNull();
    });

    it('stores null for empty string email', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['email' => '']));

        $sb = SupplierBroker::latest()->first();
        expect($sb->email)->toBeNull();
    });

    it('stores null for empty string address', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, ['address' => '']));

        $sb = SupplierBroker::latest()->first();
        expect($sb->address)->toBeNull();
    });

    it('defaults opening_balance to 0 when not provided', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id));

        $sb = SupplierBroker::latest()->first();
        expect((float) $sb->opening_balance)->toBe(0.0);
    });

    it('defaults status to 1 when not provided', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id));

        $sb = SupplierBroker::latest()->first();
        expect($sb->status)->toBe(1);
    });

    it('persists all provided optional fields', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['add-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.store'), sbPayload($state->id, $city->id, [
                'mobile'          => '9876543210',
                'email'           => 'broker@example.com',
                'address'         => '123 Test Street',
                'opening_balance' => 500.50,
                'status'          => 0,
            ]));

        $this->assertDatabaseHas('supplier_brokers', [
            'mobile'  => '9876543210',
            'email'   => 'broker@example.com',
            'address' => '123 Test Street',
            'status'  => 0,
        ]);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('returns JSON with supplier broker data for authenticated user', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['name' => 'Edit Test Broker']);

        $this->actingAs($actor)
            ->getJson(route('supplier-broker.edit', $sb))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Edit Test Broker']);
    });

    it('edit response includes loaded state and city relationships', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier-broker.edit', $sb))
            ->assertOk();

        expect($response->json('state'))->not->toBeNull()
            ->and($response->json('city'))->not->toBeNull();
    });

    it('returns 404 for a non-existent supplier broker', function () {
        $actor = sbActor(['view-supplier-broker']);
        $this->actingAs($actor)
            ->getJson(route('supplier-broker.edit', 99999))
            ->assertNotFound();
    });

    it('returns 404 for a soft-deleted supplier broker', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['view-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);
        $sb->delete(); // soft delete

        $this->actingAs($actor)
            ->getJson(route('supplier-broker.edit', $sb->id))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('fails when name is missing on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when state_id is invalid on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload(99999, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when city_id is invalid on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, 99999))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when email is already taken by a different supplier broker on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        mkSb($state->id, $city->id, ['email' => 'other@example.com']);
        $sb = mkSb($state->id, $city->id, ['email' => 'mine@example.com']);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['email' => 'other@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('allows keeping the same email on self-update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['email' => 'same@example.com']);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['email' => 'same@example.com']))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('allows reusing email of a soft-deleted supplier broker on update', function () {
        $state   = mkSbState();
        $city    = mkSbCity($state->id);
        $actor   = sbActor(['edit-supplier-broker']);
        $deleted = mkSb($state->id, $city->id, ['email' => 'recycled2@example.com']);
        $deleted->delete(); // soft delete
        $sb = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['email' => 'recycled2@example.com']))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails when city does not belong to selected state on update', function () {
        $state1 = mkSbState();
        $state2 = mkSbState();
        $city   = mkSbCity($state1->id); // city belongs to state1
        $actor  = sbActor(['edit-supplier-broker']);
        $sb     = mkSb($state1->id, $city->id);

        // Submit with state2 but city from state1
        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state2->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when opening_balance is negative on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['opening_balance' => -5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_balance']);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates the supplier broker record in the database', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['name' => 'Old Name']);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['name' => 'Updated Name']));

        $this->assertDatabaseHas('supplier_brokers', [
            'id'   => $sb->id,
            'name' => 'Updated Name',
        ]);
    });

    it('returns JSON with success and message on successful update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Supplier broker updated successfully.',
            ]);
    });

    it('stores null for empty string mobile on update', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['mobile' => '9999999999']);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['mobile' => '']));

        $sb->refresh();
        expect($sb->mobile)->toBeNull();
    });

    it('updates status field', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['status' => 1]);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['status' => 0]));

        $this->assertDatabaseHas('supplier_brokers', [
            'id'     => $sb->id,
            'status' => 0,
        ]);
    });

    it('updates opening_balance field', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['edit-supplier-broker']);
        $sb    = mkSb($state->id, $city->id, ['opening_balance' => 0]);

        $this->actingAs($actor)
            ->putJson(route('supplier-broker.update', $sb), sbPayload($state->id, $city->id, ['opening_balance' => 1500.75]));

        $sb->refresh();
        expect((float) $sb->opening_balance)->toBe(1500.75);
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes the supplier broker record', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)->delete(route('supplier-broker.destroy', $sb));

        $this->assertSoftDeleted('supplier_brokers', ['id' => $sb->id]);
    });

    it('redirects to supplier-broker index with success flash after destroy', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->delete(route('supplier-broker.destroy', $sb))
            ->assertRedirect(route('supplier-broker.index'));
    });

    it('blocks destroy when supplier broker has linked suppliers and redirects with error flash', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb    = mkSb($state->id, $city->id);

        Supplier::create([
            'supplier_broker_id' => $sb->id,
            'name'               => 'Linked Supplier',
        ]);

        $response = $this->actingAs($actor)
            ->delete(route('supplier-broker.destroy', $sb))
            ->assertRedirect(route('supplier-broker.index'));

        $response->assertSessionHas('error', 'Cannot delete — this supplier broker has linked suppliers.');

        // Record should NOT be soft-deleted
        $this->assertDatabaseHas('supplier_brokers', [
            'id'         => $sb->id,
            'deleted_at' => null,
        ]);
    });

    it('returns 404 when attempting to destroy a non-existent supplier broker', function () {
        $actor = sbActor(['delete-supplier-broker']);
        $this->actingAs($actor)
            ->delete(route('supplier-broker.destroy', 99999))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('bulkDelete', function () {
    it('returns 400 JSON when ids array is empty', function () {
        $actor = sbActor(['delete-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('returns 400 JSON when ids key is not provided', function () {
        $actor = sbActor(['delete-supplier-broker']);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), [])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('soft-deletes all selected supplier brokers', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb1   = mkSb($state->id, $city->id);
        $sb2   = mkSb($state->id, $city->id);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), ['ids' => [$sb1->id, $sb2->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected supplier brokers deleted successfully.']);

        $this->assertSoftDeleted('supplier_brokers', ['id' => $sb1->id]);
        $this->assertSoftDeleted('supplier_brokers', ['id' => $sb2->id]);
    });

    it('returns 422 when any broker has linked suppliers, includes names in message', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb1   = mkSb($state->id, $city->id, ['name' => 'BrokerWithSupplier']);
        $sb2   = mkSb($state->id, $city->id, ['name' => 'CleanBroker']);

        Supplier::create([
            'supplier_broker_id' => $sb1->id,
            'name'               => 'Linked Supplier',
        ]);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), ['ids' => [$sb1->id, $sb2->id]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot delete supplier broker(s) with linked suppliers: BrokerWithSupplier.']);
    });

    it('does not delete any records when one broker has linked suppliers', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb1   = mkSb($state->id, $city->id, ['name' => 'BrokerWithSupplier2']);
        $sb2   = mkSb($state->id, $city->id, ['name' => 'CleanBroker2']);

        Supplier::create([
            'supplier_broker_id' => $sb1->id,
            'name'               => 'Linked Supplier 2',
        ]);

        $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), ['ids' => [$sb1->id, $sb2->id]])
            ->assertStatus(422);

        // Neither should be soft-deleted
        $this->assertDatabaseHas('supplier_brokers', ['id' => $sb1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('supplier_brokers', ['id' => $sb2->id, 'deleted_at' => null]);
    });

    it('returns 422 listing multiple blocked broker names', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $actor = sbActor(['delete-supplier-broker']);
        $sb1   = mkSb($state->id, $city->id, ['name' => 'BlockedBrokerA']);
        $sb2   = mkSb($state->id, $city->id, ['name' => 'BlockedBrokerB']);

        Supplier::create(['supplier_broker_id' => $sb1->id, 'name' => 'Supplier A']);
        Supplier::create(['supplier_broker_id' => $sb2->id, 'name' => 'Supplier B']);

        $response = $this->actingAs($actor)
            ->postJson(route('supplier-broker.bulkDelete'), ['ids' => [$sb1->id, $sb2->id]])
            ->assertStatus(422);

        $message = $response->json('message');
        expect($message)->toContain('BlockedBrokerA')
            ->and($message)->toContain('BlockedBrokerB');
    });
});

// ─────────────────────────────────────────────

describe('model-relationships', function () {
    it('state() returns the associated StateManagement', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        expect($sb->state)->toBeInstanceOf(StateManagement::class)
            ->and($sb->state->id)->toBe($state->id);
    });

    it('city() returns the associated CityManagement', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        expect($sb->city)->toBeInstanceOf(CityManagement::class)
            ->and($sb->city->id)->toBe($city->id);
    });

    it('suppliers() returns linked Supplier records', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        Supplier::create(['supplier_broker_id' => $sb->id, 'name' => 'Supplier One']);
        Supplier::create(['supplier_broker_id' => $sb->id, 'name' => 'Supplier Two']);

        expect($sb->suppliers)->toHaveCount(2)
            ->and($sb->suppliers->first())->toBeInstanceOf(Supplier::class);
    });

    it('suppliers() returns empty collection when no suppliers linked', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);

        expect($sb->suppliers)->toHaveCount(0);
    });

    it('state() returns null when state_id is null', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);
        $sb->state_id = null;
        $sb->save();

        expect($sb->fresh()->state)->toBeNull();
    });

    it('city() returns null when city_id is null', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id);
        $sb->city_id = null;
        $sb->save();

        expect($sb->fresh()->city)->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('model-statusBadge', function () {
    it('returns badge-pill and bg-success when status is 1', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id, ['status' => 1]);

        $badge = $sb->statusBadge();

        expect($badge)->toContain('badge-pill')
            ->and($badge)->toContain('bg-success');
    });

    it('returns Active label when status is 1', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id, ['status' => 1]);

        expect($sb->statusBadge())->toContain('Active');
    });

    it('returns badge-pill and bg-danger when status is 0', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id, ['status' => 0]);

        $badge = $sb->statusBadge();

        expect($badge)->toContain('badge-pill')
            ->and($badge)->toContain('bg-danger');
    });

    it('returns Inactive label when status is 0', function () {
        $state = mkSbState();
        $city  = mkSbCity($state->id);
        $sb    = mkSb($state->id, $city->id, ['status' => 0]);

        expect($sb->statusBadge())->toContain('Inactive');
    });
});
