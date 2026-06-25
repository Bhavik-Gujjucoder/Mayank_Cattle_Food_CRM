<?php

use App\Models\Truck;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/*
 * The TruckManagementController::index() always calls User::role('transporter') to
 * build the transporter dropdown — Spatie throws RoleDoesNotExist if the role is
 * absent from the DB.  This beforeEach ensures it exists for every test.
 */
beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']);
});

/* ───────────────────────── Helpers ───────────────────────── */

/** Creates an authenticated user; optionally grants permissions. */
function truckActor(array $permissions = []): User
{
    $user = User::factory()->create();
    if ($permissions) {
        grantPermissions($user, $permissions);
    }
    return $user;
}

/** Creates a user with the 'transporter' role. */
function makeTruckTransporter(array $attrs = []): User
{
    static $seq = 0;
    $seq++;

    $transporter = User::create(array_merge([
        'name'     => "Transporter {$seq}",
        'email'    => "truxtransporter{$seq}@example.com",
        'phone_no' => '9700' . str_pad($seq, 6, '0', STR_PAD_LEFT),
        'password' => Hash::make('password'),
        'status'   => 1,
    ], $attrs));

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $role = Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']);
    $transporter->assignRole($role);

    return $transporter;
}

/** Creates a Truck record directly in the database. */
function makeTruck(array $attrs = []): Truck
{
    static $seq = 0;
    $seq++;

    $transporterId = $attrs['transporter_id'] ?? makeTruckTransporter()->id;

    return Truck::create(array_merge([
        'transporter_id' => $transporterId,
        'truck_number'   => 'TRK' . str_pad($seq, 4, '0', STR_PAD_LEFT),
        'status'         => 1,
    ], $attrs));
}

/* ═══════════════════════════════════════════════════════════ */
/*  ACCESS CONTROL                                            */
/* ═══════════════════════════════════════════════════════════ */
describe('access-control', function () {
    it('redirects guest from truck index', function () {
        $this->get(route('truck.index'))->assertRedirect();
    });

    it('redirects guest from truck store', function () {
        $this->post(route('truck.store'), [])->assertRedirect();
    });

    it('redirects guest from truck update', function () {
        $this->put(route('truck.update', 1), [])->assertRedirect();
    });

    it('redirects guest from truck destroy', function () {
        $this->delete(route('truck.destroy', 1))->assertRedirect();
    });

    it('redirects guest from truck bulk-delete', function () {
        $this->post(route('truck.bulkDelete'), [])->assertRedirect();
    });

    it('any authenticated user can access truck index (no specific permission required)', function () {
        $actor = truckActor(); // no permissions
        $this->actingAs($actor)->get(route('truck.index'))->assertOk();
    });

    it('any authenticated user can access truck edit endpoint (no specific permission required)', function () {
        $truck = makeTruck();
        $actor = truckActor();
        $this->actingAs($actor)
            ->get(route('truck.edit', $truck->id))
            ->assertOk();
    });

    it('authenticated user without add-truck gets 403 on store', function () {
        $actor = truckActor(); // no permissions
        $transporter = makeTruckTransporter();
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01AB1234',
                'status'         => '1',
            ])
            ->assertForbidden();
    });

    it('authenticated user without edit-truck gets 403 on update', function () {
        $truck = makeTruck();
        $actor = truckActor();
        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01AB9999',
                'status'         => '1',
            ])
            ->assertForbidden();
    });

    it('authenticated user without delete-truck gets 403 on destroy', function () {
        $truck = makeTruck();
        $actor = truckActor();
        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id))
            ->assertForbidden();
    });

    it('authenticated user without delete-truck gets 403 on bulk-delete', function () {
        $truck = makeTruck();
        $actor = truckActor();
        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$truck->id]])
            ->assertForbidden();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  INDEX                                                     */
/* ═══════════════════════════════════════════════════════════ */
describe('index', function () {
    it('returns the truck index view for non-AJAX requests', function () {
        $actor = truckActor();
        $this->actingAs($actor)
            ->get(route('truck.index'))
            ->assertOk()
            ->assertViewIs('truck.index');
    });

    it('passes active transporters to the index view', function () {
        $active   = makeTruckTransporter(['status' => 1]);
        $inactive = makeTruckTransporter(['status' => 0]);

        $actor = truckActor();
        $view  = $this->actingAs($actor)->get(route('truck.index'));

        $transporters = $view->viewData('transporters');
        $names        = $transporters->pluck('name');
        expect($names->toArray())->toContain($active->name);
        expect($names->toArray())->not->toContain($inactive->name);
    });

    it('returns DataTables JSON structure on AJAX request', function () {
        $actor = truckActor();
        $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('AJAX listing includes all trucks when no transporter filter is applied', function () {
        $t1 = makeTruck(['truck_number' => 'ALL001']);
        $t2 = makeTruck(['truck_number' => 'ALL002']);

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        expect($json['recordsTotal'])->toBeGreaterThanOrEqual(2);
    });

    it('AJAX listing filters trucks by transporter_id', function () {
        $transporterA = makeTruckTransporter(['name' => 'TransporterAlpha']);
        $transporterB = makeTruckTransporter(['name' => 'TransporterBeta']);

        Truck::create(['transporter_id' => $transporterA->id, 'truck_number' => 'FILTERA001', 'status' => 1]);
        Truck::create(['transporter_id' => $transporterB->id, 'truck_number' => 'FILTERB001', 'status' => 1]);

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(
                route('truck.index') . '?transporter_id=' . $transporterA->id,
                ['X-Requested-With' => 'XMLHttpRequest']
            )
            ->json();

        $truckNumbers = collect($json['data'])->pluck('truck_number');
        expect($truckNumbers->toArray())->toContain('FILTERA001');
        expect($truckNumbers->toArray())->not->toContain('FILTERB001');
    });

    it('AJAX listing shows all trucks when transporter_id is "all"', function () {
        $transporterA = makeTruckTransporter();
        $transporterB = makeTruckTransporter();
        Truck::create(['transporter_id' => $transporterA->id, 'truck_number' => 'ALLA001', 'status' => 1]);
        Truck::create(['transporter_id' => $transporterB->id, 'truck_number' => 'ALLB001', 'status' => 1]);

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(
                route('truck.index') . '?transporter_id=all',
                ['X-Requested-With' => 'XMLHttpRequest']
            )
            ->json();

        expect($json['recordsTotal'])->toBeGreaterThanOrEqual(2);
    });

    it('action column contains edit button when user has edit-truck permission', function () {
        makeTruck(['truck_number' => 'EDITPERM01']);
        $actor = truckActor(['edit-truck']);

        $json    = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('edit-truck-btn');
    });

    it('action column contains delete button when user has delete-truck permission', function () {
        makeTruck(['truck_number' => 'DELPERM01']);
        $actor = truckActor(['delete-truck']);

        $json    = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('deleteTruck');
    });

    it('action column shows a dash when user has neither edit nor delete permission', function () {
        makeTruck(['truck_number' => 'NOPERM001']);
        $actor = truckActor(); // no permissions

        $json    = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('—');
        expect($actions)->not->toContain('edit-truck-btn');
        expect($actions)->not->toContain('deleteTruck');
    });

    it('status column returns HTML badge markup', function () {
        makeTruck(['truck_number' => 'BADGE001', 'status' => 1]);
        $actor = truckActor();

        $json     = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $statuses = collect($json['data'])->pluck('status')->implode(' ');
        expect($statuses)->toContain('badge');
    });

    it('transporter_name column shows the transporter user name', function () {
        $transporter = makeTruckTransporter(['name' => 'VisibleTransporter']);
        Truck::create(['transporter_id' => $transporter->id, 'truck_number' => 'TNAME001', 'status' => 1]);

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $names = collect($json['data'])->pluck('transporter_name');
        expect($names->toArray())->toContain('VisibleTransporter');
    });

    it('AJAX response includes a checkbox column', function () {
        makeTruck(['truck_number' => 'CHKBOX001']);
        $actor = truckActor();

        $json = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $checkboxes = collect($json['data'])->pluck('checkbox')->implode(' ');
        expect($checkboxes)->toContain('truck_checkbox');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  STORE — VALIDATION                                        */
/* ═══════════════════════════════════════════════════════════ */
describe('store-validation', function () {
    it('requires transporter_id with custom message', function () {
        $actor = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'truck_number' => 'GJ01AA1111',
                'status'       => '1',
            ])
            ->assertJsonValidationErrors(['transporter_id' => 'Please select a transporter.']);
    });

    it('rejects non-existent transporter_id with custom message', function () {
        $actor = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => 999999,
                'truck_number'   => 'GJ01AA2222',
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['transporter_id' => 'Selected transporter is invalid.']);
    });

    it('requires truck_number with custom message', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'Truck number is required.']);
    });

    it('rejects truck_number exceeding 50 characters with custom message', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => str_repeat('A', 51),
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'Truck number must not exceed 50 characters.']);
    });

    it('rejects duplicate truck_number with custom message', function () {
        makeTruck(['truck_number' => 'GJ01DUP001']);
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01DUP001',
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'This truck number already exists.']);
    });

    it('allows truck_number of a soft-deleted truck (soft-delete-aware unique)', function () {
        $existing = makeTruck(['truck_number' => 'GJ01SOFT01']);
        $existing->delete(); // soft-delete

        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01SOFT01',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('requires status with custom message', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01AA3333',
            ])
            ->assertJsonValidationErrors(['status' => 'Status is required.']);
    });

    it('rejects status values outside 0 and 1', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01AA4444',
                'status'         => '5',
            ])
            ->assertJsonValidationErrors('status');
    });

    it('accepts status 0 (inactive)', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01AA5555',
                'status'         => '0',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('passes validation for all valid fields', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01AA6666',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJsonMissingValidationErrors();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  STORE — PERSISTENCE                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('store-persistence', function () {
    it('creates a truck record in the database', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01NEW001',
                'status'         => '1',
            ]);

        $this->assertDatabaseHas('trucks', [
            'transporter_id' => $transporter->id,
            'truck_number'   => 'GJ01NEW001',
            'status'         => 1,
        ]);
    });

    it('stores truck_number in uppercase regardless of input case', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'gj01lower',
                'status'         => '1',
            ]);

        $this->assertDatabaseHas('trucks', ['truck_number' => 'GJ01LOWER']);
        $this->assertDatabaseMissing('trucks', ['truck_number' => 'gj01lower']);
    });

    it('trims whitespace from truck_number before saving', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => '  GJ01TRIM  ',
                'status'         => '1',
            ]);

        $this->assertDatabaseHas('trucks', ['truck_number' => 'GJ01TRIM']);
        $this->assertDatabaseMissing('trucks', ['truck_number' => '  GJ01TRIM  ']);
    });

    it('returns JSON with success true on store', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01JSON01',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns the correct success message on store', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01MSG001',
                'status'         => '1',
            ])
            ->assertJson(['message' => 'Truck added successfully.']);
    });

    it('stores inactive truck (status 0) correctly', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01INACT1',
                'status'         => '0',
            ]);

        $this->assertDatabaseHas('trucks', ['truck_number' => 'GJ01INACT1', 'status' => 0]);
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  EDIT                                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('edit', function () {
    it('returns JSON with truck data for an existing truck', function () {
        $truck = makeTruck(['truck_number' => 'GJ01EDIT01', 'status' => 1]);
        $actor = truckActor();

        $this->actingAs($actor)
            ->get(route('truck.edit', $truck->id))
            ->assertOk()
            ->assertJson([
                'id'           => $truck->id,
                'truck_number' => 'GJ01EDIT01',
                'status'       => 1,
            ]);
    });

    it('returns the transporter_id in the JSON response', function () {
        $transporter = makeTruckTransporter();
        $truck       = makeTruck(['transporter_id' => $transporter->id, 'truck_number' => 'GJ01EDIT02']);
        $actor       = truckActor();

        $response = $this->actingAs($actor)->get(route('truck.edit', $truck->id));
        $response->assertJsonPath('transporter_id', $transporter->id);
    });

    it('returns 404 for a non-existent truck id', function () {
        $actor = truckActor();
        $this->actingAs($actor)
            ->get(route('truck.edit', 999999))
            ->assertNotFound();
    });

    it('returns 404 for a soft-deleted truck (route model binding excludes soft-deleted)', function () {
        $truck = makeTruck(['truck_number' => 'GJ01SOFTD1']);
        $truck->delete();

        $actor = truckActor();
        $this->actingAs($actor)
            ->get(route('truck.edit', $truck->id))
            ->assertNotFound();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  UPDATE — VALIDATION                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('update-validation', function () {
    it('requires transporter_id on update with custom message', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'truck_number' => 'GJ01UP0001',
                'status'       => '1',
            ])
            ->assertJsonValidationErrors(['transporter_id' => 'Please select a transporter.']);
    });

    it('rejects non-existent transporter_id on update with custom message', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => 999999,
                'truck_number'   => 'GJ01UP0002',
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['transporter_id' => 'Selected transporter is invalid.']);
    });

    it('requires truck_number on update with custom message', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'Truck number is required.']);
    });

    it('rejects truck_number over 50 characters on update', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => str_repeat('X', 51),
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'Truck number must not exceed 50 characters.']);
    });

    it('rejects duplicate truck_number that belongs to another truck', function () {
        makeTruck(['truck_number' => 'GJ01OTHRTK']);
        $truck = makeTruck(['truck_number' => 'GJ01MINE01']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01OTHRTK',
                'status'         => '1',
            ])
            ->assertJsonValidationErrors(['truck_number' => 'This truck number already exists.']);
    });

    it('allows same truck_number on update (excludes self from unique check)', function () {
        $truck = makeTruck(['truck_number' => 'GJ01SELF01']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01SELF01',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('requires status on update with custom message', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01UP0005',
            ])
            ->assertJsonValidationErrors(['status' => 'Status is required.']);
    });

    it('rejects invalid status value on update', function () {
        $truck = makeTruck();
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01UP0006',
                'status'         => '9',
            ])
            ->assertJsonValidationErrors('status');
    });

    it('returns 404 when updating a non-existent truck', function () {
        $transporter = makeTruckTransporter();
        $actor       = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', 999999), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01UP0007',
                'status'         => '1',
            ])
            ->assertNotFound();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  UPDATE — PERSISTENCE                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('update-persistence', function () {
    it('updates transporter_id, truck_number, and status', function () {
        $oldTransporter = makeTruckTransporter();
        $newTransporter = makeTruckTransporter();
        $truck          = makeTruck([
            'transporter_id' => $oldTransporter->id,
            'truck_number'   => 'GJ01OLD001',
            'status'         => 1,
        ]);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $newTransporter->id,
                'truck_number'   => 'GJ01NEW002',
                'status'         => '0',
            ]);

        $this->assertDatabaseHas('trucks', [
            'id'             => $truck->id,
            'transporter_id' => $newTransporter->id,
            'truck_number'   => 'GJ01NEW002',
            'status'         => 0,
        ]);
    });

    it('stores updated truck_number in uppercase', function () {
        $truck = makeTruck(['truck_number' => 'GJ01UPCASE']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'gj01newlow',
                'status'         => '1',
            ]);

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'truck_number' => 'GJ01NEWLOW']);
    });

    it('trims whitespace from truck_number on update', function () {
        $truck = makeTruck(['truck_number' => 'GJ01UPTRIM']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => '  GJ01TRIMMED  ',
                'status'         => '1',
            ]);

        $this->assertDatabaseHas('trucks', ['id' => $truck->id, 'truck_number' => 'GJ01TRIMMED']);
    });

    it('returns JSON with success true on update', function () {
        $truck = makeTruck(['truck_number' => 'GJ01UPJSON']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01UPJSN2',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('returns the correct success message on update', function () {
        $truck = makeTruck(['truck_number' => 'GJ01UPMSG1']);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => 'GJ01UPMSG2',
                'status'         => '1',
            ])
            ->assertJson(['message' => 'Truck updated successfully.']);
    });

    it('can change truck status from active to inactive on update', function () {
        $truck = makeTruck(['truck_number' => 'GJ01TOGGLE', 'status' => 1]);
        $actor = truckActor(['edit-truck']);

        $this->actingAs($actor)
            ->putJson(route('truck.update', $truck->id), [
                'transporter_id' => $truck->transporter_id,
                'truck_number'   => $truck->truck_number,
                'status'         => '0',
            ]);

        $truck->refresh();
        expect($truck->status)->toBe(0);
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  DESTROY                                                   */
/* ═══════════════════════════════════════════════════════════ */
describe('destroy', function () {
    it('returns 404 for a non-existent truck id', function () {
        $actor = truckActor(['delete-truck']);
        $this->actingAs($actor)
            ->delete(route('truck.destroy', 999999))
            ->assertNotFound();
    });

    it('soft-deletes the truck (deleted_at is set)', function () {
        $truck = makeTruck(['truck_number' => 'GJ01SOFTK1']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id));

        $this->assertSoftDeleted('trucks', ['id' => $truck->id]);
    });

    it('soft-deleted truck still exists in the database', function () {
        $truck = makeTruck(['truck_number' => 'GJ01STILLD']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id));

        $this->assertDatabaseHas('trucks', ['id' => $truck->id]);
    });

    it('redirects to truck.index after destroy', function () {
        $truck = makeTruck(['truck_number' => 'GJ01REDIR1']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id))
            ->assertRedirect(route('truck.index'));
    });

    it('sets a success flash message after destroy', function () {
        $truck = makeTruck(['truck_number' => 'GJ01FLASH1']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id))
            ->assertSessionHas('success', 'Truck deleted successfully.');
    });

    it('returns 404 for a previously soft-deleted truck (route model binding)', function () {
        $truck = makeTruck(['truck_number' => 'GJ01DELDEL']);
        $truck->delete(); // soft-delete first

        $actor = truckActor(['delete-truck']);
        $this->actingAs($actor)
            ->delete(route('truck.destroy', $truck->id))
            ->assertNotFound();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  BULK DELETE                                               */
/* ═══════════════════════════════════════════════════════════ */
describe('bulkDelete', function () {
    it('returns 400 JSON when ids array is empty', function () {
        $actor = truckActor(['delete-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('returns 400 JSON when ids is not provided', function () {
        $actor = truckActor(['delete-truck']);
        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), [])
            ->assertStatus(400);
    });

    it('soft-deletes all specified trucks', function () {
        $t1    = makeTruck(['truck_number' => 'GJ01BULK01']);
        $t2    = makeTruck(['truck_number' => 'GJ01BULK02']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$t1->id, $t2->id]]);

        $this->assertSoftDeleted('trucks', ['id' => $t1->id]);
        $this->assertSoftDeleted('trucks', ['id' => $t2->id]);
    });

    it('soft-deleted trucks still exist in the database with deleted_at set', function () {
        $t1    = makeTruck(['truck_number' => 'GJ01BLKDB1']);
        $t2    = makeTruck(['truck_number' => 'GJ01BLKDB2']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$t1->id, $t2->id]]);

        $this->assertDatabaseHas('trucks', ['id' => $t1->id]);
        $this->assertDatabaseHas('trucks', ['id' => $t2->id]);
    });

    it('returns JSON success message on bulk delete', function () {
        $truck = makeTruck(['truck_number' => 'GJ01BLKMSG']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$truck->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected trucks deleted successfully.']);
    });

    it('only soft-deletes requested trucks and leaves others intact', function () {
        $toDelete  = makeTruck(['truck_number' => 'GJ01DELME1']);
        $toKeep    = makeTruck(['truck_number' => 'GJ01KEEPME']);
        $actor     = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$toDelete->id]]);

        $this->assertSoftDeleted('trucks', ['id' => $toDelete->id]);
        $this->assertNotSoftDeleted('trucks', ['id' => $toKeep->id]);
    });

    it('silently ignores non-existent ids in bulk delete', function () {
        $truck = makeTruck(['truck_number' => 'GJ01BNEXST']);
        $actor = truckActor(['delete-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.bulkDelete'), ['ids' => [$truck->id, 999999]])
            ->assertOk()
            ->assertJson(['message' => 'Selected trucks deleted successfully.']);
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MODEL — statusBadge                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('model-statusBadge', function () {
    it('returns a green active badge for status 1', function () {
        $truck = makeTruck(['status' => 1]);
        expect($truck->statusBadge())->toContain('bg-success');
        expect($truck->statusBadge())->toContain('Active');
    });

    it('returns a red inactive badge for status 0', function () {
        $truck = makeTruck(['status' => 0]);
        expect($truck->statusBadge())->toContain('bg-danger');
        expect($truck->statusBadge())->toContain('Inactive');
    });

    it('badge contains the badge-pill class', function () {
        $truck = makeTruck(['status' => 1]);
        expect($truck->statusBadge())->toContain('badge-pill');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MODEL — transporter relationship                          */
/* ═══════════════════════════════════════════════════════════ */
describe('model-transporter-relationship', function () {
    it('returns the associated transporter user via the relationship', function () {
        $transporter = makeTruckTransporter(['name' => 'RelationTransporter']);
        $truck       = makeTruck(['transporter_id' => $transporter->id]);

        expect($truck->transporter->id)->toBe($transporter->id);
        expect($truck->transporter->name)->toBe('RelationTransporter');
    });

    it('returns null when transporter is deleted or missing', function () {
        $transporter = makeTruckTransporter();
        $truck       = makeTruck(['transporter_id' => $transporter->id]);

        // Hard-delete the transporter to simulate orphaned truck
        $transporter->forceDelete();
        $truck->refresh();

        expect($truck->transporter)->toBeNull();
    });

    it('transporter_name in AJAX shows dash when transporter does not exist', function () {
        // Create truck with an orphaned transporter_id
        $transporter = makeTruckTransporter();
        $truckOrphan = makeTruck(['transporter_id' => $transporter->id, 'truck_number' => 'GJ01ORPHAN']);
        $transporter->forceDelete();

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $truckRow = collect($json['data'])
            ->first(fn($row) => $row['truck_number'] === 'GJ01ORPHAN');

        expect($truckRow['transporter_name'])->toBe('—');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  SOFT-DELETE BEHAVIOUR                                     */
/* ═══════════════════════════════════════════════════════════ */
describe('soft-delete-behaviour', function () {
    it('soft-deleted trucks do not appear in the AJAX DataTables listing', function () {
        $visible = makeTruck(['truck_number' => 'GJ01VISTRK']);
        $hidden  = makeTruck(['truck_number' => 'GJ01HIDTRK']);
        $hidden->delete(); // soft-delete

        $actor = truckActor();
        $json  = $this->actingAs($actor)
            ->get(route('truck.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $truckNumbers = collect($json['data'])->pluck('truck_number');
        expect($truckNumbers->toArray())->toContain('GJ01VISTRK');
        expect($truckNumbers->toArray())->not->toContain('GJ01HIDTRK');
    });

    it('truck_number of a soft-deleted truck can be reused by a new truck', function () {
        $old = makeTruck(['truck_number' => 'GJ01REUSE1']);
        $old->delete();

        $transporter = makeTruckTransporter();
        $actor       = truckActor(['add-truck']);

        $this->actingAs($actor)
            ->postJson(route('truck.store'), [
                'transporter_id' => $transporter->id,
                'truck_number'   => 'GJ01REUSE1',
                'status'         => '1',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Two records exist for this truck number; one soft-deleted, one active
        $allRecords = Truck::withTrashed()->where('truck_number', 'GJ01REUSE1')->count();
        expect($allRecords)->toBe(2);
    });
});
