<?php

use App\Models\OilManagement;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function oilActor(): User
{
    $user = User::factory()->create(['status' => 1, 'email_verified_at' => now()]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    return $user;
}

describe('access-control', function () {
    it('redirects guest from oil index', function () {
        get(route('oil.index'))->assertRedirect(route('login'));
    });

    it('redirects guest from oil create', function () {
        get(route('oil.create'))->assertRedirect(route('login'));
    });

    it('allows authenticated user to view oil index', function () {
        actingAs(oilActor())->get(route('oil.index'))->assertOk()->assertViewIs('oil_management.index');
    });
});

describe('resource-stubs', function () {
    it('returns empty response for unimplemented create route', function () {
        actingAs(oilActor())->get(route('oil.create'))->assertOk();
    });

    it('returns empty response for unimplemented show route', function () {
        $oil = OilManagement::create();
        actingAs(oilActor())->get(route('oil.show', $oil))->assertOk();
    });
});
