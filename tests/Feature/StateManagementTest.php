<?php

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\User;

/* ─────────────────────────────────────────────────────────────────────────
 |  Helpers
 ───────────────────────────────────────────────────────────────────────── */

/** Authenticated user, optionally granted Spatie permissions. */
function stateTestUser(array $permissions = []): User
{
    $user = User::factory()->create();
    return empty($permissions) ? $user : grantPermissions($user, $permissions);
}

/** Quickly create a StateManagement row. */
function makeStateRecord(array $attrs = []): StateManagement
{
    return StateManagement::create(array_merge([
        'state_name' => 'Gujarat',
        'status'     => 1,
    ], $attrs));
}

/* ═══════════════════════════════════════════════════════════════════════
 |  1. ACCESS CONTROL
 ══════════════════════════════════════════════════════════════════════ */
describe('access control', function () {

    test('guest is redirected from state index', function () {
        $this->get(route('state.index'))
            ->assertRedirect(route('login'));
    });

    test('authenticated user can view state index page', function () {
        $this->actingAs(stateTestUser())
            ->get(route('state.index'))
            ->assertOk()
            ->assertSee('State Management');
    });

    test('store requires add-state permission — 403 without it', function () {
        $this->actingAs(stateTestUser())
            ->postJson(route('state.store'), ['state_name' => 'Rajasthan', 'status' => 1])
            ->assertForbidden();
    });

    test('update requires edit-state permission — 403 without it', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser())
            ->putJson(route('state.update', $state), ['state_name' => 'Rajasthan Updated', 'status' => 1])
            ->assertForbidden();
    });

    test('destroy requires delete-state permission — 403 without it', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser())
            ->delete(route('state.destroy', $state))
            ->assertForbidden();
    });

    test('bulk delete requires delete-state permission — 403 without it', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser())
            ->postJson(route('state.bulkDelete'), ['ids' => [$state->id]])
            ->assertForbidden();
    });

    test('edit JSON endpoint requires only authentication, no specific permission', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser())
            ->getJson(route('state.edit', $state))
            ->assertOk();
    });

    test('guest is redirected from edit endpoint', function () {
        $state = makeStateRecord();

        $this->get(route('state.edit', $state))
            ->assertRedirect(route('login'));
    });

    test('guest is redirected from store endpoint', function () {
        $this->postJson(route('state.store'), ['state_name' => 'Rajasthan', 'status' => 1])
            ->assertUnauthorized();
    });

    test('guest is redirected from destroy endpoint', function () {
        $state = makeStateRecord();

        $this->delete(route('state.destroy', $state))
            ->assertRedirect(route('login'));
    });

    test('guest is redirected from bulk delete endpoint', function () {
        $state = makeStateRecord();

        $this->postJson(route('state.bulkDelete'), ['ids' => [$state->id]])
            ->assertUnauthorized();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  2. STORE — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('store — validation', function () {

    test('state_name is required', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_name']);
    });

    test('state_name missing entirely is a validation error', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_name']);
    });

    test('state_name must be unique among non-deleted states', function () {
        makeStateRecord(['state_name' => 'Gujarat']);

        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Gujarat', 'status' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_name']);
    });

    test('soft-deleted state name can be reused for a new state', function () {
        $deleted = makeStateRecord(['state_name' => 'Delhi']);
        $deleted->delete(); // soft-delete; uniqueness rule ignores deleted_at IS NOT NULL

        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Delhi', 'status' => 1])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Active record: 1; total including trashed: 2
        expect(StateManagement::where('state_name', 'Delhi')->count())->toBe(1);
        expect(StateManagement::withTrashed()->where('state_name', 'Delhi')->count())->toBe(2);
    });

    test('no errors are returned for valid payload', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Karnataka', 'status' => 1])
            ->assertOk()
            ->assertJsonMissingValidationErrors();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  3. STORE — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('store — persistence', function () {

    test('creates a state and returns JSON success response', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Rajasthan', 'status' => 1])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'State created successfully']);

        $this->assertDatabaseHas('state_management', ['state_name' => 'Rajasthan', 'status' => 1]);
    });

    test('can create an inactive state with status 0', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Odisha', 'status' => 0]);

        $this->assertDatabaseHas('state_management', ['state_name' => 'Odisha', 'status' => 0]);
    });

    test('newly created state has null deleted_at', function () {
        $this->actingAs(stateTestUser(['add-state']))
            ->postJson(route('state.store'), ['state_name' => 'Tripura', 'status' => 1]);

        $state = StateManagement::where('state_name', 'Tripura')->first();
        expect($state)->not->toBeNull()
            ->and($state->deleted_at)->toBeNull();
    });

    test('multiple distinct states can be created independently', function () {
        $user = stateTestUser(['add-state']);

        $this->actingAs($user)->postJson(route('state.store'), ['state_name' => 'Goa',        'status' => 1]);
        $this->actingAs($user)->postJson(route('state.store'), ['state_name' => 'Meghalaya',  'status' => 1]);
        $this->actingAs($user)->postJson(route('state.store'), ['state_name' => 'Nagaland',   'status' => 1]);

        expect(StateManagement::count())->toBe(3);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  4. EDIT (JSON)
 ══════════════════════════════════════════════════════════════════════ */
describe('edit', function () {

    test('returns JSON with all state fields for a valid id', function () {
        $state = makeStateRecord(['state_name' => 'Punjab', 'status' => 1]);

        $this->actingAs(stateTestUser())
            ->getJson(route('state.edit', $state))
            ->assertOk()
            ->assertJson([
                'id'         => $state->id,
                'state_name' => 'Punjab',
                'status'     => 1,
            ]);
    });

    test('returns 404 for a non-existent state id', function () {
        $this->actingAs(stateTestUser())
            ->getJson(route('state.edit', 99999))
            ->assertNotFound();
    });

    test('returns 404 for a soft-deleted state', function () {
        $state = makeStateRecord();
        $state->delete();

        $this->actingAs(stateTestUser())
            ->getJson(route('state.edit', $state->id))
            ->assertNotFound();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  5. UPDATE — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('update — validation', function () {

    test('state_name is required on update', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => '', 'status' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_name']);
    });

    test('state_name must be unique excluding the state being edited', function () {
        makeStateRecord(['state_name' => 'Kerala']);
        $other = makeStateRecord(['state_name' => 'Telangana']);

        // Telangana cannot steal Kerala's name
        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $other), ['state_name' => 'Kerala', 'status' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_name']);
    });

    test('state can be updated using its own current name without uniqueness error', function () {
        $state = makeStateRecord(['state_name' => 'Bihar']);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'Bihar', 'status' => 0])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    test('soft-deleted state name can be adopted when updating another state', function () {
        $deleted = makeStateRecord(['state_name' => 'Himachal Pradesh']);
        $deleted->delete();

        $state = makeStateRecord(['state_name' => 'Uttarakhand']);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'Himachal Pradesh', 'status' => 1])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    test('returns 404 when updating a non-existent state', function () {
        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', 99999), ['state_name' => 'Unknown', 'status' => 1])
            ->assertNotFound();
    });

    test('returns 404 when updating a soft-deleted state', function () {
        $state = makeStateRecord();
        $state->delete();

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state->id), ['state_name' => 'New Name', 'status' => 1])
            ->assertNotFound();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  6. UPDATE — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('update — persistence', function () {

    test('updates state name in database', function () {
        $state = makeStateRecord(['state_name' => 'Old Name']);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'New Name', 'status' => 1]);

        $this->assertDatabaseHas('state_management',    ['id' => $state->id, 'state_name' => 'New Name']);
        $this->assertDatabaseMissing('state_management', ['id' => $state->id, 'state_name' => 'Old Name']);
    });

    test('updates state status from active to inactive', function () {
        $state = makeStateRecord(['state_name' => 'MP', 'status' => 1]);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'MP', 'status' => 0]);

        $this->assertDatabaseHas('state_management', ['id' => $state->id, 'status' => 0]);
    });

    test('updates state status from inactive to active', function () {
        $state = makeStateRecord(['state_name' => 'UP', 'status' => 0]);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'UP', 'status' => 1]);

        $this->assertDatabaseHas('state_management', ['id' => $state->id, 'status' => 1]);
    });

    test('returns JSON success message on update', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $state), ['state_name' => 'Renamed State', 'status' => 1])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'State updated successfully']);
    });

    test('updating one state does not affect sibling states', function () {
        $target  = makeStateRecord(['state_name' => 'Target State']);
        $sibling = makeStateRecord(['state_name' => 'Sibling State']);

        $this->actingAs(stateTestUser(['edit-state']))
            ->putJson(route('state.update', $target), ['state_name' => 'Renamed Target', 'status' => 0]);

        $this->assertDatabaseHas('state_management', ['id' => $sibling->id, 'state_name' => 'Sibling State', 'status' => 1]);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  7. DESTROY
 ══════════════════════════════════════════════════════════════════════ */
describe('destroy', function () {

    test('soft-deletes the state', function () {
        $state = makeStateRecord(['state_name' => 'Jharkhand']);

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $state));

        $this->assertSoftDeleted('state_management', ['id' => $state->id]);
    });

    test('soft-deletes all cities belonging to the deleted state', function () {
        $state = makeStateRecord();
        $cityA = CityManagement::create(['state_id' => $state->id, 'city_name' => 'City A', 'status' => 1]);
        $cityB = CityManagement::create(['state_id' => $state->id, 'city_name' => 'City B', 'status' => 0]);

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $state));

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('does not soft-delete cities belonging to other states', function () {
        $stateA = makeStateRecord(['state_name' => 'State A']);
        $stateB = makeStateRecord(['state_name' => 'State B']);
        $cityB  = CityManagement::create(['state_id' => $stateB->id, 'city_name' => 'Safe City', 'status' => 1]);

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $stateA));

        $this->assertNotSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('state with no cities is deleted without error', function () {
        $state = makeStateRecord(['state_name' => 'Empty State']);

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $state))
            ->assertRedirect(route('state.index'));

        $this->assertSoftDeleted('state_management', ['id' => $state->id]);
    });

    test('redirects to state index with success flash after delete', function () {
        $state = makeStateRecord();

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $state))
            ->assertRedirect(route('state.index'))
            ->assertSessionHas('success');
    });

    test('returns 404 for a non-existent state id', function () {
        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', 99999))
            ->assertNotFound();
    });

    test('soft-deleted state is excluded from normal queries but present in withTrashed', function () {
        $state = makeStateRecord(['state_name' => 'Manipur']);

        $this->actingAs(stateTestUser(['delete-state']))
            ->delete(route('state.destroy', $state));

        expect(StateManagement::where('id', $state->id)->exists())->toBeFalse();
        expect(StateManagement::withTrashed()->where('id', $state->id)->exists())->toBeTrue();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  8. BULK DELETE
 ══════════════════════════════════════════════════════════════════════ */
describe('bulk delete', function () {

    test('soft-deletes all specified states and returns JSON success', function () {
        $stateA = makeStateRecord(['state_name' => 'State X']);
        $stateB = makeStateRecord(['state_name' => 'State Y']);

        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$stateA->id, $stateB->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected states deleted successfully!']);

        $this->assertSoftDeleted('state_management', ['id' => $stateA->id]);
        $this->assertSoftDeleted('state_management', ['id' => $stateB->id]);
    });

    test('also soft-deletes cities of every bulk-deleted state', function () {
        $stateA = makeStateRecord(['state_name' => 'State P']);
        $stateB = makeStateRecord(['state_name' => 'State Q']);
        $cityA  = CityManagement::create(['state_id' => $stateA->id, 'city_name' => 'City P1', 'status' => 1]);
        $cityB  = CityManagement::create(['state_id' => $stateB->id, 'city_name' => 'City Q1', 'status' => 1]);

        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$stateA->id, $stateB->id]]);

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('does not affect cities of states outside the bulk-delete list', function () {
        $toDelete = makeStateRecord(['state_name' => 'To Delete']);
        $toKeep   = makeStateRecord(['state_name' => 'To Keep']);
        $safeCity = CityManagement::create(['state_id' => $toKeep->id, 'city_name' => 'Safe City', 'status' => 1]);

        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$toDelete->id]]);

        $this->assertNotSoftDeleted('city_management', ['id' => $safeCity->id]);
    });

    test('returns 400 when ids array is empty', function () {
        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected!']);
    });

    test('returns 400 when ids key is missing from payload', function () {
        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), [])
            ->assertStatus(400);
    });

    test('bulk-deleting a single state works correctly', function () {
        $state = makeStateRecord(['state_name' => 'Solo State']);

        $this->actingAs(stateTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$state->id]])
            ->assertOk();

        $this->assertSoftDeleted('state_management', ['id' => $state->id]);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  9. MODEL BEHAVIOUR
 ══════════════════════════════════════════════════════════════════════ */
describe('model — statusBadge', function () {

    test('active state (status=1) returns a success badge', function () {
        $state = makeStateRecord(['status' => 1]);
        expect($state->statusBadge())
            ->toContain('bg-success')
            ->toContain('Active');
    });

    test('inactive state (status=0) returns a danger badge', function () {
        $state = makeStateRecord(['status' => 0]);
        expect($state->statusBadge())
            ->toContain('bg-danger')
            ->toContain('Inactive');
    });

});

describe('model — cities relationship', function () {

    test('cities() only returns active (status=1) cities', function () {
        $state        = makeStateRecord();
        $activeCity   = CityManagement::create(['state_id' => $state->id, 'city_name' => 'Active City',   'status' => 1]);
        $inactiveCity = CityManagement::create(['state_id' => $state->id, 'city_name' => 'Inactive City', 'status' => 0]);

        $cities = $state->cities;

        expect($cities)->toHaveCount(1)
            ->and($cities->first()->id)->toBe($activeCity->id);
    });

    test('cities() excludes soft-deleted cities', function () {
        $state = makeStateRecord();
        $city  = CityManagement::create(['state_id' => $state->id, 'city_name' => 'Deleted City', 'status' => 1]);
        $city->delete();

        expect($state->fresh()->cities)->toHaveCount(0);
    });

    test('cities() only returns cities for the correct state', function () {
        $stateA = makeStateRecord(['state_name' => 'State A']);
        $stateB = makeStateRecord(['state_name' => 'State B']);

        CityManagement::create(['state_id' => $stateA->id, 'city_name' => 'City A', 'status' => 1]);
        CityManagement::create(['state_id' => $stateB->id, 'city_name' => 'City B', 'status' => 1]);

        expect($stateA->cities)->toHaveCount(1)
            ->and($stateA->cities->first()->city_name)->toBe('City A');
    });

});
