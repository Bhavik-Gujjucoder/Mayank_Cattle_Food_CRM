<?php

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\User;

/* ─────────────────────────────────────────────────────────────────────────
 |  Helpers — names are distinct from StateManagementTest.php helpers
 ───────────────────────────────────────────────────────────────────────── */

/** Authenticated user, optionally granted Spatie permissions. */
function cityTestUser(array $permissions = []): User
{
    $user = User::factory()->create();
    return empty($permissions) ? $user : grantPermissions($user, $permissions);
}

/** Create a StateManagement row (used as the city's parent state). */
function makeCityState(string $name = 'Gujarat', int $status = 1): StateManagement
{
    return StateManagement::create(['state_name' => $name, 'status' => $status]);
}

/** Create a CityManagement row for the given state. */
function makeCityRecord(StateManagement $state, array $attrs = []): CityManagement
{
    return CityManagement::create(array_merge([
        'state_id'  => $state->id,
        'city_name' => 'Ahmedabad',
        'status'    => 1,
    ], $attrs));
}

/* ═══════════════════════════════════════════════════════════════════════
 |  1. ACCESS CONTROL
 ══════════════════════════════════════════════════════════════════════ */
describe('access control', function () {

    test('guest is redirected from city index', function () {
        $this->get(route('city.index'))
            ->assertRedirect(route('login'));
    });

    test('authenticated user can view city index page', function () {
        $this->actingAs(cityTestUser())
            ->get(route('city.index'))
            ->assertOk()
            ->assertSee('City Management');
    });

    test('store requires add-city permission — 403 without it', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser())
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Surat',
                'status'    => 1,
            ])
            ->assertForbidden();
    });

    test('update requires edit-city permission — 403 without it', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser())
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => 'Vadodara',
                'status'    => 1,
            ])
            ->assertForbidden();
    });

    test('destroy requires delete-city permission — 403 without it', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser())
            ->delete(route('city.destroy', $city))
            ->assertForbidden();
    });

    test('bulk delete requires delete-city permission — 403 without it', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser())
            ->postJson(route('city.bulkDelete'), ['ids' => [$city->id]])
            ->assertForbidden();
    });

    test('edit JSON endpoint requires only authentication, no specific permission', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser())
            ->getJson(route('city.edit', $city))
            ->assertOk();
    });

    test('guest is redirected from city edit endpoint', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->get(route('city.edit', $city))
            ->assertRedirect(route('login'));
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  2. INDEX — VIEW DATA
 ══════════════════════════════════════════════════════════════════════ */
describe('index — view data', function () {

    test('index view receives only active states for the dropdown', function () {
        $active   = makeCityState('Active State',   1);
        $inactive = makeCityState('Inactive State', 0);

        $response = $this->actingAs(cityTestUser())->get(route('city.index'));

        $response->assertOk();

        // Active state is available for the city dropdown
        $response->assertSee('Active State');

        // Inactive state must not appear in the state dropdown
        $response->assertDontSee('Inactive State');
    });

    test('index view does not show soft-deleted states in the dropdown', function () {
        $deleted = makeCityState('Deleted State', 1);
        $deleted->delete();

        $response = $this->actingAs(cityTestUser())->get(route('city.index'));

        $response->assertOk()->assertDontSee('Deleted State');
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  3. STORE — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('store — validation', function () {

    test('state_id is required', function () {
        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), ['city_name' => 'Surat', 'status' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    test('state_id required error uses custom message "The state name field is required."', function () {
        $response = $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), ['city_name' => 'Surat', 'status' => 1])
            ->assertUnprocessable();

        $errors = $response->json('errors.state_id');
        expect($errors)->toContain('The state name field is required.');
    });

    test('city_name is required', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), ['state_id' => $state->id, 'city_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_name']);
    });

    test('city_name missing entirely is a validation error', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), ['state_id' => $state->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_name']);
    });

    test('city_name must be globally unique across all states', function () {
        $stateA = makeCityState('State A');
        $stateB = makeCityState('State B');
        makeCityRecord($stateA, ['city_name' => 'Ahmedabad']);

        // Same city name rejected even when submitted for a different state
        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $stateB->id,
                'city_name' => 'Ahmedabad',
                'status'    => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_name']);
    });

    test('soft-deleted city name can be reused', function () {
        $state   = makeCityState();
        $deleted = makeCityRecord($state, ['city_name' => 'Rajkot']);
        $deleted->delete();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Rajkot',
                'status'    => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // One active row, two total (including trashed)
        expect(CityManagement::where('city_name', 'Rajkot')->count())->toBe(1);
        expect(CityManagement::withTrashed()->where('city_name', 'Rajkot')->count())->toBe(2);
    });

    test('both state_id and city_name missing produces errors for both fields', function () {
        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id', 'city_name']);
    });

    test('no errors returned for a fully valid payload', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Vadodara',
                'status'    => 1,
            ])
            ->assertOk()
            ->assertJsonMissingValidationErrors();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  4. STORE — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('store — persistence', function () {

    test('creates a city and returns JSON success response', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Surat',
                'status'    => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'City created successfully']);

        $this->assertDatabaseHas('city_management', [
            'state_id'  => $state->id,
            'city_name' => 'Surat',
            'status'    => 1,
        ]);
    });

    test('can create an inactive city with status 0', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Bharuch',
                'status'    => 0,
            ]);

        $this->assertDatabaseHas('city_management', ['city_name' => 'Bharuch', 'status' => 0]);
    });

    test('newly created city has null deleted_at', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Gandhinagar',
                'status'    => 1,
            ]);

        $city = CityManagement::where('city_name', 'Gandhinagar')->first();
        expect($city)->not->toBeNull()
            ->and($city->deleted_at)->toBeNull();
    });

    test('city is linked to the correct parent state', function () {
        $state = makeCityState('Maharashtra');

        $this->actingAs(cityTestUser(['add-city']))
            ->postJson(route('city.store'), [
                'state_id'  => $state->id,
                'city_name' => 'Pune',
                'status'    => 1,
            ]);

        $city = CityManagement::where('city_name', 'Pune')->first();
        expect($city->state_id)->toBe($state->id);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  5. EDIT (JSON)
 ══════════════════════════════════════════════════════════════════════ */
describe('edit', function () {

    test('returns JSON with all city fields for a valid id', function () {
        $state = makeCityState('Karnataka');
        $city  = makeCityRecord($state, ['city_name' => 'Bengaluru', 'status' => 1]);

        $this->actingAs(cityTestUser())
            ->getJson(route('city.edit', $city))
            ->assertOk()
            ->assertJson([
                'id'        => $city->id,
                'state_id'  => $state->id,
                'city_name' => 'Bengaluru',
                'status'    => 1,
            ]);
    });

    test('returns 404 for a non-existent city id', function () {
        $this->actingAs(cityTestUser())
            ->getJson(route('city.edit', 99999))
            ->assertNotFound();
    });

    test('returns 404 for a soft-deleted city', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);
        $city->delete();

        $this->actingAs(cityTestUser())
            ->getJson(route('city.edit', $city->id))
            ->assertNotFound();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  6. UPDATE — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('update — validation', function () {

    test('state_id is required on update', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), ['city_name' => 'Surat', 'status' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['state_id']);
    });

    test('state_id required on update uses the custom message', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $response = $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), ['city_name' => 'Surat', 'status' => 1])
            ->assertUnprocessable();

        $errors = $response->json('errors.state_id');
        expect($errors)->toContain('The state name field is required.');
    });

    test('city_name is required on update', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), ['state_id' => $state->id, 'city_name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_name']);
    });

    test('city can be updated using its own current name without a uniqueness error', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['city_name' => 'Surat']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => 'Surat',
                'status'    => 0,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    test('city cannot take another active city name', function () {
        $state  = makeCityState();
        $cityA  = makeCityRecord($state, ['city_name' => 'Rajkot']);
        $cityB  = makeCityRecord($state, ['city_name' => 'Jamnagar']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $cityB), [
                'state_id'  => $state->id,
                'city_name' => 'Rajkot',
                'status'    => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city_name']);
    });

    test('soft-deleted city name can be adopted when updating another city', function () {
        $state   = makeCityState();
        $deleted = makeCityRecord($state, ['city_name' => 'Morbi']);
        $deleted->delete();

        $city = makeCityRecord($state, ['city_name' => 'Surendranagar']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => 'Morbi',
                'status'    => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    test('returns 404 when updating a non-existent city', function () {
        $state = makeCityState();

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', 99999), [
                'state_id'  => $state->id,
                'city_name' => 'Unknown City',
                'status'    => 1,
            ])
            ->assertNotFound();
    });

    test('returns 404 when updating a soft-deleted city', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);
        $city->delete();

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city->id), [
                'state_id'  => $state->id,
                'city_name' => 'New Name',
                'status'    => 1,
            ])
            ->assertNotFound();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  7. UPDATE — PERSISTENCE
 ══════════════════════════════════════════════════════════════════════ */
describe('update — persistence', function () {

    test('updates city_name in database', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['city_name' => 'Old City']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => 'New City',
                'status'    => 1,
            ]);

        $this->assertDatabaseHas('city_management',    ['id' => $city->id, 'city_name' => 'New City']);
        $this->assertDatabaseMissing('city_management', ['id' => $city->id, 'city_name' => 'Old City']);
    });

    test('updates status from active to inactive', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['status' => 1]);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => $city->city_name,
                'status'    => 0,
            ]);

        $this->assertDatabaseHas('city_management', ['id' => $city->id, 'status' => 0]);
    });

    test('updates status from inactive to active', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['status' => 0]);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => $city->city_name,
                'status'    => 1,
            ]);

        $this->assertDatabaseHas('city_management', ['id' => $city->id, 'status' => 1]);
    });

    test('can move a city to a different parent state', function () {
        $stateA = makeCityState('State A');
        $stateB = makeCityState('State B');
        $city   = makeCityRecord($stateA, ['city_name' => 'Traveller City']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $stateB->id,
                'city_name' => 'Traveller City',
                'status'    => 1,
            ]);

        $this->assertDatabaseHas('city_management', ['id' => $city->id, 'state_id' => $stateB->id]);
    });

    test('returns JSON success message on update', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $city), [
                'state_id'  => $state->id,
                'city_name' => 'Updated City',
                'status'    => 1,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'City updated successfully']);
    });

    test('updating one city does not affect sibling cities', function () {
        $state   = makeCityState();
        $target  = makeCityRecord($state, ['city_name' => 'Target City']);
        $sibling = makeCityRecord($state, ['city_name' => 'Sibling City']);

        $this->actingAs(cityTestUser(['edit-city']))
            ->putJson(route('city.update', $target), [
                'state_id'  => $state->id,
                'city_name' => 'Renamed Target',
                'status'    => 0,
            ]);

        $this->assertDatabaseHas('city_management', [
            'id'        => $sibling->id,
            'city_name' => 'Sibling City',
            'status'    => 1,
        ]);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  8. DESTROY
 ══════════════════════════════════════════════════════════════════════ */
describe('destroy', function () {

    test('soft-deletes the city', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['city_name' => 'Bhavnagar']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', $city));

        $this->assertSoftDeleted('city_management', ['id' => $city->id]);
    });

    test('does NOT delete the parent state', function () {
        $state = makeCityState('Gujarat');
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', $city));

        $this->assertDatabaseHas('state_management', ['id' => $state->id]);
        expect(StateManagement::where('id', $state->id)->exists())->toBeTrue();
    });

    test('does NOT soft-delete sibling cities in the same state', function () {
        $state   = makeCityState();
        $target  = makeCityRecord($state, ['city_name' => 'Target City']);
        $sibling = makeCityRecord($state, ['city_name' => 'Sibling City']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', $target));

        $this->assertNotSoftDeleted('city_management', ['id' => $sibling->id]);
    });

    test('redirects to city index with success flash after delete', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state);

        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', $city))
            ->assertRedirect(route('city.index'))
            ->assertSessionHas('success');
    });

    test('returns 404 for a non-existent city id', function () {
        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', 99999))
            ->assertNotFound();
    });

    test('soft-deleted city is excluded from normal queries but present in withTrashed', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['city_name' => 'Junagadh']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->delete(route('city.destroy', $city));

        expect(CityManagement::where('id', $city->id)->exists())->toBeFalse();
        expect(CityManagement::withTrashed()->where('id', $city->id)->exists())->toBeTrue();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  9. BULK DELETE
 ══════════════════════════════════════════════════════════════════════ */
describe('bulk delete', function () {

    test('soft-deletes all specified cities and returns JSON success', function () {
        $state = makeCityState();
        $cityA = makeCityRecord($state, ['city_name' => 'City X']);
        $cityB = makeCityRecord($state, ['city_name' => 'City Y']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => [$cityA->id, $cityB->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected City deleted successfully!']);

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('does NOT delete or soft-delete the parent states', function () {
        $state = makeCityState('Safe State');
        $cityA = makeCityRecord($state, ['city_name' => 'City P']);
        $cityB = makeCityRecord($state, ['city_name' => 'City Q']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => [$cityA->id, $cityB->id]]);

        expect(StateManagement::where('id', $state->id)->exists())->toBeTrue();
        $this->assertDatabaseHas('state_management', ['id' => $state->id]);
    });

    test('does not affect cities outside the bulk-delete list', function () {
        $state     = makeCityState();
        $toDelete  = makeCityRecord($state, ['city_name' => 'Deleted City']);
        $untouched = makeCityRecord($state, ['city_name' => 'Safe City']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => [$toDelete->id]]);

        $this->assertNotSoftDeleted('city_management', ['id' => $untouched->id]);
    });

    test('returns 400 when ids array is empty', function () {
        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected!']);
    });

    test('returns 400 when ids key is missing from payload', function () {
        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), [])
            ->assertStatus(400);
    });

    test('bulk-deleting a single city works correctly', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['city_name' => 'Solo City']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => [$city->id]])
            ->assertOk();

        $this->assertSoftDeleted('city_management', ['id' => $city->id]);
    });

    test('cities across different states can be bulk-deleted in one request', function () {
        $stateA = makeCityState('State A');
        $stateB = makeCityState('State B');
        $cityA  = makeCityRecord($stateA, ['city_name' => 'City A1']);
        $cityB  = makeCityRecord($stateB, ['city_name' => 'City B1']);

        $this->actingAs(cityTestUser(['delete-city']))
            ->postJson(route('city.bulkDelete'), ['ids' => [$cityA->id, $cityB->id]])
            ->assertOk();

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);

        // Parent states untouched
        $this->assertNotSoftDeleted('state_management', ['id' => $stateA->id]);
        $this->assertNotSoftDeleted('state_management', ['id' => $stateB->id]);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  10. MODEL BEHAVIOUR
 ══════════════════════════════════════════════════════════════════════ */
describe('model — statusBadge', function () {

    test('active city (status=1) returns a success badge', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['status' => 1]);

        expect($city->statusBadge())
            ->toContain('bg-success')
            ->toContain('Active');
    });

    test('inactive city (status=0) returns a danger badge', function () {
        $state = makeCityState();
        $city  = makeCityRecord($state, ['status' => 0]);

        expect($city->statusBadge())
            ->toContain('bg-danger')
            ->toContain('Inactive');
    });

});

describe('model — state relationship', function () {

    test('state() returns the correct parent StateManagement record', function () {
        $state = makeCityState('Tamil Nadu');
        $city  = makeCityRecord($state, ['city_name' => 'Chennai']);

        expect($city->state->id)->toBe($state->id)
            ->and($city->state->state_name)->toBe('Tamil Nadu');
    });

    test('state() is not affected by soft-deleting the city', function () {
        $state = makeCityState('Telangana');
        $city  = makeCityRecord($state, ['city_name' => 'Hyderabad']);
        $city->delete();

        // Retrieve with trashed to check the relationship on the deleted model
        $retrieved = CityManagement::withTrashed()->find($city->id);
        expect($retrieved->state->id)->toBe($state->id);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  11. CROSS-CONTROLLER — STATE → CITY CASCADE
 ══════════════════════════════════════════════════════════════════════ */
describe('cross-controller cascade — state delete cascades to cities', function () {

    test('deleting a state also soft-deletes all its cities', function () {
        $state = makeCityState('Maharashtra');
        $cityA = makeCityRecord($state, ['city_name' => 'Mumbai']);
        $cityB = makeCityRecord($state, ['city_name' => 'Nagpur']);

        $this->actingAs(cityTestUser(['delete-state']))
            ->delete(route('state.destroy', $state));

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('deleting a state only cascades to its own cities, not cities of other states', function () {
        $stateA = makeCityState('State A');
        $stateB = makeCityState('State B');
        $cityA  = makeCityRecord($stateA, ['city_name' => 'City A1']);
        $cityB  = makeCityRecord($stateB, ['city_name' => 'City B1']);

        $this->actingAs(cityTestUser(['delete-state']))
            ->delete(route('state.destroy', $stateA));

        $this->assertSoftDeleted('city_management',    ['id' => $cityA->id]);
        $this->assertNotSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('bulk-deleting states also soft-deletes all cities of those states', function () {
        $stateA = makeCityState('Bulk State A');
        $stateB = makeCityState('Bulk State B');
        $cityA  = makeCityRecord($stateA, ['city_name' => 'Bulk City A1']);
        $cityB  = makeCityRecord($stateB, ['city_name' => 'Bulk City B1']);

        $this->actingAs(cityTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$stateA->id, $stateB->id]]);

        $this->assertSoftDeleted('city_management', ['id' => $cityA->id]);
        $this->assertSoftDeleted('city_management', ['id' => $cityB->id]);
    });

    test('bulk-deleting states does not cascade to cities of untouched states', function () {
        $toDelete = makeCityState('To Delete');
        $toKeep   = makeCityState('To Keep');
        $safeCity = makeCityRecord($toKeep, ['city_name' => 'Safe City']);

        $this->actingAs(cityTestUser(['delete-state']))
            ->postJson(route('state.bulkDelete'), ['ids' => [$toDelete->id]]);

        $this->assertNotSoftDeleted('city_management', ['id' => $safeCity->id]);
    });

    test('cities of inactive parent state are still deleted when state is destroyed', function () {
        $state = makeCityState('Inactive Parent', 0); // inactive state
        $city  = makeCityRecord($state, ['city_name' => 'Orphan City', 'status' => 1]);

        $this->actingAs(cityTestUser(['delete-state']))
            ->delete(route('state.destroy', $state));

        $this->assertSoftDeleted('city_management', ['id' => $city->id]);
    });

});
