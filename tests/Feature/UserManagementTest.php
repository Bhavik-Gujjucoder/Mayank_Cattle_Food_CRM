<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/* ───────────────────────── Helpers ───────────────────────── */

/** Creates an authenticated user; optionally grants direct permissions. */
function userCtrlActor(array $permissions = []): User
{
    $user = User::factory()->create();
    if ($permissions) {
        grantPermissions($user, $permissions);
    }
    return $user;
}

/** Creates a raw user record for CRUD target operations. */
function userCtrlTarget(array $attrs = []): User
{
    static $seq = 0;
    $seq++;
    return User::create(array_merge([
        'name'     => "Target User {$seq}",
        'email'    => "target{$seq}@example.com",
        'phone_no' => '9876' . str_pad($seq, 6, '0', STR_PAD_LEFT),
        'password' => Hash::make('password123'),
        'status'   => 1,
    ], $attrs));
}

/** Ensures a Spatie role exists and returns it. */
function userCtrlRole(string $name): Role
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
}

/* ═══════════════════════════════════════════════════════════ */
/*  ACCESS CONTROL                                            */
/* ═══════════════════════════════════════════════════════════ */
describe('access-control', function () {
    it('redirects guest from broker index', function () {
        $this->get(route('users.index', 'broker'))->assertRedirect();
    });

    it('redirects guest from transporter index', function () {
        $this->get(route('users.index', 'transporter'))->assertRedirect();
    });

    it('redirects guest from user index', function () {
        $this->get(route('users.index', 'user'))->assertRedirect();
    });

    it('redirects guest from create', function () {
        $this->get(route('users.create', 'broker'))->assertRedirect();
    });

    it('redirects guest from store', function () {
        $this->post(route('users.store', 'broker'), [])->assertRedirect();
    });

    it('redirects guest from destroy', function () {
        $this->delete(route('users.destroy', ['type' => 'broker', 'id' => 1]))->assertRedirect();
    });

    it('redirects guest from show', function () {
        $target = userCtrlTarget();
        $this->get(route('users.show', ['id' => $target->id, 'type' => 'broker']))->assertRedirect();
    });

    it('redirects guest from bulk-delete', function () {
        $this->post(route('user.bulkDelete', 'broker'), [])->assertRedirect();
    });

    it('authenticated user can access broker index page', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'broker'))
            ->assertOk();
    });

    it('authenticated user can access transporter index page', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'transporter'))
            ->assertOk();
    });

    it('authenticated user can access user index page', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'user'))
            ->assertOk();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  BROKER QUICK-CREATE FORM                                  */
/* ═══════════════════════════════════════════════════════════ */
describe('brokerQuickCreateForm', function () {
    it('redirects guest from broker quick-create form', function () {
        $this->get(route('users.broker.quickCreateForm'))->assertRedirect();
    });

    it('returns 403 for user without add-broker permission', function () {
        $actor = userCtrlActor(); // no permissions
        $this->actingAs($actor)
            ->get(route('users.broker.quickCreateForm'))
            ->assertForbidden();
    });

    it('returns view for user with add-broker permission', function () {
        $actor = userCtrlActor(['add-broker']);
        $this->actingAs($actor)
            ->get(route('users.broker.quickCreateForm'))
            ->assertOk()
            ->assertViewIs('users.partials.quick-create-broker-form');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  INDEX                                                     */
/* ═══════════════════════════════════════════════════════════ */
describe('index', function () {
    it('returns DataTables JSON for broker type via AJAX', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('returns DataTables JSON for transporter type via AJAX', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'transporter'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('returns DataTables JSON for user type via AJAX', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.index', 'user'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('broker AJAX listing only includes users with broker role', function () {
        $brokerRole      = userCtrlRole('broker');
        $transporterRole = userCtrlRole('transporter');

        $broker      = userCtrlTarget(['name' => 'Broker Visible']);
        $transporter = userCtrlTarget(['name' => 'Transporter Hidden']);
        $broker->assignRole($brokerRole);
        $transporter->assignRole($transporterRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $names = collect($json['data'])->pluck('name')->map(fn($html) => strip_tags($html))->all();
        expect(implode(',', $names))->toContain('Broker Visible');
        expect(implode(',', $names))->not->toContain('Transporter Hidden');
    });

    it('transporter AJAX listing only includes users with transporter role', function () {
        $brokerRole      = userCtrlRole('broker');
        $transporterRole = userCtrlRole('transporter');

        $broker      = userCtrlTarget(['name' => 'Broker Excluded']);
        $transporter = userCtrlTarget(['name' => 'Transporter Included']);
        $broker->assignRole($brokerRole);
        $transporter->assignRole($transporterRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'transporter'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $names = collect($json['data'])->pluck('name')->map(fn($html) => strip_tags($html))->all();
        expect(implode(',', $names))->toContain('Transporter Included');
        expect(implode(',', $names))->not->toContain('Broker Excluded');
    });

    it('user-type listing includes admin and staff but excludes broker users', function () {
        $adminRole  = userCtrlRole('admin');
        $brokerRole = userCtrlRole('broker');

        $admin  = userCtrlTarget(['name' => 'AdminVisible']);
        $broker = userCtrlTarget(['name' => 'BrokerExcluded']);
        $admin->assignRole($adminRole);
        $broker->assignRole($brokerRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'user'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $names = collect($json['data'])->pluck('name')->map(fn($html) => strip_tags($html))->all();
        expect(implode(',', $names))->toContain('AdminVisible');
        expect(implode(',', $names))->not->toContain('BrokerExcluded');
    });

    it('action column contains edit link when user has edit-broker permission', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'BrokerForEdit']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor(['edit-broker']);
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('edit-btn');
    });

    it('action column contains delete button when user has delete-broker permission', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'BrokerForDelete']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor(['delete-broker']);
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('deleteUser');
    });

    it('action column contains view link in dropdown', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'BrokerForView']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->toContain('users/' . $target->id . '/show')
            ->and($actions)->toContain('View');
    });

    it('action column omits edit and delete buttons without permissions', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'BrokerNoPerms']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor(); // no permissions
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $actions = collect($json['data'])->pluck('action')->implode(' ');
        expect($actions)->not->toContain('edit-btn');
        expect($actions)->not->toContain('deleteUser');
    });

    it('status column contains HTML badge markup', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['status' => 1]);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $statuses = collect($json['data'])->pluck('status')->implode(' ');
        expect($statuses)->toContain('badge');
    });

    it('role column excludes the sales role', function () {
        $brokerRole = userCtrlRole('broker');
        $salesRole  = userCtrlRole('sales');

        $target = userCtrlTarget(['name' => 'BrokerWithSales']);
        $target->assignRole($brokerRole);
        $target->assignRole($salesRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(route('users.index', 'broker'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json();

        $roles = collect($json['data'])->where('name', fn($html) => str_contains($html, 'BrokerWithSales'))
            ->pluck('role')->implode(' ');
        expect($roles)->not->toContain('sales');
    });

    it('role filterColumn searches by role name', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'SearchableByRole']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor();
        $json  = $this->actingAs($actor)
            ->get(
                route('users.index', 'broker') . '?columns[0][data]=role&columns[0][search][value]=broker&search[value]=',
                ['X-Requested-With' => 'XMLHttpRequest']
            )
            ->json();

        expect($json['recordsFiltered'])->toBeGreaterThanOrEqual(1);
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  CREATE                                                    */
/* ═══════════════════════════════════════════════════════════ */
describe('create', function () {
    it('returns create view for user type with admin and staff roles', function () {
        userCtrlRole('admin');
        userCtrlRole('staff');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.create', 'user'))
            ->assertOk()
            ->assertViewIs('users.create')
            ->assertViewHas('roles');
    });

    it('returns create view for broker type', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.create', 'broker'))
            ->assertOk()
            ->assertViewIs('users.create')
            ->assertViewHas('type', 'broker');
    });

    it('returns create view for transporter type', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.create', 'transporter'))
            ->assertOk()
            ->assertViewIs('users.create')
            ->assertViewHas('type', 'transporter');
    });

    it('page title differs by type', function () {
        $actor = userCtrlActor();

        $r1 = $this->actingAs($actor)->get(route('users.create', 'broker'));
        $r2 = $this->actingAs($actor)->get(route('users.create', 'transporter'));
        $r3 = $this->actingAs($actor)->get(route('users.create', 'user'));

        $r1->assertViewHas('page_title', 'Add Broker');
        $r2->assertViewHas('page_title', 'Add Transporter');
        $r3->assertViewHas('page_title', 'Add User');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  STORE — VALIDATION                                        */
/* ═══════════════════════════════════════════════════════════ */
describe('store-validation', function () {
    it('requires name', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'email'                 => 'new@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects duplicate name from active user', function () {
        userCtrlTarget(['name' => 'DupeName']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'DupeName',
                'email'                 => 'unique@example.com',
                'phone_no'              => '9876543211',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('name');
    });

    it('allows name of a soft-deleted user', function () {
        $brokerRole = userCtrlRole('broker');
        $existing   = userCtrlTarget(['name' => 'SoftDelName']);
        $existing->delete(); // soft-delete

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'SoftDelName',
                'email'                 => 'brandnew@example.com',
                'phone_no'              => '9876543222',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['name' => 'SoftDelName', 'deleted_at' => null]);
    });

    it('requires email', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'NoEmailUser',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('email');
    });

    it('rejects invalid email format', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'BadEmailUser',
                'email'                 => 'not-an-email',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('email');
    });

    it('rejects duplicate email from active user', function () {
        userCtrlTarget(['email' => 'dup@example.com']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'AnotherUser',
                'email'                 => 'dup@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('email');
    });

    it('requires phone_no', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'NoPhoneUser',
                'email'                 => 'nophone@example.com',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('rejects phone_no shorter than 10 digits', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'ShortPhoneUser',
                'email'                 => 'shortphone@example.com',
                'phone_no'              => '123456789', // 9 digits
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('rejects phone_no longer than 11 digits', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'LongPhoneUser',
                'email'                 => 'longphone@example.com',
                'phone_no'              => '123456789012', // 12 digits
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('rejects duplicate phone_no from active user', function () {
        userCtrlTarget(['phone_no' => '9876543210']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'DupePhoneUser',
                'email'                 => 'dupephone@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('allows phone_no of a soft-deleted user', function () {
        userCtrlRole('broker');
        $existing = userCtrlTarget(['phone_no' => '9111111111']);
        $existing->delete();

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'ReusedPhoneUser',
                'email'                 => 'reusedphone@example.com',
                'phone_no'              => '9111111111',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasNoErrors();
    });

    it('requires password', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'     => 'NoPassUser',
                'email'    => 'nopass@example.com',
                'phone_no' => '9876543210',
            ])
            ->assertSessionHasErrors('password');
    });

    it('rejects password shorter than 6 characters', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'ShortPassUser',
                'email'                 => 'shortpass@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'abc',
                'password_confirmation' => 'abc',
            ])
            ->assertSessionHasErrors('password');
    });

    it('rejects mismatched password confirmation', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'MismatchUser',
                'email'                 => 'mismatch@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'different',
            ])
            ->assertSessionHasErrors('password');
    });

    it('rejects super admin role selection', function () {
        $superAdminRole = userCtrlRole('super admin');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'user'), [
                'name'                  => 'NoSuperAdminUser',
                'email'                 => 'nosuperadmin@example.com',
                'phone_no'              => '9876543210',
                'role'                  => $superAdminRole->id,
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasErrors('role');
    });

    it('accepts a valid role id', function () {
        $adminRole = userCtrlRole('admin');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'user'), [
                'name'                  => 'ValidRoleUser',
                'email'                 => 'validrole@example.com',
                'phone_no'              => '9876543210',
                'role'                  => $adminRole->id,
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertSessionHasNoErrors();
    });

    it('role field is optional for broker type', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'NoRoleBroker',
                'email'                 => 'norolebroke@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
                // no role field
            ])
            ->assertSessionHasNoErrors();
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  STORE — PERSISTENCE                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('store-persistence', function () {
    it('creates a broker user in the database and assigns broker role', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'New Broker',
                'email'                 => 'newbroker@example.com',
                'phone_no'              => '9876543210',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ]);

        $user = User::where('email', 'newbroker@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->hasRole('broker'))->toBeTrue();
    });

    it('creates a transporter user and assigns transporter role', function () {
        userCtrlRole('transporter');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'transporter'), [
                'name'                  => 'New Transporter',
                'email'                 => 'newtransporter@example.com',
                'phone_no'              => '9876543211',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ]);

        $user = User::where('email', 'newtransporter@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->hasRole('transporter'))->toBeTrue();
    });

    it('creates a user-type user and assigns the requested role', function () {
        $adminRole = userCtrlRole('admin');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'user'), [
                'name'                  => 'New Admin',
                'email'                 => 'newadmin@example.com',
                'phone_no'              => '9876543212',
                'role'                  => $adminRole->id,
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ]);

        $user = User::where('email', 'newadmin@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('status defaults to 1 when not provided', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'Default Status User',
                'email'                 => 'defaultstatus@example.com',
                'phone_no'              => '9876543213',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
                // no status
            ]);

        $this->assertDatabaseHas('users', [
            'email'  => 'defaultstatus@example.com',
            'status' => 1,
        ]);
    });

    it('stores profile picture to public disk', function () {
        Storage::fake('public');
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'Pic Upload User',
                'email'                 => 'picupload@example.com',
                'phone_no'              => '9876543214',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
                'profile_picture'       => UploadedFile::fake()->image('avatar.jpg'),
            ]);

        $user = User::where('email', 'picupload@example.com')->first();
        expect($user->profile_picture)->not->toBeNull();
        Storage::disk('public')->assertExists('profile_pictures/' . $user->profile_picture);
    });

    it('returns JSON success when request expects JSON', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('users.store', 'broker'), [
                'name'                  => 'JSON Broker',
                'email'                 => 'jsonbroker@example.com',
                'phone_no'              => '9876543215',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);
    });

    it('JSON response contains broker id and name', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $response = $this->actingAs($actor)
            ->postJson(route('users.store', 'broker'), [
                'name'                  => 'JSON Broker Named',
                'email'                 => 'jsonbrokername@example.com',
                'phone_no'              => '9876543216',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ]);

        $response->assertJsonStructure(['success', 'message', 'broker' => ['id', 'name']]);
        $response->assertJsonPath('broker.name', 'JSON Broker Named');
    });

    it('redirects to users.index with type on normal store', function () {
        userCtrlRole('broker');

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->post(route('users.store', 'broker'), [
                'name'                  => 'Redirect Broker',
                'email'                 => 'redirectbroker@example.com',
                'phone_no'              => '9876543217',
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('users.index', 'broker'));
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  SHOW                                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('show', function () {
    it('returns show view for an existing user', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'ShowTarget Broker']);
        $target->assignRole($brokerRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.show', ['id' => $target->id, 'type' => 'broker']))
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertViewHas('user', fn ($u) => $u->id === $target->id)
            ->assertViewHas('type', 'broker');
    });

    it('returns 404 for non-existent user on show', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.show', ['id' => 99999, 'type' => 'broker']))
            ->assertNotFound();
    });

    it('show view displays user role and status', function () {
        $brokerRole = userCtrlRole('broker');
        $target     = userCtrlTarget(['name' => 'ShowDetails Broker', 'status' => 1]);
        $target->assignRole($brokerRole);

        $response = $this->actingAs(userCtrlActor())
            ->get(route('users.show', ['id' => $target->id, 'type' => 'broker']));

        $response->assertSee('ShowDetails Broker')
            ->assertSee('broker');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  EDIT                                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('edit', function () {
    it('returns edit view for an existing user', function () {
        $target = userCtrlTarget(['name' => 'EditTarget']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->get(route('users.edit', ['type' => 'user', 'id' => $target->id]))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('user', fn($u) => $u->id === $target->id);
    });

    it('returns admin and staff roles in view data', function () {
        userCtrlRole('admin');
        userCtrlRole('staff');
        $target = userCtrlTarget();

        $actor = userCtrlActor();
        $view  = $this->actingAs($actor)
            ->get(route('users.edit', ['type' => 'user', 'id' => $target->id]))
            ->assertViewHas('roles');

        $roles = $view->viewData('roles');
        expect($roles->values()->toArray())->toContain('admin');
        expect($roles->values()->toArray())->toContain('staff');
    });

    it('page title differs by type on edit', function () {
        $target = userCtrlTarget();

        $actor = userCtrlActor();
        $r1    = $this->actingAs($actor)->get(route('users.edit', ['type' => 'broker', 'id' => $target->id]));
        $r2    = $this->actingAs($actor)->get(route('users.edit', ['type' => 'transporter', 'id' => $target->id]));
        $r3    = $this->actingAs($actor)->get(route('users.edit', ['type' => 'user', 'id' => $target->id]));

        $r1->assertViewHas('page_title', 'Edit Broker');
        $r2->assertViewHas('page_title', 'Edit Transporter');
        $r3->assertViewHas('page_title', 'Edit User');
    });

    it('does not throw 404 for non-existent user id (uses find not findOrFail)', function () {
        // Controller uses User::find() — returns null user in view data.
        // The view will cause an error when rendering null $user, so we can't assert OK.
        // This test documents that the controller itself does NOT call abort(404).
        $actor = userCtrlActor();
        // The response will be an error (500) — not a 404
        $response = $this->actingAs($actor)
            ->get(route('users.edit', ['type' => 'user', 'id' => 999999]));
        $response->assertStatus(500);
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  UPDATE — VALIDATION                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('update-validation', function () {
    it('requires name', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'email'    => 'updated@example.com',
                'phone_no' => '9876543210',
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasErrors('name');
    });

    it('rejects duplicate name belonging to another user', function () {
        $adminRole = userCtrlRole('admin');
        userCtrlTarget(['name' => 'TakenName']);
        $target = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => 'TakenName',
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasErrors('name');
    });

    it('allows same name on update (excludes self from unique check)', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget(['name' => 'SameName']);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => 'SameName',
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasNoErrors();
    });

    it('requires email', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasErrors('email');
    });

    it('rejects duplicate email belonging to another user', function () {
        userCtrlTarget(['email' => 'taken@example.com']);
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => 'taken@example.com',
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasErrors('email');
    });

    it('allows same email on update (excludes self from unique check)', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget(['email' => 'same@example.com']);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => 'same@example.com',
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasNoErrors();
    });

    it('requires phone_no', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'   => $target->name,
                'email'  => $target->email,
                'role'   => 'admin',
                'status' => '1',
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('allows same phone_no on update (excludes self)', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget(['phone_no' => '9988776655']);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => '9988776655',
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertSessionHasNoErrors();
    });

    it('requires status', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
            ])
            ->assertSessionHasErrors('status');
    });

    it('rejects invalid status value', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '5', // invalid
            ])
            ->assertSessionHasErrors('status');
    });

    it('password is optional on update', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
                // no password
            ])
            ->assertSessionHasNoErrors();
    });

    it('rejects mismatched password confirmation on update', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'                  => $target->name,
                'email'                 => $target->email,
                'phone_no'              => $target->phone_no,
                'role'                  => 'admin',
                'status'                => '1',
                'password'              => 'newpass1',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  UPDATE — PERSISTENCE                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('update-persistence', function () {
    it('updates user name, email, phone_no, and status', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => 'Updated Name',
                'email'    => 'updated@example.com',
                'phone_no' => '9999988888',
                'role'     => 'admin',
                'status'   => '0',
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $target->id,
            'name'     => 'Updated Name',
            'email'    => 'updated@example.com',
            'phone_no' => '9999988888',
            'status'   => 0,
        ]);
    });

    it('updates password when provided', function () {
        $adminRole   = userCtrlRole('admin');
        $target      = userCtrlTarget(['password' => Hash::make('oldpassword')]);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'                  => $target->name,
                'email'                 => $target->email,
                'phone_no'              => $target->phone_no,
                'role'                  => 'admin',
                'status'                => '1',
                'password'              => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

        $target->refresh();
        expect(Hash::check('newpassword', $target->password))->toBeTrue();
    });

    it('preserves password when not provided on update', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget(['password' => Hash::make('keepthis')]);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ]);

        $target->refresh();
        expect(Hash::check('keepthis', $target->password))->toBeTrue();
    });

    it('replaces profile picture on upload', function () {
        Storage::fake('public');
        $adminRole = userCtrlRole('admin');

        Storage::disk('public')->put('profile_pictures/old.jpg', 'old content');
        $target = userCtrlTarget(['profile_picture' => 'old.jpg']);
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'            => $target->name,
                'email'           => $target->email,
                'phone_no'        => $target->phone_no,
                'role'            => 'admin',
                'status'          => '1',
                'profile_picture' => UploadedFile::fake()->image('new.jpg'),
            ]);

        Storage::disk('public')->assertMissing('profile_pictures/old.jpg');
        $target->refresh();
        Storage::disk('public')->assertExists('profile_pictures/' . $target->profile_picture);
    });

    it('user type syncs the requested role', function () {
        $adminRole = userCtrlRole('admin');
        $staffRole = userCtrlRole('staff');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'staff',
                'status'   => '1',
            ]);

        $target->refresh();
        expect($target->hasRole('staff'))->toBeTrue();
        expect($target->hasRole('admin'))->toBeFalse();
    });

    it('user type does not change the role of a super admin target', function () {
        $superAdminRole = userCtrlRole('super admin');
        $adminRole      = userCtrlRole('admin');
        $target         = userCtrlTarget();
        $target->assignRole($superAdminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ]);

        $target->refresh();
        expect($target->hasRole('super admin'))->toBeTrue();
        expect($target->hasRole('admin'))->toBeFalse();
    });

    it('broker type always syncs to broker role regardless of request', function () {
        $brokerRole = userCtrlRole('broker');
        $staffRole  = userCtrlRole('staff');
        $target     = userCtrlTarget();
        $target->assignRole($staffRole); // starts with non-broker role

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'broker', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'staff', // request says staff, but type=broker overrides
                'status'   => '1',
            ]);

        $target->refresh();
        expect($target->hasRole('broker'))->toBeTrue();
        expect($target->hasRole('staff'))->toBeFalse();
    });

    it('transporter type always syncs to transporter role regardless of request', function () {
        $transporterRole = userCtrlRole('transporter');
        $adminRole       = userCtrlRole('admin');
        $target          = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'transporter', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ]);

        $target->refresh();
        expect($target->hasRole('transporter'))->toBeTrue();
        expect($target->hasRole('admin'))->toBeFalse();
    });

    it('redirects to users.index with the correct type on update', function () {
        $adminRole = userCtrlRole('admin');
        $target    = userCtrlTarget();
        $target->assignRole($adminRole);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->put(route('users.update', ['type' => 'user', 'id' => $target->id]), [
                'name'     => $target->name,
                'email'    => $target->email,
                'phone_no' => $target->phone_no,
                'role'     => 'admin',
                'status'   => '1',
            ])
            ->assertRedirect(route('users.index', 'user'));
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  DESTROY                                                   */
/* ═══════════════════════════════════════════════════════════ */
describe('destroy', function () {
    it('returns 404 for a non-existent user id', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => 999999]))
            ->assertNotFound();
    });

    it('hard-deletes the user (removed from database)', function () {
        // User model does NOT use SoftDeletes — destroy() is a permanent hard delete.
        $target = userCtrlTarget(['name' => 'ToHardDelete']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => $target->id]));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    });

    it('hard-deleted user is no longer findable', function () {
        $target = userCtrlTarget(['name' => 'GoneFromDB']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => $target->id]));

        expect(User::find($target->id))->toBeNull();
    });

    it('deletes profile picture from storage on destroy', function () {
        Storage::fake('public');
        Storage::disk('public')->put('profile_pictures/todelete.jpg', 'data');

        $target = userCtrlTarget(['profile_picture' => 'todelete.jpg']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => $target->id]));

        Storage::disk('public')->assertMissing('profile_pictures/todelete.jpg');
    });

    it('does not error when user has no profile picture on destroy', function () {
        $target = userCtrlTarget(['profile_picture' => null]);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => $target->id]))
            ->assertRedirect();
    });

    it('redirects to users.index with type after destroy', function () {
        $target = userCtrlTarget();

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->delete(route('users.destroy', ['type' => 'broker', 'id' => $target->id]))
            ->assertRedirect(route('users.index', 'broker'));
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  BULK DELETE                                               */
/* ═══════════════════════════════════════════════════════════ */
describe('bulkDelete', function () {
    it('returns 400 JSON when ids array is empty', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected!']);
    });

    it('returns 400 JSON when ids is not provided', function () {
        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), [])
            ->assertStatus(400);
    });

    it('hard-deletes all specified users (User model has no SoftDeletes)', function () {
        $t1 = userCtrlTarget(['name' => 'BulkUser1']);
        $t2 = userCtrlTarget(['name' => 'BulkUser2']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), ['ids' => [$t1->id, $t2->id]]);

        $this->assertDatabaseMissing('users', ['id' => $t1->id]);
        $this->assertDatabaseMissing('users', ['id' => $t2->id]);
    });

    it('returns JSON success message on bulk delete', function () {
        $target = userCtrlTarget();

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), ['ids' => [$target->id]])
            ->assertOk()
            ->assertJsonStructure(['message']);
    });

    it('success message includes capitalized type name', function () {
        $target = userCtrlTarget();

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), ['ids' => [$target->id]])
            ->assertJsonFragment(['message' => 'Selected Brokers deleted successfully!']);
    });

    it('deletes profile pictures for all bulk-deleted users', function () {
        Storage::fake('public');
        Storage::disk('public')->put('profile_pictures/bulk1.jpg', 'data');
        Storage::disk('public')->put('profile_pictures/bulk2.jpg', 'data');

        $t1 = userCtrlTarget(['profile_picture' => 'bulk1.jpg']);
        $t2 = userCtrlTarget(['profile_picture' => 'bulk2.jpg']);

        $actor = userCtrlActor();
        $this->actingAs($actor)
            ->postJson(route('user.bulkDelete', 'broker'), ['ids' => [$t1->id, $t2->id]]);

        Storage::disk('public')->assertMissing('profile_pictures/bulk1.jpg');
        Storage::disk('public')->assertMissing('profile_pictures/bulk2.jpg');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MY PROFILE                                                */
/* ═══════════════════════════════════════════════════════════ */
describe('my-profile', function () {
    it('returns the edit view with user and my_profile data', function () {
        $user = userCtrlActor();
        $this->actingAs($user)
            ->get(route('my_profile', $user->id))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('my_profile', 'my_profile')
            ->assertViewHas('user', fn($u) => $u->id === $user->id);
    });

    it('includes admin and staff roles in view data', function () {
        userCtrlRole('admin');
        userCtrlRole('staff');

        $user = userCtrlActor();
        $view = $this->actingAs($user)
            ->get(route('my_profile', $user->id));

        $view->assertViewHas('roles');
        $roles = $view->viewData('roles');
        expect($roles->values()->toArray())->toContain('admin');
        expect($roles->values()->toArray())->toContain('staff');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MY PROFILE UPDATE                                         */
/* ═══════════════════════════════════════════════════════════ */
describe('my-profile-update', function () {
    it('updates profile name, email, and phone_no', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000001']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => 'Updated Profile Name',
                'email'    => 'profile_updated@example.com',
                'phone_no' => '9000000002',
                'status'   => '1',
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'name'     => 'Updated Profile Name',
            'email'    => 'profile_updated@example.com',
            'phone_no' => '9000000002',
        ]);
    });

    it('updates password when provided', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000003', 'password' => Hash::make('oldpass')]);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'phone_no'              => '9000000003',
                'status'                => '1',
                'password'              => 'newpass123',
                'password_confirmation' => 'newpass123',
            ]);

        $user->refresh();
        expect(Hash::check('newpass123', $user->password))->toBeTrue();
    });

    it('preserves old password when password is not provided', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000004', 'password' => Hash::make('keepthispass')]);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'email'    => $user->email,
                'phone_no' => '9000000004',
            ]);

        $user->refresh();
        expect(Hash::check('keepthispass', $user->password))->toBeTrue();
    });

    it('requires name on profile update', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000005']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'email'    => $user->email,
                'phone_no' => '9000000005',
            ])
            ->assertSessionHasErrors('name');
    });

    it('requires email on profile update', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000006']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'phone_no' => '9000000006',
            ])
            ->assertSessionHasErrors('email');
    });

    it('rejects duplicate name belonging to another user on profile update', function () {
        userCtrlTarget(['name' => 'OtherProfileName']);
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000007']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => 'OtherProfileName',
                'email'    => $user->email,
                'phone_no' => '9000000007',
            ])
            ->assertSessionHasErrors('name');
    });

    it('replaces profile picture on upload via my_profile_update', function () {
        Storage::fake('public');
        Storage::disk('public')->put('profile_pictures/myold.jpg', 'old');

        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000008', 'profile_picture' => 'myold.jpg']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'            => $user->name,
                'email'           => $user->email,
                'phone_no'        => '9000000008',
                'status'          => '1',
                'profile_picture' => UploadedFile::fake()->image('mynew.jpg'),
            ]);

        Storage::disk('public')->assertMissing('profile_pictures/myold.jpg');
        $user->refresh();
        Storage::disk('public')->assertExists('profile_pictures/' . $user->profile_picture);
    });

    it('rejects mismatched password confirmation on profile update', function () {
        $user = userCtrlActor();
        $user->update(['phone_no' => '9000000009']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'phone_no'              => '9000000009',
                'password'              => 'newpass1',
                'password_confirmation' => 'mismatch',
            ])
            ->assertSessionHasErrors('password');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MODEL — statusBadge                                       */
/* ═══════════════════════════════════════════════════════════ */
describe('model-statusBadge', function () {
    it('returns a green active badge for status 1', function () {
        $user = userCtrlTarget(['status' => 1]);
        expect($user->statusBadge())->toContain('bg-success');
        expect($user->statusBadge())->toContain('Active');
    });

    it('returns a red inactive badge for status 0', function () {
        $user = userCtrlTarget(['status' => 0]);
        expect($user->statusBadge())->toContain('bg-danger');
        expect($user->statusBadge())->toContain('Inactive');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MODEL — scopeBrokers                                      */
/* ═══════════════════════════════════════════════════════════ */
describe('model-scopeBrokers', function () {
    it('scopeBrokers returns only users assigned the broker role', function () {
        $brokerRole = userCtrlRole('broker');
        $broker     = userCtrlTarget(['name' => 'OnlyBroker']);
        $broker->assignRole($brokerRole);

        $results = User::brokers()->pluck('name');
        expect($results->toArray())->toContain('OnlyBroker');
    });

    it('scopeBrokers excludes users without the broker role', function () {
        $adminRole = userCtrlRole('admin');
        $admin     = userCtrlTarget(['name' => 'NonBrokerAdmin']);
        $admin->assignRole($adminRole);

        $brokers = User::brokers()->pluck('name');
        expect($brokers->toArray())->not->toContain('NonBrokerAdmin');
    });
});

/* ═══════════════════════════════════════════════════════════ */
/*  MODEL — activeBrokersForDropdown                          */
/* ═══════════════════════════════════════════════════════════ */
describe('model-activeBrokersForDropdown', function () {
    it('returns active broker users only', function () {
        $brokerRole    = userCtrlRole('broker');
        $activeBroker  = userCtrlTarget(['name' => 'ActiveDropdownBroker', 'status' => 1]);
        $inactiveBroker = userCtrlTarget(['name' => 'InactiveDropdownBroker', 'status' => 0]);
        $activeBroker->assignRole($brokerRole);
        $inactiveBroker->assignRole($brokerRole);

        $results = User::activeBrokersForDropdown()->pluck('name');
        expect($results->toArray())->toContain('ActiveDropdownBroker');
        expect($results->toArray())->not->toContain('InactiveDropdownBroker');
    });
});
