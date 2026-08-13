<?php

use App\Models\BrandManagement;
use App\Models\CityManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\StateManagement;
use App\Models\User;
use App\Services\SalesDailySheetsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

function sdsBroker(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['status' => 1], $attrs));
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

    return $user;
}

function sdsAdmin(): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

    return $user;
}

function sdsCity(string $name = 'Mansa'): CityManagement
{
    $state = StateManagement::create([
        'state_name' => 'Gujarat ' . uniqid(),
        'status'     => 1,
    ]);

    return CityManagement::create([
        'state_id'  => $state->id,
        'city_name' => $name,
        'status'    => 1,
    ]);
}

function sdsPendingOrder(array $overrides = []): array
{
    $broker = $overrides['broker'] ?? sdsBroker(['name' => $overrides['broker_name'] ?? 'Viral Enterprise']);
    $brand = $overrides['brand'] ?? BrandManagement::create([
        'name'   => $overrides['brand_name'] ?? 'Ajay Brand',
        'status' => 1,
    ]);
    $city = $overrides['city'] ?? sdsCity($overrides['city_name'] ?? 'Mansa');
    $dealerUser = User::factory()->create(['status' => 1, 'name' => 'Dealer User']);
    $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-SDS-' . uniqid(),
        'firm_shop_name'    => $overrides['firm'] ?? 'RADHESHYAM TRADERS',
        'firm_shop_address' => 'Addr',
        'city_id'           => $city->id,
    ]);

    $product = $overrides['product'] ?? Product::create([
        'name'     => $overrides['product_name'] ?? 'Cake',
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => $overrides['rate'] ?? 1600,
        'status'   => 1,
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD-SDS-' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => $overrides['order_date'] ?? '2026-07-03',
        'delivery_address'   => 'Addr',
        'payment_status'     => 'unpaid',
        'total_order_amount' => ($overrides['qty'] ?? 500) * ($overrides['rate'] ?? 1600),
        'grand_total'        => ($overrides['qty'] ?? 500) * ($overrides['rate'] ?? 1600),
        'status'             => $overrides['status'] ?? 1,
    ]);

    $item = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => $overrides['qty'] ?? 500,
        'unit_price'  => $overrides['rate'] ?? 1600,
        'total_price' => ($overrides['qty'] ?? 500) * ($overrides['rate'] ?? 1600),
    ]);

    if (isset($overrides['dispatched'])) {
        $transporter = User::factory()->create(['status' => 1]);
        DispatchManagement::create([
            'order_id'         => $order->id,
            'order_item_id'    => $item->id,
            'product_id'       => $product->id,
            'no_of_bags'       => $overrides['dispatched'],
            'dispatch_date'    => $overrides['dispatch_date'] ?? '2026-08-07',
            'transport_id'     => $transporter->id,
            'truck_number'     => 'GJ01AA1234',
            'driver_contact'   => '9876543210',
            'status'           => 0,
            'accrued_late_fee' => 0,
        ]);
    }

    return compact('broker', 'brand', 'dealer', 'product', 'order', 'item', 'city');
}

beforeEach(function () {
    Mail::fake();
    foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter'] as $role) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
    $now = now();
    foreach ([
        'cash_due_days' => '',
        'cash_due_amount' => '',
        'credit_due_days' => '',
        'credit_due_amount' => '',
    ] as $key => $value) {
        DB::table('general_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
        );
    }
});

test('pending rows are grouped by brand broker and product', function () {
    sdsPendingOrder(['brand_name' => 'Ajay Brand', 'broker_name' => 'Viral Enterprise', 'product_name' => 'Cake']);
    sdsPendingOrder(['brand_name' => 'Jay Mahakal', 'broker_name' => 'Local Broker', 'product_name' => 'Pellets']);

    $payload = app(SalesDailySheetsService::class)->build(sdsAdmin());

    expect($payload['brand']['groups'])->toHaveCount(2)
        ->and(collect($payload['brand']['groups'])->pluck('name')->all())->toBe(['Ajay Brand', 'Jay Mahakal'])
        ->and($payload['broker']['groups'])->toHaveCount(2)
        ->and($payload['product']['groups'])->toHaveCount(2)
        ->and(app(SalesDailySheetsService::class)->isEmpty($payload))->toBeFalse();
});

test('party name includes firm and city', function () {
    sdsPendingOrder(['firm' => 'RADHESHYAM TRADERS', 'city_name' => 'Mansa']);

    $payload = app(SalesDailySheetsService::class)->build(sdsAdmin());
    $row = $payload['brand']['groups'][0]['rows'][0];

    expect($row['party_name'])->toBe('RADHESHYAM TRADERS - Mansa')
        ->and($row['order_date'])->toBe('03.07.2026');
});

test('fully dispatched items are excluded and last loading uses latest dispatch', function () {
    sdsPendingOrder(['qty' => 10, 'dispatched' => 10, 'dispatch_date' => '2026-08-01']);
    sdsPendingOrder([
        'qty'           => 500,
        'dispatched'    => 150,
        'dispatch_date' => '2026-08-07',
        'firm'          => 'Open Firm',
    ]);

    $payload = app(SalesDailySheetsService::class)->build(sdsAdmin());
    $row = $payload['brand']['groups'][0]['rows'][0];

    expect($payload['brand']['groups'])->toHaveCount(1)
        ->and($row['total'])->toBe(500)
        ->and($row['pending'])->toBe(350)
        ->and($row['last_loading'])->toBe('07.08.2026')
        ->and($payload['brand']['groups'][0]['totals']['pending'])->toBe(350)
        ->and($payload['brand']['groups'][0]['totals']['total'])->toBe(500);
});

test('inactive orders are excluded', function () {
    sdsPendingOrder(['status' => 0]);

    $payload = app(SalesDailySheetsService::class)->build(sdsAdmin());

    expect(app(SalesDailySheetsService::class)->isEmpty($payload))->toBeTrue();
});

test('broker only sees their own pending orders', function () {
    $brokerA = sdsBroker(['name' => 'Broker A']);
    $brokerB = sdsBroker(['name' => 'Broker B']);
    sdsPendingOrder(['broker' => $brokerA, 'firm' => 'Firm A']);
    sdsPendingOrder(['broker' => $brokerB, 'firm' => 'Firm B']);

    $payload = app(SalesDailySheetsService::class)->build($brokerA);

    expect($payload['broker']['groups'])->toHaveCount(1)
        ->and($payload['broker']['groups'][0]['name'])->toBe('Broker A')
        ->and($payload['broker']['groups'][0]['rows'][0]['party_name'])->toStartWith('Firm A');
});
