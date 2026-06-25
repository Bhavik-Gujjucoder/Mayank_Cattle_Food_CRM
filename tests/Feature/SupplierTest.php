<?php

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function supActor(array $permissions = []): User
{
    $user = User::factory()->create(['status' => 1]);
    grantPermissions($user, $permissions);
    return $user;
}

function mkSupState(array $attrs = []): StateManagement
{
    return StateManagement::create(array_merge([
        'state_name' => 'State ' . uniqid(),
        'status'     => 1,
    ], $attrs));
}

function mkSupCity(int $stateId, array $attrs = []): CityManagement
{
    return CityManagement::create(array_merge([
        'state_id'  => $stateId,
        'city_name' => 'City ' . uniqid(),
        'status'    => 1,
    ], $attrs));
}

function mkSupBroker(int $stateId, int $cityId, array $attrs = []): SupplierBroker
{
    return SupplierBroker::create(array_merge([
        'name'     => 'Broker ' . uniqid(),
        'state_id' => $stateId,
        'city_id'  => $cityId,
        'status'   => 1,
    ], $attrs));
}

function mkSup(int $brokerId, int $stateId, int $cityId, array $attrs = []): Supplier
{
    return Supplier::create(array_merge([
        'supplier_broker_id' => $brokerId,
        'name'               => 'Supplier ' . uniqid(),
        'state_id'           => $stateId,
        'city_id'            => $cityId,
        'status'             => 1,
    ], $attrs));
}

function supPayload(int $brokerId, int $stateId, int $cityId, array $overrides = []): array
{
    return array_merge([
        'supplier_broker_id' => $brokerId,
        'name'               => 'Test Supplier',
        'state_id'           => $stateId,
        'city_id'            => $cityId,
    ], $overrides);
}

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from index', function () {
        $this->get(route('supplier.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $this->post(route('supplier.store'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->get(route('supplier.edit', $sup))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->put(route('supplier.update', $sup))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->delete(route('supplier.destroy', $sup))->assertRedirect(route('login'));
    });

    it('index is accessible to any authenticated user without specific permission', function () {
        $actor = supActor(); // no permissions
        $this->actingAs($actor)
            ->get(route('supplier.index'))
            ->assertOk()
            ->assertViewIs('supplier.index');
    });

    it('returns 403 when user lacks add-supplier for store', function () {
        $actor = supActor();
        $this->actingAs($actor)->post(route('supplier.store'))->assertForbidden();
    });

    it('returns 403 when user lacks edit-supplier for update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);
        $actor  = supActor();

        $this->actingAs($actor)->put(route('supplier.update', $sup))->assertForbidden();
    });

    it('returns 403 when user lacks delete-supplier for destroy', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);
        $actor  = supActor();

        $this->actingAs($actor)->delete(route('supplier.destroy', $sup))->assertForbidden();
    });

    it('returns 403 when user lacks delete-supplier for bulkDelete', function () {
        $actor = supActor();
        $this->actingAs($actor)->post(route('supplier.bulkDelete'))->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('returns supplier index view with states and supplier_brokers', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();

        $this->actingAs($actor)
            ->get(route('supplier.index'))
            ->assertOk()
            ->assertViewIs('supplier.index')
            ->assertViewHas('states')
            ->assertViewHas('supplier_brokers');
    });

    it('supplier_brokers in view only contains active brokers', function () {
        $state          = mkSupState();
        $city           = mkSupCity($state->id);
        mkSupBroker($state->id, $city->id, ['status' => 1]);
        mkSupBroker($state->id, $city->id, ['status' => 0]);
        $actor = supActor();

        $response = $this->actingAs($actor)->get(route('supplier.index'));
        expect($response->viewData('supplier_brokers'))->toHaveCount(1);
    });

    it('returns DataTables JSON on AJAX request', function () {
        $actor = supActor();
        $this->actingAs($actor)
            ->getJson(route('supplier.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX response includes supplier records', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by status=1 (active only)', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city->id, ['status' => 1]);
        mkSup($broker->id, $state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . '?status=1', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by status=0 (inactive only)', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city->id, ['status' => 1]);
        mkSup($broker->id, $state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . '?status=0', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX does not filter by status when status=all', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city->id, ['status' => 1]);
        mkSup($broker->id, $state->id, $city->id, ['status' => 0]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . '?status=all', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(2);
    });

    it('AJAX filters by supplier_broker_id', function () {
        $state   = mkSupState();
        $city    = mkSupCity($state->id);
        $broker1 = mkSupBroker($state->id, $city->id);
        $broker2 = mkSupBroker($state->id, $city->id);
        $actor   = supActor();
        mkSup($broker1->id, $state->id, $city->id);
        mkSup($broker2->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . "?supplier_broker_id={$broker1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by state_id', function () {
        $state1  = mkSupState();
        $state2  = mkSupState();
        $city1   = mkSupCity($state1->id);
        $city2   = mkSupCity($state2->id);
        $broker1 = mkSupBroker($state1->id, $city1->id);
        $broker2 = mkSupBroker($state2->id, $city2->id);
        $actor   = supActor();
        mkSup($broker1->id, $state1->id, $city1->id);
        mkSup($broker2->id, $state2->id, $city2->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . "?state_id={$state1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX filters by city_id', function () {
        $state  = mkSupState();
        $city1  = mkSupCity($state->id);
        $city2  = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city1->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city1->id);
        mkSup($broker->id, $state->id, $city2->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index') . "?city_id={$city1->id}", ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('recordsTotal'))->toBe(1);
    });

    it('AJAX action column hides edit button when user lacks edit-supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        mkSup($broker->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.action'))
            ->not->toContain('edit-supplier-btn')
            ->and($response->json('data.0.action'))->toContain('delete-supplier-btn');
    });

    it('AJAX action column hides delete button when user lacks delete-supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        mkSup($broker->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.action'))
            ->toContain('edit-supplier-btn')
            ->and($response->json('data.0.action'))->not->toContain('delete-supplier-btn');
    });

    it('AJAX renders status as statusBadge HTML', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        mkSup($broker->id, $state->id, $city->id, ['status' => 1]);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        expect($response->json('data.0.status'))->toContain('badge-pill');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('fails when supplier_broker_id is missing', function () {
        $state = mkSupState();
        $city  = mkSupCity($state->id);
        $actor = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload(0, $state->id, $city->id, ['supplier_broker_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_broker_id']);
    });

    it('fails when supplier_broker_id does not exist', function () {
        $state = mkSupState();
        $city  = mkSupCity($state->id);
        $actor = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload(99999, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_broker_id']);
    });

    it('fails when name is missing', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when state_id is missing', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['state_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when state_id does not exist', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, 99999, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    it('fails when city_id is missing', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['city_id' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when city_id does not exist', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, 99999))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when city does not belong to the selected state', function () {
        $state1 = mkSupState();
        $state2 = mkSupState();
        $city1  = mkSupCity($state1->id);
        $city2  = mkSupCity($state2->id); // city from state2
        $broker = mkSupBroker($state1->id, $city1->id);
        $actor  = supActor(['add-supplier']);

        // state_id=state1 but city_id belongs to state2
        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state1->id, $city2->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when city is inactive (city-belongs-to-state validation checks city.status=1)', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id, ['status' => 0]); // inactive city
        $broker = mkSupBroker($state->id, mkSupCity($state->id)->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when email format is invalid', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['email' => 'not-an-email']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when email is already taken by an active supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);
        mkSup($broker->id, $state->id, $city->id, ['email' => 'taken@example.com']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when opening_balance is negative', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['opening_balance' => -5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_balance']);
    });

    it('fails when status is an invalid value', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['status' => 5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates a supplier record with correct data', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, [
            'name'    => 'New Supplier',
            'mobile'  => '9876543210',
            'email'   => 'newsup@example.com',
            'address' => '123 Supply Road',
        ]));

        $this->assertDatabaseHas('suppliers', [
            'supplier_broker_id' => $broker->id,
            'name'               => 'New Supplier',
            'mobile'             => '9876543210',
            'email'              => 'newsup@example.com',
            'state_id'           => $state->id,
            'city_id'            => $city->id,
        ]);
    });

    it('defaults status to 1 when status is not provided', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id));

        $supplier = Supplier::latest()->first();
        expect((int) $supplier->status)->toBe(1);
    });

    it('defaults opening_balance to 0 when not provided', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id));

        $supplier = Supplier::latest()->first();
        expect((float) $supplier->opening_balance)->toBe(0.0);
    });

    it('stores empty string mobile/email/address as null', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, [
            'mobile'  => '',
            'email'   => '',
            'address' => '',
        ]));

        $supplier = Supplier::latest()->first();
        expect($supplier->mobile)->toBeNull()
            ->and($supplier->email)->toBeNull()
            ->and($supplier->address)->toBeNull();
    });

    it('returns JSON success response on store', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id))
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Supplier created successfully.']);
    });

    it('allows reusing an email from a soft-deleted supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['add-supplier']);

        // Create and soft-delete a supplier with that email
        $deleted = mkSup($broker->id, $state->id, $city->id, ['email' => 'reuse@example.com']);
        $deleted->delete(); // soft-delete

        $this->actingAs($actor)
            ->postJson(route('supplier.store'), supPayload($broker->id, $state->id, $city->id, ['email' => 'reuse@example.com']))
            ->assertOk()
            ->assertJson(['success' => true]);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('returns JSON with supplier and loaded relationships', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $response = $this->actingAs($actor)
            ->getJson(route('supplier.edit', $sup))
            ->assertOk();

        expect($response->json('id'))->toBe($sup->id)
            ->and($response->json('state'))->not->toBeNull()
            ->and($response->json('city'))->not->toBeNull()
            ->and($response->json('supplier_broker'))->not->toBeNull();
    });

    it('returns 404 for a non-existent supplier', function () {
        $actor = supActor();
        $this->actingAs($actor)
            ->getJson(route('supplier.edit', 99999))
            ->assertNotFound();
    });

    it('returns 404 for a soft-deleted supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor();
        $sup    = mkSup($broker->id, $state->id, $city->id);
        $sup->delete();

        $this->actingAs($actor)
            ->getJson(route('supplier.edit', $sup->id))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('fails when name is empty on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, ['name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when supplier_broker_id does not exist on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload(99999, $state->id, $city->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_broker_id']);
    });

    it('allows keeping the same email on self-update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id, ['email' => 'mine@example.com']);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, ['email' => 'mine@example.com']))
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('fails when email is already taken by a different supplier on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        mkSup($broker->id, $state->id, $city->id, ['email' => 'taken@example.com']);
        $sup = mkSup($broker->id, $state->id, $city->id, ['email' => 'mine@example.com']);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, ['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when city does not belong to the selected state on update', function () {
        $state1 = mkSupState();
        $state2 = mkSupState();
        $city1  = mkSupCity($state1->id);
        $city2  = mkSupCity($state2->id);
        $broker = mkSupBroker($state1->id, $city1->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state1->id, $city1->id);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state1->id, $city2->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_id']);
    });

    it('fails when opening_balance is negative on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, ['opening_balance' => -100]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opening_balance']);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates supplier record with new data', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, [
            'name'    => 'Updated Supplier',
            'mobile'  => '1112223334',
            'address' => 'New Address',
        ]));

        $this->assertDatabaseHas('suppliers', [
            'id'     => $sup->id,
            'name'   => 'Updated Supplier',
            'mobile' => '1112223334',
        ]);
    });

    it('updates status on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id, ['status' => 1]);

        $this->actingAs($actor)->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, [
            'status' => 0,
        ]));

        $this->assertDatabaseHas('suppliers', ['id' => $sup->id, 'status' => 0]);
    });

    it('returns JSON success response on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id))
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Supplier updated successfully.']);
    });

    it('stores empty string mobile/email as null on update', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['edit-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id, ['mobile' => '9999999999', 'email' => 'old@example.com']);

        $this->actingAs($actor)->putJson(route('supplier.update', $sup), supPayload($broker->id, $state->id, $city->id, [
            'mobile' => '',
            'email'  => '',
        ]));

        $sup->refresh();
        expect($sup->mobile)->toBeNull()->and($sup->email)->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes the supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)->delete(route('supplier.destroy', $sup));

        $this->assertSoftDeleted('suppliers', ['id' => $sup->id]);
    });

    it('supplier is removed from default query after soft-delete', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)->delete(route('supplier.destroy', $sup));

        expect(Supplier::find($sup->id))->toBeNull();
    });

    it('redirects to supplier index after destroy', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->delete(route('supplier.destroy', $sup))
            ->assertRedirect(route('supplier.index'));
    });

    it('returns 404 when destroying a non-existent supplier', function () {
        $actor = supActor(['delete-supplier']);
        $this->actingAs($actor)
            ->delete(route('supplier.destroy', 99999))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('bulkDelete', function () {
    it('returns 400 when ids array is empty', function () {
        $actor = supActor(['delete-supplier']);
        $this->actingAs($actor)
            ->postJson(route('supplier.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('returns 400 when ids key is missing', function () {
        $actor = supActor(['delete-supplier']);
        $this->actingAs($actor)
            ->postJson(route('supplier.bulkDelete'), [])
            ->assertStatus(400);
    });

    it('soft-deletes selected suppliers', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup1   = mkSup($broker->id, $state->id, $city->id);
        $sup2   = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->postJson(route('supplier.bulkDelete'), ['ids' => [$sup1->id, $sup2->id]])
            ->assertOk();

        $this->assertSoftDeleted('suppliers', ['id' => $sup1->id]);
        $this->assertSoftDeleted('suppliers', ['id' => $sup2->id]);
    });

    it('does not affect suppliers not in the ids list', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup1   = mkSup($broker->id, $state->id, $city->id);
        $sup2   = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->postJson(route('supplier.bulkDelete'), ['ids' => [$sup1->id]]);

        expect(Supplier::find($sup2->id))->not->toBeNull();
    });

    it('returns JSON success message on bulk delete', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $actor  = supActor(['delete-supplier']);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        $this->actingAs($actor)
            ->postJson(route('supplier.bulkDelete'), ['ids' => [$sup->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected suppliers deleted successfully.']);
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('statusBadge returns bg-success badge for active supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id, ['status' => 1]);

        expect($sup->statusBadge())
            ->toContain('bg-success')
            ->toContain('badge-pill')
            ->toContain('Active');
    });

    it('statusBadge returns bg-danger badge for inactive supplier', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id, ['status' => 0]);

        expect($sup->statusBadge())
            ->toContain('bg-danger')
            ->toContain('badge-pill')
            ->toContain('Inactive');
    });

    it('supplierBroker() returns the associated SupplierBroker', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        expect($sup->supplierBroker)->toBeInstanceOf(SupplierBroker::class)
            ->and($sup->supplierBroker->id)->toBe($broker->id);
    });

    it('state() returns the associated StateManagement', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        expect($sup->state)->toBeInstanceOf(StateManagement::class)
            ->and($sup->state->id)->toBe($state->id);
    });

    it('city() returns the associated CityManagement', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);

        expect($sup->city)->toBeInstanceOf(CityManagement::class)
            ->and($sup->city->id)->toBe($city->id);
    });

    it('soft-deleted supplier is not found via normal query', function () {
        $state  = mkSupState();
        $city   = mkSupCity($state->id);
        $broker = mkSupBroker($state->id, $city->id);
        $sup    = mkSup($broker->id, $state->id, $city->id);
        $sup->delete();

        expect(Supplier::find($sup->id))->toBeNull();
        expect(Supplier::withTrashed()->find($sup->id))->not->toBeNull();
    });
});
