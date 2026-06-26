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
    DB::table('general_settings')->insert([
        ['key' => 'payment_due_days',   'value' => '3', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'payment_due_amount', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
    ]);
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
});
