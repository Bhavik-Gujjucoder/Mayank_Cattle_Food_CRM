<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\User;

function seedRawMaterialReceive(array $overrides = []): RawMaterialReceive
{
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'RMC-9001',
        'name' => 'Test Category',
        'status' => 1,
    ]);

    $material = RawMaterial::create([
        'raw_material_unique_id' => 'RM-9001',
        'raw_material_category_id' => $category->id,
        'name' => 'Test Material',
        'unit' => 'kg',
        'status' => 1,
    ]);

    $supplier = Supplier::create([
        'name' => 'Test Supplier',
        'status' => 1,
    ]);

    $order = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-9001',
        'supplier_id' => $supplier->id,
        'supplier_order_id' => 'SO-9001',
        'order_date' => now()->toDateString(),
        'status' => 0,
    ]);

    $orderItem = RawMaterialOrderItem::create([
        'raw_material_id' => $material->id,
        'raw_material_order_id' => $order->id,
        'total_qty' => 100,
        'pending_qty' => 100,
        'status' => 0,
    ]);

    return RawMaterialReceive::create(array_merge([
        'raw_material_id' => $material->id,
        'raw_material_order_id' => $order->id,
        'raw_material_order_item_id' => $orderItem->id,
        'qty' => 10,
        'freight' => 500,
        'received_date' => now()->toDateString(),
        'status' => 0,
    ], $overrides));
}

describe('raw material category exports', function () {
    test('excel export returns spreadsheet when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-category']);

        RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0001',
            'name' => 'Grains',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('raw-material.category.export'));

        $response->assertOk();
        $response->assertDownload('raw-material-categories-' . now()->format('Y-m-d') . '.xlsx');
    });

    test('pdf export returns pdf when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-category']);

        RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0002',
            'name' => 'Minerals',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('raw-material.category.export-list-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('raw-material-categories-' . now()->format('Y-m-d') . '.pdf');
    });

    test('category exports are forbidden without permission', function () {
        $user = User::factory()->create();

        RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0003',
            'name' => 'Vitamins',
            'status' => 1,
        ]);

        $this->actingAs($user)->get(route('raw-material.category.export'))->assertForbidden();
        $this->actingAs($user)->get(route('raw-material.category.export-list-pdf'))->assertForbidden();
    });

    test('category export redirects when no records match filters', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-category']);

        RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0004',
            'name' => 'Active Only',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('raw-material.category.index'))
            ->get(route('raw-material.category.export', ['status' => 0]));

        $response->assertRedirect(route('raw-material.category.index'));
        $response->assertSessionHas('error', 'No records found to export for the current filters.');
    });
});

describe('raw material inventory exports', function () {
    test('excel export returns spreadsheet when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-inventory']);

        $category = RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0100',
            'name' => 'Protein',
            'status' => 1,
        ]);

        RawMaterial::create([
            'raw_material_unique_id' => 'RM-0001',
            'raw_material_category_id' => $category->id,
            'name' => 'Soybean Meal',
            'unit' => 'kg',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('raw-material.export'));

        $response->assertOk();
        $response->assertDownload('raw-materials-' . now()->format('Y-m-d') . '.xlsx');
    });

    test('pdf export returns pdf when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-inventory']);

        $category = RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0101',
            'name' => 'Fiber',
            'status' => 1,
        ]);

        RawMaterial::create([
            'raw_material_unique_id' => 'RM-0002',
            'raw_material_category_id' => $category->id,
            'name' => 'Wheat Bran',
            'unit' => 'kg',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('raw-material.export-list-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('raw-materials-' . now()->format('Y-m-d') . '.pdf');
    });

    test('material exports are forbidden without permission', function () {
        $user = User::factory()->create();

        $category = RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0102',
            'name' => 'Oil',
            'status' => 1,
        ]);

        RawMaterial::create([
            'raw_material_unique_id' => 'RM-0003',
            'raw_material_category_id' => $category->id,
            'name' => 'Rice Bran Oil',
            'unit' => 'kg',
            'status' => 1,
        ]);

        $this->actingAs($user)->get(route('raw-material.export'))->assertForbidden();
        $this->actingAs($user)->get(route('raw-material.export-list-pdf'))->assertForbidden();
    });

    test('material export redirects when no records match filters', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-inventory']);

        $category = RawMaterialCategory::create([
            'category_unique_id' => 'RMC-0103',
            'name' => 'Additives',
            'status' => 1,
        ]);

        RawMaterial::create([
            'raw_material_unique_id' => 'RM-0004',
            'raw_material_category_id' => $category->id,
            'name' => 'Salt',
            'unit' => 'kg',
            'status' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from(route('raw-material.index'))
            ->get(route('raw-material.export', ['status' => 0]));

        $response->assertRedirect(route('raw-material.index'));
        $response->assertSessionHas('error', 'No records found to export for the current filters.');
    });
});

describe('raw material receive exports', function () {
    test('excel export returns spreadsheet when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-receive']);

        seedRawMaterialReceive();

        $response = $this->actingAs($user)->get(route('raw-material.receive.export'));

        $response->assertOk();
        $response->assertDownload('raw-material-receives-' . now()->format('Y-m-d') . '.xlsx');
    });

    test('pdf export returns pdf when user has permission', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-receive']);

        seedRawMaterialReceive();

        $response = $this->actingAs($user)->get(route('raw-material.receive.export-list-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('raw-material-receives-' . now()->format('Y-m-d') . '.pdf');
    });

    test('receive exports are forbidden without permission', function () {
        $user = User::factory()->create();

        seedRawMaterialReceive();

        $this->actingAs($user)->get(route('raw-material.receive.export'))->assertForbidden();
        $this->actingAs($user)->get(route('raw-material.receive.export-list-pdf'))->assertForbidden();
    });

    test('receive export redirects when no records match filters', function () {
        $user = grantPermissions(User::factory()->create(), ['export-raw-material-receive']);

        seedRawMaterialReceive(['status' => 0]);

        $response = $this->actingAs($user)
            ->from(route('raw-material.receive.index'))
            ->get(route('raw-material.receive.export', ['status' => 2]));

        $response->assertRedirect(route('raw-material.receive.index'));
        $response->assertSessionHas('error', 'No records found to export for the current filters.');
    });
});
