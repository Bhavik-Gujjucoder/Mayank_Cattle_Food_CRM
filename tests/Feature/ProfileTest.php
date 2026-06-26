<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    foreach (['super admin', 'admin'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
});

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function profActor(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1, 'phone_no' => '9000000099'], $attrs));
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    return $user;
}

// ─────────────────────────────────────────────

describe('my-profile (GET)', function () {
    it('renders the profile edit view', function () {
        $user = profActor();

        $this->actingAs($user)
            ->get(route('my_profile', $user->id))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('my_profile', 'my_profile')
            ->assertViewHas('user', fn ($u) => $u->id === $user->id);
    });

    it('redirects unauthenticated user to login', function () {
        $user = User::factory()->create(['status' => 1]);

        $this->get(route('my_profile', $user->id))
            ->assertRedirect(route('login'));
    });
});

// ─────────────────────────────────────────────

describe('my-profile-update (PUT)', function () {
    it('updates name, email, and phone_no successfully', function () {
        $user = profActor(['phone_no' => '9000000001']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => 'Updated Name',
                'email'    => 'updated@example.com',
                'phone_no' => '9000000002',
                'status'   => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'name'     => 'Updated Name',
            'email'    => 'updated@example.com',
            'phone_no' => '9000000002',
        ]);
    });

    it('updates password when provided', function () {
        $user = profActor(['phone_no' => '9000000003', 'password' => Hash::make('oldpassword')]);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'                  => $user->name,
                'email'                 => $user->email,
                'phone_no'              => '9000000003',
                'status'                => 1,
                'password'              => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

        $user->refresh();
        expect(Hash::check('newpassword', $user->password))->toBeTrue();
    });

    it('preserves password when not provided', function () {
        $user = profActor(['phone_no' => '9000000004', 'password' => Hash::make('keepthis')]);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'email'    => $user->email,
                'phone_no' => '9000000004',
                'status'   => 1,
            ]);

        $user->refresh();
        expect(Hash::check('keepthis', $user->password))->toBeTrue();
    });

    it('requires name', function () {
        $user = profActor(['phone_no' => '9000000005']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'email'    => $user->email,
                'phone_no' => '9000000005',
                'status'   => 1,
            ])
            ->assertSessionHasErrors('name');
    });

    it('requires email', function () {
        $user = profActor(['phone_no' => '9000000006']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'phone_no' => '9000000006',
                'status'   => 1,
            ])
            ->assertSessionHasErrors('email');
    });

    it('requires phone_no', function () {
        $user = profActor(['phone_no' => '9000000007']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'   => $user->name,
                'email'  => $user->email,
                'status' => 1,
            ])
            ->assertSessionHasErrors('phone_no');
    });

    it('rejects duplicate name from another user', function () {
        $other = profActor(['name' => 'TakenName', 'phone_no' => '9000000008']);
        $user  = profActor(['phone_no' => '9000000009']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => 'TakenName',
                'email'    => $user->email,
                'phone_no' => '9000000009',
                'status'   => 1,
            ])
            ->assertSessionHasErrors('name');
    });

    it('allows keeping the same name on update', function () {
        $user = profActor(['phone_no' => '9000000010']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'email'    => $user->email,
                'phone_no' => '9000000010',
                'status'   => 1,
            ])
            ->assertSessionDoesntHaveErrors('name');
    });

    it('rejects invalid phone_no format', function () {
        $user = profActor(['phone_no' => '9000000011']);

        $this->actingAs($user)
            ->put(route('my_profile.update', $user->id), [
                'name'     => $user->name,
                'email'    => $user->email,
                'phone_no' => 'not-a-number',
                'status'   => 1,
            ])
            ->assertSessionHasErrors('phone_no');
    });
});
