<?php

use App\Models\CityManagement;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\StateManagement;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use App\Services\RawMaterial\RawMaterialDailySummaryService;

function seedDailySummaryFixture(array $overrides = []): array
{
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'RMC-DS-' . uniqid(),
        'name' => 'Daily Summary Category',
        'status' => 1,
    ]);

    $materialA = RawMaterial::create([
        'raw_material_unique_id' => 'RM-DS-A-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name' => 'Soda Material',
        'unit' => 'ton',
        'status' => 1,
    ]);

    $materialB = RawMaterial::create([
        'raw_material_unique_id' => 'RM-DS-B-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name' => 'Maize Material',
        'unit' => 'ton',
        'status' => 1,
    ]);

    $state = StateManagement::create([
        'state_name' => 'Test State ' . uniqid(),
        'status' => 1,
    ]);

    $city = CityManagement::create([
        'state_id' => $state->id,
        'city_name' => $overrides['city_name'] ?? 'Gokak',
        'status' => 1,
    ]);

    $supplierBroker = SupplierBroker::create([
        'name' => $overrides['supplier_broker_name'] ?? 'Nesheil Broker',
        'status' => 1,
    ]);

    $supplier = Supplier::create([
        'name' => $overrides['supplier_name'] ?? 'Roquette Supplier',
        'city_id' => $city->id,
        'status' => 1,
    ]);

    $order = RawMaterialOrder::create(array_merge([
        'order_unique_id' => 'RMO-DS-' . uniqid(),
        'supplier_id' => $supplier->id,
        'supplier_broker_id' => $supplierBroker->id,
        'supplier_order_id' => 'SO-100',
        'order_date' => now()->subDays(10)->toDateString(),
        'status' => 0,
    ], $overrides['order'] ?? []));

    $item = RawMaterialOrderItem::create(array_merge([
        'raw_material_id' => $materialA->id,
        'raw_material_order_id' => $order->id,
        'total_qty' => 200,
        'price' => 65,
    ], $overrides['item'] ?? []));

    if (isset($overrides['on_road_qty'])) {
        RawMaterialReceive::create([
            'raw_material_id' => $materialA->id,
            'raw_material_order_id' => $order->id,
            'raw_material_order_item_id' => $item->id,
            'qty' => $overrides['on_road_qty'],
            'freight' => 0,
            'received_date' => now()->toDateString(),
            'status' => 0,
        ]);
    }

    if (isset($overrides['received_qty'])) {
        RawMaterialReceive::create([
            'raw_material_id' => $materialA->id,
            'raw_material_order_id' => $order->id,
            'raw_material_order_item_id' => $item->id,
            'qty' => $overrides['received_qty'],
            'freight' => 0,
            'received_date' => now()->toDateString(),
            'status' => 1,
        ]);
        $item->refresh();
    }

    return compact('category', 'materialA', 'materialB', 'supplier', 'supplierBroker', 'city', 'order', 'item');
}

test('daily summary uses rate as average when freight is zero', function () {
    $fixture = seedDailySummaryFixture();

    $summary = app(RawMaterialDailySummaryService::class)->build();
    $row = $summary['rows']->first();

    expect($row['freight'])->toBe(0.0)
        ->and($row['rate'])->toBe(65.0)
        ->and($row['average'])->toBe(65.0);
});

test('daily summary party name includes supplier city', function () {
    seedDailySummaryFixture(['city_name' => 'Gokak']);

    $summary = app(RawMaterialDailySummaryService::class)->build();
    $row = $summary['rows']->first();

    expect($row['party_name'])->toBe('Roquette Supplier - Gokak')
        ->and($row['supplier_broker_name'])->toBe('Nesheil Broker');
});

test('daily summary order date filter limits rows', function () {
    $fixture = seedDailySummaryFixture();

    $oldOrder = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-OLD-' . uniqid(),
        'supplier_id' => $fixture['supplier']->id,
        'supplier_broker_id' => $fixture['supplierBroker']->id,
        'order_date' => now()->subMonths(3)->toDateString(),
        'status' => 0,
    ]);

    RawMaterialOrderItem::create([
        'raw_material_id' => $fixture['materialB']->id,
        'raw_material_order_id' => $oldOrder->id,
        'total_qty' => 50,
        'price' => 40,
    ]);

    $from = now()->subMonth()->toDateString();
    $to = now()->toDateString();

    $summary = app(RawMaterialDailySummaryService::class)->build(null, $from, $to);

    expect($summary['rows'])->toHaveCount(1)
        ->and($summary['rows']->first()['order_id'])->toBe($fixture['order']->id);
});

test('daily summary row qty math matches spreadsheet split', function () {
    $fixture = seedDailySummaryFixture([
        'on_road_qty' => 50,
        'received_qty' => 0,
    ]);

    $item = $fixture['item']->fresh();
    $item->update([
        'pending_qty' => 200,
        'received_qty' => 0,
        'pending_price' => 200 * 1000 * 65,
        'received_price' => 0,
    ]);

    $summary = app(RawMaterialDailySummaryService::class)->build();
    $row = $summary['rows']->first();

    expect($row['total_qty'])->toBe(200)
        ->and($row['on_road_qty'])->toBe(50)
        ->and($row['pending_qty'])->toBe(150)
        ->and($row['on_road_qty'] + $row['unloading_qty'] + $row['pending_qty'])->toBe(200);
});

test('daily summary footer totals aggregate open items', function () {
    $fixture = seedDailySummaryFixture([
        'received_qty' => 50,
    ]);

    $fixture['item']->refresh();

    $summary = app(RawMaterialDailySummaryService::class)->build();

    expect($summary['totals']['ordered_qty'])->toBe(200)
        ->and($summary['totals']['unloading_qty'])->toBe(50)
        ->and($summary['totals']['pending']['qty'])->toBe(150)
        ->and($summary['totals']['received']['qty'])->toBe(50);
});

test('daily summary material filter excludes other materials', function () {
    $fixture = seedDailySummaryFixture();

    RawMaterialOrderItem::create([
        'raw_material_id' => $fixture['materialB']->id,
        'raw_material_order_id' => $fixture['order']->id,
        'total_qty' => 100,
        'price' => 40,
    ]);

    $allSummary = app(RawMaterialDailySummaryService::class)->build();
    $filteredSummary = app(RawMaterialDailySummaryService::class)->build($fixture['materialA']->id);

    expect($allSummary['rows'])->toHaveCount(2)
        ->and($filteredSummary['rows'])->toHaveCount(1)
        ->and($filteredSummary['rows']->first()['material_name'])->toBe('Soda Material');
});

test('daily summary excludes cancelled orders and fully received items', function () {
    $open = seedDailySummaryFixture();

    $cancelledOrder = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-CANCEL-' . uniqid(),
        'supplier_id' => $open['supplier']->id,
        'order_date' => now()->toDateString(),
        'status' => 3,
    ]);

    RawMaterialOrderItem::create([
        'raw_material_id' => $open['materialA']->id,
        'raw_material_order_id' => $cancelledOrder->id,
        'total_qty' => 80,
        'price' => 50,
        'status' => 3,
    ]);

    $receivedOrder = RawMaterialOrder::create([
        'order_unique_id' => 'RMO-RECEIVED-' . uniqid(),
        'supplier_id' => $open['supplier']->id,
        'order_date' => now()->toDateString(),
        'status' => 2,
    ]);

    RawMaterialOrderItem::create([
        'raw_material_id' => $open['materialB']->id,
        'raw_material_order_id' => $receivedOrder->id,
        'total_qty' => 60,
        'price' => 55,
    ])->update([
        'pending_qty' => 0,
        'received_qty' => 60,
        'pending_price' => 0,
        'received_price' => 60 * 1000 * 55,
        'status' => 2,
    ]);

    $summary = app(RawMaterialDailySummaryService::class)->build();

    expect($summary['rows'])->toHaveCount(1)
        ->and($summary['rows']->first()['order_id'])->toBe($open['order']->id);
});

test('dashboard shows daily summary widget for permitted users', function () {
    $user = grantPermissions(User::factory()->create(), ['raw-material-daily-summary']);

    seedDailySummaryFixture();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Daily Raw Material Summary');
    $response->assertSee('id="rm_daily_summary_table"', false);

    $this->actingAs($user)
        ->getJson(route('dashboard.data.rm-daily-summary'))
        ->assertOk()
        ->assertJsonPath('data.0.party_name', 'Roquette Supplier - Gokak')
        ->assertJsonPath('data.0.supplier_broker_name', 'Nesheil Broker');
});

test('dashboard hides daily summary widget without permission', function () {
    $user = User::factory()->create();

    seedDailySummaryFixture();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Daily Raw Material Summary');
});

test('daily summary excel export returns spreadsheet when user has permission', function () {
    $user = grantPermissions(User::factory()->create(), [
        'export-raw-material-purchas-order',
        'raw-material-daily-summary',
    ]);

    seedDailySummaryFixture();

    $response = $this->actingAs($user)->get(route('dashboard.raw-material-daily-summary.export'));

    $response->assertOk();
    $response->assertDownload('raw-material-daily-summary-' . now()->format('Y-m-d') . '.xlsx');
});

test('daily summary excel export is forbidden without permission', function () {
    $user = User::factory()->create();

    seedDailySummaryFixture();

    $this->actingAs($user)
        ->get(route('dashboard.raw-material-daily-summary.export'))
        ->assertForbidden();
});
