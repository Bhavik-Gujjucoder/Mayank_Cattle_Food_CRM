<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function logoutUser(array $attrs = []): array
{
    Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']);

    $user = User::factory()->create(array_merge([
        'status'   => 1,
        'email'    => 'dealer.logout.' . uniqid() . '@example.com',
        'phone_no' => '9' . rand(100000000, 999999999),
        'password' => Hash::make('password123'),
    ], $attrs));

    $user->assignRole('dealer');

    $token = $user->createToken('test-device')->plainTextToken;

    return [$user, $token];
}

describe('POST /api/v1/auth/logout', function () {

    it('returns 200 and revokes the current token', function () {
        [$user, $token] = logoutUser();

        postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
                'data'    => null,
            ]);

        expect($user->fresh()->tokens()->count())->toBe(0);

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();
    });

    it('revokes only the current token and leaves other device tokens valid', function () {
        [$user, $tokenA] = logoutUser();
        $tokenB = $user->createToken('other-device')->plainTextToken;

        postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $tokenA"])
            ->assertOk();

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $tokenA"])
            ->assertUnauthorized();

        getJson('/api/v1/auth/me', ['Authorization' => "Bearer $tokenB"])
            ->assertOk();
    });

    it('returns 401 when no Authorization header is provided', function () {
        postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    });

    it('returns 401 when an invalid Bearer token is provided', function () {
        postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer invalid-token-value'])
            ->assertUnauthorized();
    });

    it('returns 401 when the token has already been revoked', function () {
        [, $token] = logoutUser();

        postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token"])
            ->assertOk();

        postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();
    });
});
