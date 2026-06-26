<?php

use App\Models\BrandManagement;
use App\Models\User;
use App\Support\ActiveDropdownValidation;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']);
});

// ─────────────────────────────────────────────

describe('brokerId', function () {
    it('returns an array of validation rules', function () {
        $rules = ActiveDropdownValidation::brokerId();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('rules include required and integer', function () {
        $rules = ActiveDropdownValidation::brokerId();
        expect($rules)->toContain('required')
            ->and($rules)->toContain('integer');
    });

    it('passes for an active broker user', function () {
        $broker = User::factory()->create(['status' => 1]);
        $broker->assignRole('broker');

        $validator = Validator::make(
            ['broker_id' => $broker->id],
            ['broker_id' => ActiveDropdownValidation::brokerId()]
        );

        expect($validator->fails())->toBeFalse();
    });

    it('fails for a non-existent user id', function () {
        $validator = Validator::make(
            ['broker_id' => 99999],
            ['broker_id' => ActiveDropdownValidation::brokerId()]
        );

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('broker_id'))->toBeTrue();
    });

    it('fails when user exists but does not have broker role', function () {
        $nonBroker = User::factory()->create(['status' => 1]);

        $validator = Validator::make(
            ['broker_id' => $nonBroker->id],
            ['broker_id' => ActiveDropdownValidation::brokerId()]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails when broker_id is missing', function () {
        $validator = Validator::make(
            [],
            ['broker_id' => ActiveDropdownValidation::brokerId()]
        );

        expect($validator->fails())->toBeTrue();
    });
});

// ─────────────────────────────────────────────

describe('brandId', function () {
    it('returns an array of validation rules', function () {
        $rules = ActiveDropdownValidation::brandId();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('rules include required and integer', function () {
        $rules = ActiveDropdownValidation::brandId();
        expect($rules)->toContain('required')
            ->and($rules)->toContain('integer');
    });

    it('passes for an active brand', function () {
        $brand = BrandManagement::create(['name' => 'Active Brand', 'status' => 1]);

        $validator = Validator::make(
            ['brand_id' => $brand->id],
            ['brand_id' => ActiveDropdownValidation::brandId()]
        );

        expect($validator->fails())->toBeFalse();
    });

    it('fails for an inactive brand', function () {
        $brand = BrandManagement::create(['name' => 'Inactive Brand', 'status' => 0]);

        $validator = Validator::make(
            ['brand_id' => $brand->id],
            ['brand_id' => ActiveDropdownValidation::brandId()]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails for a non-existent brand id', function () {
        $validator = Validator::make(
            ['brand_id' => 99999],
            ['brand_id' => ActiveDropdownValidation::brandId()]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails when brand_id is missing', function () {
        $validator = Validator::make(
            [],
            ['brand_id' => ActiveDropdownValidation::brandId()]
        );

        expect($validator->fails())->toBeTrue();
    });
});
