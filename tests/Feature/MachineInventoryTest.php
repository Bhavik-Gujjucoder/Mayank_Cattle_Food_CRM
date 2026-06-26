<?php

use App\Models\MachineInventory;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function machineActor(): User
{
    $user = User::factory()->create(['status' => 1, 'email_verified_at' => now()]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    return $user;
}

describe('access-control', function () {
    it('redirects guest from machine index', function () {
        get(route('machine.index'))->assertRedirect(route('login'));
    });

    it('redirects guest from machine create', function () {
        get(route('machine.create'))->assertRedirect(route('login'));
    });

    it('allows authenticated user to view machine index', function () {
        actingAs(machineActor())->get(route('machine.index'))->assertOk()->assertViewIs('machine_inventory.index');
    });
});

describe('resource-stubs', function () {
    it('returns empty response for unimplemented create route', function () {
        actingAs(machineActor())->get(route('machine.create'))->assertOk();
    });

    it('returns empty response for unimplemented show route', function () {
        $machine = MachineInventory::create();
        actingAs(machineActor())->get(route('machine.show', $machine))->assertOk();
    });
});
