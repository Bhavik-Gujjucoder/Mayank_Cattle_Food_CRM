<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;

// ─── Shared helpers ───────────────────────────────────────────────────────────

/**
 * Create an active user, assign the given Spatie role, and return a
 * Sanctum Bearer token string ready for the Authorization header.
 */
function authCheckUser(string $roleName, array $permissions = [], array $attrs = []): array
{
    $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    if (! empty($permissions)) {
        foreach ($permissions as $perm) {
            $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $role->givePermissionTo($p);
        }
    }

    $user = User::factory()->create(array_merge([
        'status'   => 1,
        'email'    => $roleName . '.check.' . uniqid() . '@example.com',
        'phone_no' => '9' . rand(100000000, 999999999),
        'password' => Hash::make('password123'),
    ], $attrs));

    $user->assignRole($role);

    $token = $user->createToken('test-device')->plainTextToken;

    return [$user, $token];
}

// ─── Success — Dealer ─────────────────────────────────────────────────────────

describe('GET /api/v1/auth/me — dealer access', function () {

    it('returns 200 with role=dealer for a dealer user', function () {
        [, $token] = authCheckUser('dealer');

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Authenticated successfully.',
                'data'    => ['role' => 'dealer', 'token_status' => 'valid'],
            ])
            ->assertJsonStructure([
                'data' => ['user', 'role', 'permissions', 'token_status'],
            ]);
    });

    it('returns the correct user profile fields for a dealer', function () {
        [$user, $token] = authCheckUser('dealer', [], [
            'name'     => 'Test Dealer',
            'email'    => 'dealer.profile@example.com',
            'phone_no' => '9111222333',
        ]);

        $response = getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $data = $response->json('data.user');
        expect($data['id'])->toBe($user->id)
            ->and($data['name'])->toBe('Test Dealer')
            ->and($data['email'])->toBe('dealer.profile@example.com')
            ->and($data['phone_no'])->toBe('9111222333')
            ->and($data['status'])->toBe(1);
    });

    it('returns the permissions assigned to the dealer role', function () {
        [, $token] = authCheckUser('dealer', ['view-order', 'view-dispatch']);

        $response = getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk();

        $permissions = $response->json('data.permissions');
        expect($permissions)->toContain('view-order')
            ->and($permissions)->toContain('view-dispatch');
    });

    it('returns an empty permissions array when dealer has no permissions', function () {
        [, $token] = authCheckUser('dealer');

        $response = getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.permissions'))->toBeArray()->toBeEmpty();
    });
});

// ─── Success — Broker ─────────────────────────────────────────────────────────

describe('GET /api/v1/auth/me — broker access', function () {

    it('returns 200 with role=broker for a broker user', function () {
        [, $token] = authCheckUser('broker');

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data'    => ['role' => 'broker', 'token_status' => 'valid'],
            ]);
    });

    it('returns the permissions assigned to the broker role', function () {
        [, $token] = authCheckUser('broker', ['view-supplier-broker']);

        $response = getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertOk();

        expect($response->json('data.permissions'))->toContain('view-supplier-broker');
    });
});

// ─── Forbidden — non-dealer/broker roles ─────────────────────────────────────

describe('GET /api/v1/auth/me — forbidden for other roles', function () {

    it('returns 403 for a user with the admin role', function () {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        [, $token] = authCheckUser('admin');

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Access denied. This endpoint is restricted to Dealer and Broker accounts.',
            ]);
    });

    it('returns 403 for a user with the super admin role', function () {
        Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        [, $token] = authCheckUser('super admin');

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    });
});

// ─── Unauthorized ─────────────────────────────────────────────────────────────

describe('GET /api/v1/auth/me — unauthorized', function () {

    it('returns 401 when no Authorization header is provided', function () {
        getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });

    it('returns 401 when an invalid Bearer token is provided', function () {
        getJson('/api/v1/auth/me', ['Authorization' => 'Bearer invalid-token-value'])
            ->assertUnauthorized();
    });

    it('returns 401 when the token has been revoked', function () {
        [$user, $token] = authCheckUser('dealer');

        // Revoke all tokens for this user.
        $user->tokens()->delete();

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();
    });
});
