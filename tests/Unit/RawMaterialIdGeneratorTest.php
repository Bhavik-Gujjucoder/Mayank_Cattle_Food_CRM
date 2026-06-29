<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Services\RawMaterialIdGenerator;
use Carbon\Carbon;

// ─────────────────────────────────────────────

describe('nextMaterialId', function () {
    it('returns Raw-0001 when no materials exist', function () {
        expect(RawMaterialIdGenerator::nextMaterialId())->toBe('Raw-0001');
    });

    it('increments based on total count including soft-deleted', function () {
        $category = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-IDG-' . uniqid(),
            'name'               => 'IdGenCat-' . uniqid(),
            'status'             => 1,
        ]);
        RawMaterial::create([
            'raw_material_unique_id'   => 'RM-IDG-' . uniqid(),
            'raw_material_category_id' => $category->id,
            'name'                     => 'IdGenMat-' . uniqid(),
            'unit'                     => 'Ton',
            'status'                   => 1,
        ]);
        expect(RawMaterialIdGenerator::nextMaterialId())->toBe('Raw-0002');
    });

    it('counts soft-deleted materials when generating next id', function () {
        $category = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-IDG2-' . uniqid(),
            'name'               => 'IdGenCat2-' . uniqid(),
            'status'             => 1,
        ]);
        $mat = RawMaterial::create([
            'raw_material_unique_id'   => 'RM-IDG2-' . uniqid(),
            'raw_material_category_id' => $category->id,
            'name'                     => 'IdGenMat2-' . uniqid(),
            'unit'                     => 'Ton',
            'status'                   => 1,
        ]);
        $mat->delete(); // soft-delete

        // withTrashed() count = 1, next = Raw-0002
        expect(RawMaterialIdGenerator::nextMaterialId())->toBe('Raw-0002');
    });

    it('pads id to 4 digits', function () {
        expect(RawMaterialIdGenerator::nextMaterialId())->toMatch('/^Raw-\d{4}$/');
    });
});

// ─────────────────────────────────────────────

describe('nextCategoryId', function () {
    it('returns RMC-0001 when no categories exist', function () {
        expect(RawMaterialIdGenerator::nextCategoryId())->toBe('RMC-0001');
    });

    it('increments based on total count including soft-deleted', function () {
        RawMaterialCategory::create([
            'category_unique_id' => 'CAT-IDG3-' . uniqid(),
            'name'               => 'IdGenCat3-' . uniqid(),
            'status'             => 1,
        ]);
        expect(RawMaterialIdGenerator::nextCategoryId())->toBe('RMC-0002');
    });

    it('counts soft-deleted categories when generating next id', function () {
        $cat = RawMaterialCategory::create([
            'category_unique_id' => 'CAT-IDG4-' . uniqid(),
            'name'               => 'IdGenCat4-' . uniqid(),
            'status'             => 1,
        ]);
        $cat->delete();

        expect(RawMaterialIdGenerator::nextCategoryId())->toBe('RMC-0002');
    });

    it('pads id to 4 digits', function () {
        expect(RawMaterialIdGenerator::nextCategoryId())->toMatch('/^RMC-\d{4}$/');
    });
});

// ─────────────────────────────────────────────

describe('financialYear', function () {
    it('returns correct FY for April (start of year)', function () {
        expect(RawMaterialIdGenerator::financialYear(Carbon::parse('2026-04-01')))->toBe('2026-27');
    });

    it('returns correct FY for March (end of year)', function () {
        expect(RawMaterialIdGenerator::financialYear(Carbon::parse('2026-03-31')))->toBe('2025-26');
    });

    it('returns correct FY for December', function () {
        expect(RawMaterialIdGenerator::financialYear(Carbon::parse('2025-12-15')))->toBe('2025-26');
    });

    it('returns correct FY for January', function () {
        expect(RawMaterialIdGenerator::financialYear(Carbon::parse('2026-01-15')))->toBe('2025-26');
    });

    it('uses current date when no date is provided', function () {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));
        expect(RawMaterialIdGenerator::financialYear())->toBe('2026-27');
        Carbon::setTestNow();
    });

    it('formats end year as 2-digit suffix', function () {
        expect(RawMaterialIdGenerator::financialYear(Carbon::parse('2099-04-01')))->toBe('2099-00');
    });
});

// ─────────────────────────────────────────────

describe('nextOrderId', function () {
    it('returns first order id when no orders exist for that FY', function () {
        Carbon::setTestNow(Carbon::parse('2026-04-01'));
        expect(RawMaterialIdGenerator::nextOrderId())->toBe('RMO/2026-27/0001');
        Carbon::setTestNow();
    });

    it('uses provided order date for financial year', function () {
        $date = Carbon::parse('2025-12-15');
        expect(RawMaterialIdGenerator::nextOrderId($date))->toBe('RMO/2025-26/0001');
    });

    it('increments counter for existing orders in the same FY', function () {
        $broker   = SupplierBroker::create(['name' => 'SB-IDG-' . uniqid(), 'status' => 1]);
        $supplier = Supplier::create([
            'supplier_broker_id' => $broker->id,
            'name'               => 'Sup-IDG-' . uniqid(),
            'email'              => uniqid() . '@idg.test',
            'status'             => 1,
        ]);
        RawMaterialOrder::create([
            'order_unique_id' => 'RMO/2025-26/0001',
            'supplier_id'     => $supplier->id,
            'order_date'      => '2025-11-01',
        ]);

        $date = Carbon::parse('2026-01-01');
        expect(RawMaterialIdGenerator::nextOrderId($date))->toBe('RMO/2025-26/0002');
    });

    it('resets counter independently for different financial years', function () {
        $broker   = SupplierBroker::create(['name' => 'SB-IDG2-' . uniqid(), 'status' => 1]);
        $supplier = Supplier::create([
            'supplier_broker_id' => $broker->id,
            'name'               => 'Sup-IDG2-' . uniqid(),
            'email'              => uniqid() . '@idg2.test',
            'status'             => 1,
        ]);
        RawMaterialOrder::create([
            'order_unique_id' => 'RMO/2024-25/0001',
            'supplier_id'     => $supplier->id,
            'order_date'      => '2024-11-01',
        ]);

        // 2025-26 FY should start at 0001 (different year)
        $date = Carbon::parse('2026-01-01');
        expect(RawMaterialIdGenerator::nextOrderId($date))->toBe('RMO/2025-26/0001');
    });

    it('pads order number to 4 digits', function () {
        Carbon::setTestNow(Carbon::parse('2026-04-01'));
        expect(RawMaterialIdGenerator::nextOrderId())->toMatch('/^RMO\/\d{4}-\d{2}\/\d{4}$/');
        Carbon::setTestNow();
    });
});
