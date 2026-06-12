<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Services\RawMaterial\RawMaterialFilterService;
use Illuminate\Http\Request;

test('material filter search matches material id for export and listing', function () {
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'RMC-FILTER-1',
        'name' => 'Filter Category',
        'status' => 1,
    ]);

    RawMaterial::create([
        'raw_material_unique_id' => 'RM-FILTER-1',
        'raw_material_category_id' => $category->id,
        'name' => 'Alpha Material',
        'unit' => 'kg',
        'status' => 1,
    ]);

    RawMaterial::create([
        'raw_material_unique_id' => 'RM-FILTER-2',
        'raw_material_category_id' => $category->id,
        'name' => 'Beta Material',
        'unit' => 'kg',
        'status' => 1,
    ]);

    $count = RawMaterialFilterService::materials(new Request(['search' => 'RM-FILTER-1']))->count();

    expect($count)->toBe(1);
});
