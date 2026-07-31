<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Models\WeeklyReportItem;
use App\Support\ProductUnit;
use Database\Seeders\WeeklyReportPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['admin', 'super admin', 'broker', 'dealer', 'transporter'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }
    (new WeeklyReportPermissionSeeder)->run();
});

function wrAdmin(array $extraPerms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole('admin');
    foreach ($extraPerms as $perm) {
        Permission::findOrCreate($perm, 'web');
        $user->givePermissionTo($perm);
    }

    return $user;
}

function wrPendingLine(string $unit = 'Bag', int $qty = 100): array
{
    $brand = BrandManagement::create(['name' => 'WR Brand ' . uniqid(), 'status' => 1]);
    $broker = User::factory()->create(['status' => 1]);
    $broker->assignRole('broker');
    $dealerUser = User::factory()->create(['name' => 'Dealer One', 'status' => 1]);
    $dealerUser->assignRole('dealer');
    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-' . uniqid(),
        'firm_shop_name'    => 'Dealer Firm',
        'firm_shop_address' => 'Addr',
    ]);

    $product = Product::create([
        'name'     => 'Cattle Feed',
        'unit'     => $unit,
        'brand_id' => $brand->id,
        'price'    => 100,
        'status'   => 1,
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD/' . uniqid(),
        'order_date'         => now()->toDateString(),
        'dealer_id'          => $dealer->id,
        'brand_id'           => $brand->id,
        'broker_id'          => $broker->id,
        'delivery_address'   => 'Test Address',
        'status'             => 1,
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000,
        'grand_total'        => 1000,
    ]);

    $orderItem = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => $qty,
        'unit_price'  => 10,
        'total_price' => $qty * 10,
    ]);

    return compact('brand', 'dealer', 'product', 'order', 'orderItem');
}

it('reuses existing report when storing the same date again', function () {
    $admin = wrAdmin();

    $this->actingAs($admin)
        ->post(route('weekly-report.store'), [
            'mode' => 'day',
            'report_date' => '2026-07-09',
        ])
        ->assertRedirect();

    expect(WeeklyReport::whereDate('report_date', '2026-07-09')->count())->toBe(1);

    $this->actingAs($admin)
        ->post(route('weekly-report.store'), [
            'mode' => 'day',
            'report_date' => '2026-07-09',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(WeeklyReport::whereDate('report_date', '2026-07-09')->count())->toBe(1);
});

it('creates week shells thursday through wednesday skipping existing', function () {
    $admin = wrAdmin();

    WeeklyReport::create([
        'report_date' => '2026-07-02',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('weekly-report.store'), [
            'mode' => 'week',
            'week_start' => '2026-07-05',
        ])
        ->assertRedirect();

    $dates = WeeklyReport::orderBy('report_date')->pluck('report_date')->map->toDateString()->all();

    expect($dates)->toContain('2026-07-02', '2026-07-03', '2026-07-08');
    expect(count($dates))->toBe(7);
});

it('converts mixed units for footer totals', function () {
    $admin = wrAdmin();
    $ctx = wrPendingLine('Bag', 100);

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 50,
        'created_by' => $admin->id,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 100,
        'status' => WeeklyReportItem::STATUS_PENDING,
    ]);

    $report->load('items.product');

    expect($report->totalQuantityInBags())->toBe(100.0);
    expect($report->differenceInBags())->toBe(50.0);
    expect(round($report->calculatedProductionHours(), 4))->toBe(round(50 / 135, 4));
    expect(ProductUnit::toBags(1, 'Ton'))->toBe(1000 / 60);
});

it('keeps difference equal to total when ready stock is zero', function () {
    $admin = wrAdmin();
    $ctx = wrPendingLine('Bag', 100);

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 100,
        'status' => WeeklyReportItem::STATUS_PENDING,
    ]);

    $report->load('items.product');

    expect($report->totalQuantityInBags())->toBe(100.0);
    expect($report->differenceInBags())->toBe(100.0);
    expect(round($report->calculatedProductionHours(), 4))->toBe(round(100 / 135, 4));
});

it('uses stored bags per hour for production hours and persists changes', function () {
    $admin = wrAdmin();
    $this->actingAs($admin);
    $ctx = wrPendingLine('Bag', 100);
    $service = app(\App\Services\WeeklyReportService::class);

    $report = WeeklyReport::create([
        'report_date' => '2026-07-10',
        'already_produced' => 0,
        'bags_per_hour' => 150,
        'created_by' => $admin->id,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 100,
        'status' => WeeklyReportItem::STATUS_PENDING,
    ]);

    $report->load('items.product');

    expect($report->bagsPerHour())->toBe(150.0);
    expect(round($report->calculatedProductionHours(), 4))->toBe(round(100 / 150, 4));

    $updated = $service->updateFooter($report->fresh(['items.product']), [
        'already_produced' => 25,
        'bags_per_hour' => 200,
    ]);

    expect((float) $updated->bags_per_hour)->toBe(200.0);
    expect(round($updated->calculatedProductionHours(), 4))->toBe(round(75 / 200, 4));
});

it('rejects ready stock greater than total quantity', function () {
    $admin = wrAdmin();
    $this->actingAs($admin);
    $ctx = wrPendingLine('Bag', 100);
    $service = app(\App\Services\WeeklyReportService::class);

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 100,
        'status' => WeeklyReportItem::STATUS_PENDING,
    ]);

    expect(fn () => $service->updateFooter($report->fresh(['items.product']), [
        'already_produced' => 150,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('limits weekly report qty by remaining after other pending reservations', function () {
    $admin = wrAdmin();
    $this->actingAs($admin);

    $ctx = wrPendingLine('Bag', 361);
    $service = app(\App\Services\WeeklyReportService::class);

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    $service->addItem($report, [
        'order_item_id' => $ctx['orderItem']->id,
        'quantity' => 100,
    ]);

    expect($service->availableWeeklyQty($ctx['orderItem']->fresh(['dispatches'])))->toBe(261);

    expect(fn () => $service->addItem($report, [
        'order_item_id' => $ctx['orderItem']->id,
        'quantity' => 262,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    $service->addItem($report, [
        'order_item_id' => $ctx['orderItem']->id,
        'quantity' => 261,
    ]);

    expect($service->availableWeeklyQty($ctx['orderItem']->fresh(['dispatches'])))->toBe(0);
});

it('loads workspace for today on index', function () {
    $admin = wrAdmin(['view-weekly-report', 'add-weekly-report', 'edit-weekly-report']);

    $this->actingAs($admin)
        ->get(route('weekly-report.index'))
        ->assertOk()
        ->assertSee('Weekly Report')
        ->assertSee(now()->format('d M Y'));
});

it('redirects legacy show route to workspace date', function () {
    $admin = wrAdmin(['view-weekly-report']);
    $report = WeeklyReport::create([
        'report_date' => '2026-07-11',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('weekly-report.show', $report->id))
        ->assertRedirect(route('weekly-report.index', ['date' => '2026-07-11']));
});

it('allows order number update on confirmed rows', function () {
    $admin = wrAdmin(['view-weekly-report', 'edit-weekly-report']);
    $ctx = wrPendingLine();

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    $item = WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 10,
        'status' => WeeklyReportItem::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->put(route('weekly-report.items.update', [$report->id, $item->id]), [
            'sort_order' => 5,
        ])
        ->assertRedirect();

    expect($item->fresh()->sort_order)->toBe(5);
});

it('locks confirmed rows from quantity update', function () {
    $admin = wrAdmin();
    $ctx = wrPendingLine();

    $report = WeeklyReport::create([
        'report_date' => '2026-07-09',
        'already_produced' => 0,
        'created_by' => $admin->id,
    ]);

    $item = WeeklyReportItem::create([
        'weekly_report_id' => $report->id,
        'sort_order' => 1,
        'order_id' => $ctx['order']->id,
        'order_item_id' => $ctx['orderItem']->id,
        'product_id' => $ctx['product']->id,
        'quantity' => 10,
        'status' => WeeklyReportItem::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->put(route('weekly-report.items.update', [$report->id, $item->id]), [
            'quantity' => 20,
        ])
        ->assertSessionHasErrors('item');
});
