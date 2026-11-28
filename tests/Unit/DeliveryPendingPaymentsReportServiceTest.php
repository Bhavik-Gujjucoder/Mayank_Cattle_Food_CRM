<?php

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderItem;
use App\Models\OrderManagement;
use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryPendingPaymentsReportService;
use App\Services\PaymentReceivableService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function dprsSetup(): array
{
    $brand = BrandManagement::create(['name' => 'Brand-DPRS-' . uniqid(), 'status' => 1]);

    $broker = User::factory()->create(['status' => 1]);
    $broker->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'broker', 'guard_name' => 'web']));

    $dealerUser = User::factory()->create(['status' => 1]);
    $dealerUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'dealer', 'guard_name' => 'web']));

    $dealer = DealerManagement::create([
        'broker_id'         => $broker->id,
        'brand_id'          => $brand->id,
        'user_id'           => $dealerUser->id,
        'code_no'           => 'D-DPRS-' . uniqid(),
        'firm_shop_name'    => 'Firm-DPRS-' . uniqid(),
        'firm_shop_address' => 'Test Address',
    ]);

    $product = Product::create([
        'name'     => 'Prod-DPRS-' . uniqid(),
        'brand_id' => $brand->id,
        'unit'     => 'Bag',
        'price'    => 100,
        'status'   => 1,
    ]);

    $order = OrderManagement::create([
        'unique_order_id'    => 'ORD-DPRS-' . uniqid(),
        'broker_id'          => $broker->id,
        'brand_id'           => $brand->id,
        'dealer_id'          => $dealer->id,
        'order_date'         => '2026-01-01',
        'delivery_address'   => 'Test Address',
        'payment_status'     => 'unpaid',
        'total_order_amount' => 1000,
        'grand_total'        => 1000,
        'status'             => 1,
    ]);

    $orderItem = OrderItem::create([
        'order_id'    => $order->id,
        'product_id'  => $product->id,
        'qty'         => 10,
        'unit_price'  => 100,
        'total_price' => 1000,
    ]);

    $transporter = User::factory()->create(['status' => 1]);
    $transporter->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'transporter', 'guard_name' => 'web']));

    return compact('brand', 'broker', 'dealerUser', 'dealer', 'product', 'order', 'orderItem', 'transporter');
}

function dprsDispatch(array $s, array $attrs = []): DispatchManagement
{
    return DispatchManagement::create(array_merge([
        'order_id'       => $s['order']->id,
        'order_item_id'  => $s['orderItem']->id,
        'product_id'     => $s['product']->id,
        'no_of_bags'     => 5,
        'dispatch_date'  => '2026-01-01',
        'transport_id'   => $s['transporter']->id,
        'truck_number'   => 'GJ01AA1234',
        'driver_contact' => '9876543210',
        'status'         => DispatchManagement::STATUS_UNPAID,
    ], $attrs));
}

// ─────────────────────────────────────────────

beforeEach(function () {
    Mail::fake();
    foreach (['super admin', 'admin', 'broker', 'dealer', 'transporter'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
    $now = now();
    foreach ([
        'cash_due_days' => '3',
        'cash_due_amount' => '10',
        'credit_due_days' => '3',
        'credit_due_amount' => '10',
    ] as $key => $value) {
        DB::table('general_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
        );
    }
});

// ─────────────────────────────────────────────

describe('formatPendingDaysLabel', function () {
    it('formats single dispatch with days and date', function () {
        $items = [
            ['days' => 15, 'dispatch_date' => '5 Jan 2026'],
        ];
        expect(DeliveryPendingPaymentsReportService::formatPendingDaysLabel($items))
            ->toBe('15 (5 Jan 2026)');
    });

    it('formats multiple dispatches separated by dashes', function () {
        $items = [
            ['days' => 15, 'dispatch_date' => '5 Jan 2026'],
            ['days' => 13, 'dispatch_date' => '7 Jan 2026'],
        ];
        expect(DeliveryPendingPaymentsReportService::formatPendingDaysLabel($items))
            ->toBe('15 (5 Jan 2026) - 13 (7 Jan 2026)');
    });

    it('returns empty string for empty items array', function () {
        expect(DeliveryPendingPaymentsReportService::formatPendingDaysLabel([]))->toBe('');
    });
});

// ─────────────────────────────────────────────

describe('formatBrandSectionTitle', function () {
    it('appends Brand when name does not end with brand', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle('Mayank'))
            ->toBe('Mayank Brand');
    });

    it('does not double-append Brand when name already ends with it', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle('Mayank Brand'))
            ->toBe('Mayank Brand');
    });

    it('is case-insensitive for the Brand suffix check', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle('Mayank BRAND'))
            ->toBe('Mayank BRAND');
    });

    it('returns dash for empty string', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle(''))->toBe('—');
    });

    it('returns dash for placeholder dash', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle('—'))->toBe('—');
    });

    it('trims surrounding whitespace before checking', function () {
        expect(DeliveryPendingPaymentsReportService::formatBrandSectionTitle('  '))->toBe('—');
    });
});

// ─────────────────────────────────────────────

describe('dayAgingColors', function () {
    it('returns green colors for low aging level', function () {
        $colors = DeliveryPendingPaymentsReportService::dayAgingColors('low');

        expect($colors['fill'])->toBe('F0FDF4')
            ->and($colors['num'])->toBe('15803D')
            ->and($colors['border'])->toBe('BBF7D0')
            ->and($colors['date'])->toBe('64748B');
    });

    it('returns amber colors for mid aging level', function () {
        $colors = DeliveryPendingPaymentsReportService::dayAgingColors('mid');

        expect($colors['fill'])->toBe('FFFBEB')
            ->and($colors['num'])->toBe('B45309')
            ->and($colors['border'])->toBe('FDE68A');
    });

    it('returns red colors for high aging level', function () {
        $colors = DeliveryPendingPaymentsReportService::dayAgingColors('high');

        expect($colors['fill'])->toBe('FEF2F2')
            ->and($colors['num'])->toBe('B91C1C')
            ->and($colors['border'])->toBe('FECACA');
    });

    it('returns red colors for unknown aging level', function () {
        $colors = DeliveryPendingPaymentsReportService::dayAgingColors('unknown');

        expect($colors['fill'])->toBe('FEF2F2');
    });

    it('returns array with exactly four keys', function () {
        $colors = DeliveryPendingPaymentsReportService::dayAgingColors('low');
        expect(array_keys($colors))->toEqual(['fill', 'border', 'num', 'date']);
    });
});

// ─────────────────────────────────────────────

describe('build', function () {
    it('returns empty collection when no pending dispatches exist', function () {
        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build();

        expect($result)->toBeEmpty();
    });

    it('excludes paid dispatches from the report', function () {
        $s = dprsSetup();
        dprsDispatch($s, ['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_PAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build();

        expect($result)->toBeEmpty();
    });

    it('includes unpaid dispatches grouped by brand', function () {
        $s = dprsSetup();
        dprsDispatch($s, ['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build();

        expect($result)->toHaveCount(1)
            ->and($result->first()['brand_id'])->toBe((int) $s['brand']->id)
            ->and($result->first()['rows'])->not->toBeEmpty();
    });

    it('filters by brand_id when brandFilter is provided', function () {
        $s1 = dprsSetup();
        $s2 = dprsSetup();
        dprsDispatch($s1, ['status' => DispatchManagement::STATUS_UNPAID]);
        dprsDispatch($s2, ['status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build((string) $s1['brand']->id);

        expect($result)->toHaveCount(1)
            ->and($result->first()['brand_id'])->toBe((int) $s1['brand']->id);
    });

    it('returns all brands when brandFilter is all', function () {
        $s1 = dprsSetup();
        $s2 = dprsSetup();
        dprsDispatch($s1, ['status' => DispatchManagement::STATUS_UNPAID]);
        dprsDispatch($s2, ['status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build('all');

        expect($result)->toHaveCount(2);
    });

    it('excludes dispatches below minDays threshold', function () {
        $s = dprsSetup();
        // dispatch_date far in the past so days_since_dispatch is very high
        dprsDispatch($s, ['dispatch_date' => '2020-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        // Very high minDays threshold — no dispatch can match
        $result  = $service->build('all', 999_999);

        expect($result)->toBeEmpty();
    });

    it('includes dispatches meeting minDays threshold', function () {
        $s = dprsSetup();
        // dispatch_date far in the past so days_since_dispatch is very high
        dprsDispatch($s, ['dispatch_date' => '2020-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $result  = $service->build('all', 1);

        expect($result)->toHaveCount(1);
    });

    it('lists every unpaid dispatch for one dealer on a single row', function () {
        $s = dprsSetup();
        dprsDispatch($s, ['dispatch_date' => '2026-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);
        dprsDispatch($s, ['dispatch_date' => '2026-01-10', 'status' => DispatchManagement::STATUS_UNPAID]);

        $secondOrder = OrderManagement::create([
            'unique_order_id'    => 'ORD-DPRS-B-' . uniqid(),
            'broker_id'          => $s['broker']->id,
            'brand_id'           => $s['brand']->id,
            'dealer_id'          => $s['dealer']->id,
            'order_date'         => '2026-02-01',
            'delivery_address'   => 'Test Address',
            'payment_status'     => 'unpaid',
            'total_order_amount' => 500,
            'grand_total'        => 500,
            'status'             => 1,
        ]);
        $secondItem = OrderItem::create([
            'order_id'    => $secondOrder->id,
            'product_id'  => $s['product']->id,
            'qty'         => 5,
            'unit_price'  => 100,
            'total_price' => 500,
        ]);
        dprsDispatch($s, [
            'order_id'      => $secondOrder->id,
            'order_item_id' => $secondItem->id,
            'dispatch_date' => '2026-02-01',
            'no_of_bags'    => 2,
            'status'        => DispatchManagement::STATUS_UNPAID,
        ]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $rows = $service->build()->first()['rows'];

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['dealer_name'])->toBe($s['dealerUser']->name)
            ->and($rows[0]['broker_name'])->toBe($s['broker']->name)
            ->and(collect($rows[0]['orders'])->pluck('order_id')->all())
                ->toEqualCanonicalizing([(int) $s['order']->id, (int) $secondOrder->id])
            ->and($rows[0]['pending_days_items'])->toHaveCount(3)
            ->and($rows[0]['total_balance_due'])->toBeGreaterThan(0)
            ->and(collect($rows[0]['pending_days_items'])->pluck('order_id')->unique()->count())->toBe(2);
    });

    it('keeps different dealers on separate rows', function () {
        $first = dprsSetup();
        $second = dprsSetup();
        dprsDispatch($first, ['status' => DispatchManagement::STATUS_UNPAID]);
        dprsDispatch($second, ['status' => DispatchManagement::STATUS_UNPAID]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $names = $service->build()->flatMap(fn ($section) => collect($section['rows'])->pluck('dealer_name'));

        expect($names)->toHaveCount(2)
            ->and($names->all())->toContain($first['dealerUser']->name)
            ->and($names->all())->toContain($second['dealerUser']->name);
    });

    it('groups retail orders that share a party name', function () {
        $s = dprsSetup();
        $makeRetail = function (string $party, string $suffix) use ($s) {
            $order = OrderManagement::create([
                'unique_order_id'    => 'ORD-RTL-' . $suffix . '-' . uniqid(),
                'order_type'         => OrderManagement::TYPE_RETAIL,
                'party_name'         => $party,
                'broker_id'          => $s['broker']->id,
                'brand_id'           => $s['brand']->id,
                'dealer_id'          => null,
                'order_date'         => '2026-03-01',
                'payment_status'     => 'unpaid',
                'total_order_amount' => 200,
                'grand_total'        => 200,
                'status'             => 1,
            ]);
            $item = OrderItem::create([
                'order_id'    => $order->id,
                'product_id'  => $s['product']->id,
                'qty'         => 2,
                'unit_price'  => 100,
                'total_price' => 200,
            ]);
            dprsDispatch($s, [
                'order_id'      => $order->id,
                'order_item_id' => $item->id,
                'dispatch_date' => '2026-03-01',
                'status'        => DispatchManagement::STATUS_UNPAID,
            ]);

            return $order;
        };

        $sameA = $makeRetail('Village Shop', 'A');
        $sameB = $makeRetail('Village Shop', 'B');
        $makeRetail('Other Shop', 'C');

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $rows = collect($service->build((string) $s['brand']->id)->first()['rows']);
        $village = $rows->firstWhere('dealer_name', 'Village Shop');

        expect($rows)->toHaveCount(2)
            ->and($village['orders'])->toHaveCount(2)
            ->and(collect($village['orders'])->pluck('order_id')->all())
                ->toEqualCanonicalizing([(int) $sameA->id, (int) $sameB->id]);
    });

    it('drops an order from the dealer row when its dispatches are under minDays', function () {
        $s = dprsSetup();
        dprsDispatch($s, ['dispatch_date' => '2020-01-01', 'status' => DispatchManagement::STATUS_UNPAID]);

        $recent = OrderManagement::create([
            'unique_order_id'    => 'ORD-DPRS-NEW-' . uniqid(),
            'broker_id'          => $s['broker']->id,
            'brand_id'           => $s['brand']->id,
            'dealer_id'          => $s['dealer']->id,
            'order_date'         => now()->toDateString(),
            'delivery_address'   => 'Test Address',
            'payment_status'     => 'unpaid',
            'total_order_amount' => 100,
            'grand_total'        => 100,
            'status'             => 1,
        ]);
        $recentItem = OrderItem::create([
            'order_id'    => $recent->id,
            'product_id'  => $s['product']->id,
            'qty'         => 1,
            'unit_price'  => 100,
            'total_price' => 100,
        ]);
        dprsDispatch($s, [
            'order_id'      => $recent->id,
            'order_item_id' => $recentItem->id,
            'dispatch_date' => now()->toDateString(),
            'status'        => DispatchManagement::STATUS_UNPAID,
        ]);

        $service = new DeliveryPendingPaymentsReportService(new PaymentReceivableService());
        $row = $service->build('all', 30)->first()['rows'][0];

        expect(collect($row['orders'])->pluck('order_id')->all())->toBe([(int) $s['order']->id])
            ->and(collect($row['pending_days_items'])->pluck('order_id')->unique()->all())
                ->toBe([(int) $s['order']->id]);
    });
});
