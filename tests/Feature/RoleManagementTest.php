<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/* ─────────────────────────────────────────────────────────────────────────
 |  Helpers
 ───────────────────────────────────────────────────────────────────────── */

/** Create a user and assign Spatie roles. */
function roleTestUser(array $roleNames = []): User
{
    $user = User::factory()->create();

    if (!empty($roleNames)) {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($roleNames as $name) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $user->assignRole($role);
        }
    }

    return $user;
}

/** Create a Role record (not 'super admin' unless requested). */
function makeTestRole(string $name = 'editor'): Role
{
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

/** Create a Spatie Permission record. */
function makeRolePerm(string $name): Permission
{
    return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

/* ═══════════════════════════════════════════════════════════════════════
 |  1. ACCESS CONTROL — role:super admin|admin middleware
 ══════════════════════════════════════════════════════════════════════ */
describe('access control', function () {

    test('guest is redirected to login from index', function () {
        $this->get(route('roles.index'))
            ->assertRedirect(route('login'));
    });

    test('guest is redirected to login from create', function () {
        $this->get(route('roles.create'))
            ->assertRedirect(route('login'));
    });

    test('guest cannot POST to store', function () {
        $this->post(route('roles.store'), ['name' => 'editor'])
            ->assertRedirect(route('login'));
    });

    test('guest cannot access edit', function () {
        $role = makeTestRole();

        $this->get(route('roles.edit', $role))
            ->assertRedirect(route('login'));
    });

    test('guest cannot PUT to update', function () {
        $role = makeTestRole();

        $this->put(route('roles.update', $role), ['name' => 'new-name'])
            ->assertRedirect(route('login'));
    });

    test('guest cannot DELETE a role', function () {
        $role = makeTestRole();

        $this->delete(route('roles.destroy', $role))
            ->assertRedirect(route('login'));
    });

    test('authenticated user with no role is denied — 403', function () {
        $this->actingAs(roleTestUser())
            ->get(route('roles.index'))
            ->assertForbidden();
    });

    test('user with staff role is denied — 403', function () {
        $this->actingAs(roleTestUser(['staff']))
            ->get(route('roles.index'))
            ->assertForbidden();
    });

    test('user with broker role is denied — 403', function () {
        $this->actingAs(roleTestUser(['broker']))
            ->get(route('roles.index'))
            ->assertForbidden();
    });

    test('user with admin role can access index', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'))
            ->assertOk();
    });

    test('user with super admin role can access index', function () {
        $this->actingAs(roleTestUser(['super admin']))
            ->get(route('roles.index'))
            ->assertOk();
    });

    test('user with admin role can access create', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.create'))
            ->assertOk();
    });

    test('user with admin role can access edit', function () {
        $role = makeTestRole();

        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.edit', $role))
            ->assertOk();
    });

    test('user with no role cannot store a role — 403', function () {
        $this->actingAs(roleTestUser())
            ->post(route('roles.store'), ['name' => 'reporter'])
            ->assertForbidden();
    });

    test('user with no role cannot update a role — 403', function () {
        $role = makeTestRole();

        $this->actingAs(roleTestUser())
            ->put(route('roles.update', $role), ['name' => 'reporter'])
            ->assertForbidden();
    });

    test('user with no role cannot delete a role — 403', function () {
        $role = makeTestRole();

        $this->actingAs(roleTestUser())
            ->delete(route('roles.destroy', $role))
            ->assertForbidden();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  2. INDEX
 ══════════════════════════════════════════════════════════════════════ */
describe('index', function () {

    test('renders roles.index view with page_title', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'))
            ->assertOk()
            ->assertViewIs('roles.index')
            ->assertViewHas('page_title', 'Role & Permissions');
    });

    test('AJAX request returns DataTables-compatible JSON', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    });

    test('super admin role is excluded from the AJAX listing', function () {
        makeTestRole('super admin');
        makeTestRole('editor');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $names = collect($response->json('data'))->pluck('name');
        expect($names)->not->toContain('super admin')
            ->and($names)->toContain('editor');
    });

    test('custom roles appear in the AJAX listing', function () {
        makeTestRole('manager');
        makeTestRole('auditor');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('manager')
            ->and($names)->toContain('auditor');
    });

    test('AJAX data rows include action and permission_name columns', function () {
        makeTestRole('qa-lead');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $row = collect($response->json('data'))->firstWhere('name', 'qa-lead');
        expect($row)->toHaveKey('action')
            ->and($row)->toHaveKey('permission_name');
    });

    test('action column contains the Edit link pointing to roles.edit', function () {
        $role = makeTestRole('ops-lead');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $row = collect($response->json('data'))->firstWhere('name', 'ops-lead');
        expect($row['action'])->toContain(route('roles.edit', $role->id));
    });

    test('permission_name column shows badge HTML for each assigned permission', function () {
        $perm = makeRolePerm('view-reports');
        $role = makeTestRole('reporter');
        $role->givePermissionTo($perm);

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $row = collect($response->json('data'))->firstWhere('name', 'reporter');
        expect($row['permission_name'])
            ->toContain('badge')
            ->toContain('View Reports');
    });

    test('permission_name column is empty string when role has no permissions', function () {
        makeTestRole('empty-role');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $row = collect($response->json('data'))->firstWhere('name', 'empty-role');
        expect($row['permission_name'])->toBe('');
    });

    test('AJAX listing is empty when only the super admin role exists', function () {
        // Use a super admin actor: only the 'super admin' role is created, and it is
        // excluded from the listing by Role::where('name','!=','super admin').
        $response = $this->actingAs(roleTestUser(['super admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        expect($response->json('recordsTotal'))->toBe(0);
    });

    test('super admin user sees the same listing as admin user', function () {
        makeTestRole('reviewer');

        $adminResponse = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $saResponse = $this->actingAs(roleTestUser(['super admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        expect($adminResponse->json('recordsTotal'))->toBe($saResponse->json('recordsTotal'));
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  3. CREATE
 ══════════════════════════════════════════════════════════════════════ */
describe('create', function () {

    test('renders roles.create view with correct page_title', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.create'))
            ->assertOk()
            ->assertViewIs('roles.create')
            ->assertViewHas('page_title', 'Add Role & Permission');
    });

    test('create view receives permissions grouped by type', function () {
        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.create'))
            ->assertOk();

        $response->assertViewHas('permissions');
    });

    test('create view receives dashboard_permissions array', function () {
        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.create'))
            ->assertOk();

        $response->assertViewHas('dashboard_permissions');
    });

    test('super admin can access the create page', function () {
        $this->actingAs(roleTestUser(['super admin']))
            ->get(route('roles.create'))
            ->assertOk();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  4. STORE — VALIDATION
 ══════════════════════════════════════════════════════════════════════ */
describe('store — validation', function () {

    test('name is required with custom message', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The role name field is required.']);
    });

    test('name missing from payload uses the custom required message', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), [])
            ->assertSessionHasErrors(['name' => 'The role name field is required.']);
    });

    test('name must be unique — duplicate rejected with custom message', function () {
        makeTestRole('editor');

        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'editor'])
            ->assertSessionHasErrors(['name' => 'The role name has already been taken.']);
    });

    test('duplicate name validation does not create a second record', function () {
        // Create actor first so its role is already in the count baseline.
        $actor = roleTestUser(['admin']);
        makeTestRole('analyst');
        $countBefore = Role::count();

        $this->actingAs($actor)
            ->post(route('roles.store'), ['name' => 'analyst']);

        expect(Role::count())->toBe($countBefore);
    });

    test('validation error does not create any role', function () {
        // Create actor first so its role is already in the count baseline.
        $actor = roleTestUser(['admin']);
        $countBefore = Role::count();

        $this->actingAs($actor)
            ->post(route('roles.store'), ['name' => '']);

        expect(Role::count())->toBe($countBefore);
    });

    test('name with only spaces fails required validation', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => '   '])
            ->assertSessionHasErrors(['name']);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  5. STORE — PERSISTENCE & RESPONSE
 ══════════════════════════════════════════════════════════════════════ */
describe('store — persistence', function () {

    test('creates a role and redirects to roles.index with success flash', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'dispatcher'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role created successfully.');

        $this->assertDatabaseHas('roles', ['name' => 'dispatcher']);
    });

    test('role is stored with guard_name "web" by default', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'inventory-manager']);

        $this->assertDatabaseHas('roles', ['name' => 'inventory-manager', 'guard_name' => 'web']);
    });

    test('role created without permissions has no permissions', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'no-perm-role']);

        $role = Role::where('name', 'no-perm-role')->first();
        expect($role->permissions)->toBeEmpty();
    });

    test('role created with permissions has those permissions assigned', function () {
        $p1 = makeRolePerm('add-city');
        $p2 = makeRolePerm('delete-city');

        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), [
                'name'        => 'city-manager',
                'permissions' => [$p1->name, $p2->name],
            ]);

        $role = Role::where('name', 'city-manager')->first();
        expect($role->permissions->pluck('name')->toArray())
            ->toContain('add-city')
            ->toContain('delete-city');
    });

    test('multiple distinct roles can be created', function () {
        $user = roleTestUser(['admin']);

        $this->actingAs($user)->post(route('roles.store'), ['name' => 'role-alpha']);
        $this->actingAs($user)->post(route('roles.store'), ['name' => 'role-beta']);

        $this->assertDatabaseHas('roles', ['name' => 'role-alpha']);
        $this->assertDatabaseHas('roles', ['name' => 'role-beta']);
    });

    test('super admin can also create a role', function () {
        $this->actingAs(roleTestUser(['super admin']))
            ->post(route('roles.store'), ['name' => 'super-created-role'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('roles', ['name' => 'super-created-role']);
    });

    test('a previously hard-deleted role name can be reused', function () {
        $role = makeTestRole('temp-role');
        $role->delete(); // hard delete — name is free

        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'temp-role'])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'temp-role']);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  6. EDIT
 ══════════════════════════════════════════════════════════════════════ */
describe('edit', function () {

    test('renders roles.edit view with correct page_title and role', function () {
        $role = makeTestRole('content-editor');

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertViewIs('roles.edit')
            ->assertViewHas('page_title', 'Edit Role & Permission');

        expect($response->viewData('role')->id)->toBe($role->id);
    });

    test('edit view receives permissions and dashboard_permissions', function () {
        $role = makeTestRole('viewer');

        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertViewHas('permissions')
            ->assertViewHas('dashboard_permissions');
    });

    test('returns 404 for a non-existent role id', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.edit', 99999))
            ->assertNotFound();
    });

    test('super admin can access edit', function () {
        $role = makeTestRole('target-role');

        $this->actingAs(roleTestUser(['super admin']))
            ->get(route('roles.edit', $role))
            ->assertOk();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  7. UPDATE — CUSTOM ROLES (name IS updatable)
 ══════════════════════════════════════════════════════════════════════ */
describe('update — custom roles', function () {

    test('name is required for a custom role', function () {
        $role = makeTestRole('custom-one');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The role name field is required.']);
    });

    test('name missing from payload triggers required error for custom role', function () {
        $role = makeTestRole('custom-two');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [])
            ->assertSessionHasErrors(['name']);
    });

    test('name must be unique — cannot take another custom role name', function () {
        makeTestRole('existing-role');
        $role = makeTestRole('another-role');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'existing-role'])
            ->assertSessionHasErrors(['name' => 'The role name has already been taken.']);
    });

    test('custom role can keep its own name without uniqueness error', function () {
        $role = makeTestRole('self-name-role');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'self-name-role'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('roles.index'));
    });

    test('custom role name is updated in database', function () {
        $role = makeTestRole('old-custom-name');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'new-custom-name']);

        $this->assertDatabaseHas('roles',    ['id' => $role->id, 'name' => 'new-custom-name']);
        $this->assertDatabaseMissing('roles', ['id' => $role->id, 'name' => 'old-custom-name']);
    });

    test('update redirects to roles.index with success flash', function () {
        $role = makeTestRole('redirect-test-role');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'redirect-test-role-v2'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role updated successfully.');
    });

    test('custom role permissions are synced on update', function () {
        $p1   = makeRolePerm('view-state');
        $p2   = makeRolePerm('edit-state');
        $role = makeTestRole('state-role');
        $role->givePermissionTo($p1); // currently has p1

        // Update syncing to only p2
        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'name'        => 'state-role',
                'permissions' => [$p2->id],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions->pluck('name')->toArray())
            ->not->toContain('view-state')
            ->toContain('edit-state');
    });

    test('existing permissions are preserved when no permissions key is sent', function () {
        $perm = makeRolePerm('preserve-perm');
        $role = makeTestRole('preserve-role');
        $role->givePermissionTo($perm);

        // Update with name only — no permissions key
        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'preserve-role']);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions->pluck('name')->toArray())->toContain('preserve-perm');
    });

    test('sending empty permissions array does not clear existing permissions', function () {
        // if ($request->permissions) is falsy for [], so syncPermissions is never called
        $perm = makeRolePerm('sticky-perm');
        $role = makeTestRole('sticky-role');
        $role->givePermissionTo($perm);

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'name'        => 'sticky-role',
                'permissions' => [],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions->pluck('name')->toArray())->toContain('sticky-perm');
    });

    test('returns 404 when updating a non-existent role', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', 99999), ['name' => 'ghost'])
            ->assertNotFound();
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  8. UPDATE — PROTECTED ROLES (name is NOT updatable)
 ══════════════════════════════════════════════════════════════════════ */
describe('update — protected roles', function () {

    test('admin role name is not updated even when a new name is submitted', function () {
        $role = makeTestRole('admin');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'super-admin-renamed']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'admin']);
        $this->assertDatabaseMissing('roles', ['id' => $role->id, 'name' => 'super-admin-renamed']);
    });

    test('staff role name cannot be changed', function () {
        $role = makeTestRole('staff');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'renamed-staff']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'staff']);
    });

    test('broker role name cannot be changed', function () {
        $role = makeTestRole('broker');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'renamed-broker']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'broker']);
    });

    test('transporter role name cannot be changed', function () {
        $role = makeTestRole('transporter');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'renamed-transporter']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'transporter']);
    });

    test('dealer role name cannot be changed', function () {
        $role = makeTestRole('dealer');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'renamed-dealer']);

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'dealer']);
    });

    test('name field is nullable for protected roles — empty name passes validation', function () {
        $role = makeTestRole('staff');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => ''])
            ->assertSessionHasNoErrors();
    });

    test('name field can be omitted entirely for protected roles', function () {
        $role = makeTestRole('broker');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), []) // no name key at all
            ->assertSessionHasNoErrors();
    });

    test('permissions are synced for protected roles even though name is locked', function () {
        $perm = makeRolePerm('view-dashboard');
        $role = makeTestRole('staff');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'permissions' => [$perm->id],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions->pluck('name')->toArray())->toContain('view-dashboard');
    });

    test('protected role update redirects with success flash', function () {
        $role = makeTestRole('dealer');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), ['name' => 'whatever'])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role updated successfully.');
    });

    test('all five protected role names are locked', function () {
        $actor     = roleTestUser(['admin']);
        $protected = ['admin', 'staff', 'broker', 'transporter', 'dealer'];

        foreach ($protected as $roleName) {
            $role = makeTestRole($roleName);

            $this->actingAs($actor)
                ->put(route('roles.update', $role), ['name' => 'renamed-' . $roleName]);

            // assertDatabaseHas third arg is the DB connection — pass null (default).
            $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $roleName]);
        }
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  9. DESTROY
 ══════════════════════════════════════════════════════════════════════ */
describe('destroy', function () {

    test('hard-deletes the role record', function () {
        $role = makeTestRole('to-delete-role');

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    });

    test('redirects to roles.index with success flash after deletion', function () {
        $role = makeTestRole('flash-delete-role');

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role deleted successfully.');
    });

    test('role is completely removed — cannot be found by id', function () {
        $role = makeTestRole('gone-role');
        $id   = $role->id;

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role));

        expect(Role::find($id))->toBeNull();
    });

    test('returns 404 when deleting a non-existent role', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', 99999))
            ->assertNotFound();
    });

    test('deleting one role does not affect other roles', function () {
        $toDelete = makeTestRole('delete-me-role');
        $toKeep   = makeTestRole('keep-me-role');

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $toDelete));

        $this->assertDatabaseHas('roles', ['id' => $toKeep->id, 'name' => 'keep-me-role']);
    });

    test('role count decrements by one after deletion', function () {
        // Create actor first so its role is already in the count baseline.
        $actor = roleTestUser(['admin']);
        makeTestRole('count-role-a');
        makeTestRole('count-role-b');
        $toDelete    = makeTestRole('count-role-c');
        $countBefore = Role::count();

        $this->actingAs($actor)
            ->delete(route('roles.destroy', $toDelete));

        expect(Role::count())->toBe($countBefore - 1);
    });

    test('deleted role name can be immediately reused', function () {
        $role = makeTestRole('reuse-me-role');

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role));

        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'reuse-me-role'])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'reuse-me-role']);
    });

    test('deleting a role that has permissions removes the role-permission pivot rows', function () {
        $perm = makeRolePerm('delete-with-perm');
        $role = makeTestRole('role-with-perm');
        $role->givePermissionTo($perm);

        expect($role->permissions)->toHaveCount(1);

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role));

        // role_has_permissions rows cascade-deleted by DB foreign key
        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => $role->id]);
    });

    test('super admin can delete a role', function () {
        $role = makeTestRole('super-deletes-this');

        $this->actingAs(roleTestUser(['super admin']))
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success', 'Role deleted successfully.');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    });

});

/* ═══════════════════════════════════════════════════════════════════════
 |  10. EDGE CASES & CROSS-CUTTING BUSINESS LOGIC
 ══════════════════════════════════════════════════════════════════════ */
describe('edge cases', function () {

    test('super admin role is never listed in AJAX index regardless of how many exist', function () {
        makeTestRole('super admin');
        makeTestRole('super admin'); // firstOrCreate means just one record

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $names = collect($response->json('data'))->pluck('name');
        expect($names)->not->toContain('super admin');
    });

    test('a role assigned to a user can still be deleted (hard delete cascade)', function () {
        $role     = makeTestRole('assigned-to-user-role');
        $targetUser = User::factory()->create();
        $targetUser->assignRole($role);

        $this->actingAs(roleTestUser(['admin']))
            ->delete(route('roles.destroy', $role));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        // model_has_roles pivot row removed by cascade
        $this->assertDatabaseMissing('model_has_roles', ['role_id' => $role->id]);
    });

    test('syncPermissions replaces all permissions when updating with new set', function () {
        $p1   = makeRolePerm('old-perm-1');
        $p2   = makeRolePerm('old-perm-2');
        $p3   = makeRolePerm('new-perm-3');
        $role = makeTestRole('sync-test-role');
        $role->givePermissionTo([$p1, $p2]);

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'name'        => 'sync-test-role',
                'permissions' => [$p3->id],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        $permNames = $role->permissions->pluck('name')->toArray();
        expect($permNames)->not->toContain('old-perm-1')
            ->not->toContain('old-perm-2')
            ->toContain('new-perm-3');
    });

    test('role with hyphens and underscores in name stores correctly', function () {
        $this->actingAs(roleTestUser(['admin']))
            ->post(route('roles.store'), ['name' => 'raw_material-manager_v2'])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'raw_material-manager_v2']);
    });

    test('permissions column in update accepts an array of IDs and resolves them correctly', function () {
        $perm = makeRolePerm('id-based-perm');
        $role = makeTestRole('id-test-role');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'name'        => 'id-test-role',
                'permissions' => [$perm->id],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions->pluck('id')->toArray())->toContain($perm->id);
    });

    test('update with non-existent permission IDs assigns no permissions', function () {
        $role = makeTestRole('bad-perm-role');

        $this->actingAs(roleTestUser(['admin']))
            ->put(route('roles.update', $role), [
                'name'        => 'bad-perm-role',
                'permissions' => [99999, 88888],
            ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $role->refresh();

        expect($role->permissions)->toBeEmpty();
    });

    test('permission_name badges use ucwords formatting of hyphenated names', function () {
        $perm = makeRolePerm('manage-raw-materials');
        $role = makeTestRole('raw-role');
        $role->givePermissionTo($perm);

        $response = $this->actingAs(roleTestUser(['admin']))
            ->get(route('roles.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $row = collect($response->json('data'))->firstWhere('name', 'raw-role');
        expect($row['permission_name'])->toContain('Manage Raw Materials');
    });

});
