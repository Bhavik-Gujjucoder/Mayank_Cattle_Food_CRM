<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Services\RawMaterialCacheService;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function rcsSetup(): array
{
    $broker   = SupplierBroker::create(['name' => 'SB-RCS-' . uniqid(), 'status' => 1]);
    $supplier = Supplier::create([
        'supplier_broker_id' => $broker->id,
        'name'               => 'Sup-RCS-' . uniqid(),
        'email'              => uniqid() . '@rcs.test',
        'status'             => 1,
    ]);
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'CAT-RCS-' . uniqid(),
        'name'               => 'RcsCat-' . uniqid(),
        'status'             => 1,
    ]);
    $material = RawMaterial::create([
        'raw_material_unique_id'   => 'RM-RCS-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name'                     => 'RcsMat-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ]);
    $order = RawMaterialOrder::create([
        'order_unique_id'    => 'ORD-RCS-' . uniqid(),
        'supplier_broker_id' => $broker->id,
        'supplier_id'        => $supplier->id,
        'order_date'         => now()->toDateString(),
        'price_basis'        => 'FOR + GST',
        'status'             => 0,
    ]);

    return compact('broker', 'supplier', 'category', 'material', 'order');
}

/** Create an order item bypassing observers, return its model. */
function rcsItem(array $s, array $attrs = []): RawMaterialOrderItem
{
    $id = DB::table('raw_material_order_items')->insertGetId(array_merge([
        'raw_material_order_id' => $s['order']->id,
        'raw_material_id'       => $s['material']->id,
        'total_qty'             => 10,
        'price'                 => 500,
        'other_expense'         => 0,
        'pending_qty'           => 10,
        'received_qty'          => 0,
        'pending_price'         => 5_000_000,
        'received_price'        => 0,
        'total_price'           => 5_000_000,
        'total_freight'         => 0,
        'price_avg'             => 0,
        'status'                => 0,
        'created_at'            => now(),
        'updated_at'            => now(),
    ], $attrs));

    return RawMaterialOrderItem::find($id);
}

/** Create a receive bypassing observers, return its model. */
function rcsReceive(array $s, RawMaterialOrderItem $item, array $attrs = []): RawMaterialReceive
{
    $id = DB::table('raw_material_receives')->insertGetId(array_merge([
        'raw_material_order_id'      => $s['order']->id,
        'raw_material_order_item_id' => $item->id,
        'raw_material_id'            => $s['material']->id,
        'qty'                        => 5,
        'freight'                    => 100,
        'status'                     => 1,
        'received_date'              => now()->toDateString(),
        'created_at'                 => now(),
        'updated_at'                 => now(),
    ], $attrs));

    return RawMaterialReceive::find($id);
}

// ─────────────────────────────────────────────

describe('receiveFreightAmount', function () {
    it('calculates freight as rate times qty', function () {
        $receive = RawMaterialReceive::make(['freight' => 100, 'qty' => 5]);
        expect(RawMaterialCacheService::receiveFreightAmount($receive))->toBe(500.0);
    });

    it('rounds to 3 decimal places', function () {
        $receive = RawMaterialReceive::make(['freight' => 33.333, 'qty' => 3]);
        expect(RawMaterialCacheService::receiveFreightAmount($receive))->toBe(99.999);
    });

    it('returns zero when qty is zero', function () {
        $receive = RawMaterialReceive::make(['freight' => 100, 'qty' => 0]);
        expect(RawMaterialCacheService::receiveFreightAmount($receive))->toBe(0.0);
    });
});

// ─────────────────────────────────────────────

describe('receiveFreightRateLabel', function () {
    it('formats rate as rupee per ton', function () {
        $receive = RawMaterialReceive::make(['freight' => 150.50]);
        expect(RawMaterialCacheService::receiveFreightRateLabel($receive))->toBe('₹ 150.50/ton');
    });

    it('formats zero freight correctly', function () {
        $receive = RawMaterialReceive::make(['freight' => 0]);
        expect(RawMaterialCacheService::receiveFreightRateLabel($receive))->toBe('₹ 0.00/ton');
    });
});

// ─────────────────────────────────────────────

describe('receiveFreightLineLabel', function () {
    it('formats line total with Line prefix', function () {
        $receive = RawMaterialReceive::make(['freight' => 100, 'qty' => 5]);
        expect(RawMaterialCacheService::receiveFreightLineLabel($receive))->toBe('Line: ₹ 500.00');
    });
});

// ─────────────────────────────────────────────

describe('receiveFreightHtml', function () {
    it('contains rate label and line label wrapped in html', function () {
        $receive = RawMaterialReceive::make(['freight' => 100, 'qty' => 5]);
        $html    = RawMaterialCacheService::receiveFreightHtml($receive);

        expect($html)->toContain('₹ 100.00/ton')
            ->and($html)->toContain('Line: ₹ 500.00')
            ->and($html)->toContain('<br>')
            ->and($html)->toContain('text-muted');
    });
});

// ─────────────────────────────────────────────

describe('receiveFreightPdfHtml', function () {
    it('contains rate label and line label with pdf-specific span', function () {
        $receive = RawMaterialReceive::make(['freight' => 80, 'qty' => 10]);
        $html    = RawMaterialCacheService::receiveFreightPdfHtml($receive);

        expect($html)->toContain('₹ 80.00/ton')
            ->and($html)->toContain('Line: ₹ 800.00')
            ->and($html)->toContain('freight-sub');
    });
});

// ─────────────────────────────────────────────

describe('receiveFreightPlain', function () {
    it('joins rate and line labels with newline', function () {
        $receive = RawMaterialReceive::make(['freight' => 50, 'qty' => 4]);
        $plain   = RawMaterialCacheService::receiveFreightPlain($receive);

        expect($plain)->toContain('₹ 50.00/ton')
            ->and($plain)->toContain('Line: ₹ 200.00')
            ->and($plain)->toContain("\n");
    });
});

// ─────────────────────────────────────────────

describe('initializeOrderItem', function () {
    it('calculates total_price from total_qty and price', function () {
        $item = RawMaterialOrderItem::make(['total_qty' => 10, 'price' => 500, 'other_expense' => 0]);
        RawMaterialCacheService::initializeOrderItem($item);

        // 10 * 1000 * 500 = 5,000,000
        expect((float) $item->total_price)->toBe(5_000_000.0);
    });

    it('sets received_qty, total_freight, and price_avg to zero', function () {
        $item = RawMaterialOrderItem::make(['total_qty' => 5, 'price' => 100, 'other_expense' => 0]);
        RawMaterialCacheService::initializeOrderItem($item);

        expect((int) $item->received_qty)->toBe(0)
            ->and((float) $item->total_freight)->toBe(0.0)
            ->and((float) $item->price_avg)->toBe(0.0);
    });

    it('sets pending_qty equal to total_qty', function () {
        $item = RawMaterialOrderItem::make(['total_qty' => 8, 'price' => 200, 'other_expense' => 0]);
        RawMaterialCacheService::initializeOrderItem($item);

        expect((int) $item->pending_qty)->toBe(8);
    });

    it('sets status to 0 (pending)', function () {
        $item = RawMaterialOrderItem::make(['total_qty' => 5, 'price' => 100, 'other_expense' => 0]);
        RawMaterialCacheService::initializeOrderItem($item);

        expect((int) $item->status)->toBe(0);
    });
});

// ─────────────────────────────────────────────

describe('syncItemStatus', function () {
    it('sets status to 0 when received_qty is 0', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 0, 'status' => 1]);

        RawMaterialCacheService::syncItemStatus($item);

        $item->refresh();
        expect((int) $item->status)->toBe(0);
    });

    it('sets status to 2 when received_qty equals total_qty', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 10, 'total_qty' => 10, 'status' => 1]);

        RawMaterialCacheService::syncItemStatus($item);

        $item->refresh();
        expect((int) $item->status)->toBe(2);
    });

    it('sets status to 1 when partially received', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 5, 'total_qty' => 10, 'status' => 0]);

        RawMaterialCacheService::syncItemStatus($item);

        $item->refresh();
        expect((int) $item->status)->toBe(1);
    });

    it('skips update when item is cancelled (status 3)', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 0, 'status' => 3]);

        RawMaterialCacheService::syncItemStatus($item);

        $item->refresh();
        expect((int) $item->status)->toBe(3); // unchanged
    });
});

// ─────────────────────────────────────────────

describe('recalculateOrder', function () {
    it('updates order total_qty from sum of items', function () {
        $s     = rcsSetup();
        $item1 = rcsItem($s, ['total_qty' => 10]);
        $item2 = rcsItem($s, ['total_qty' => 5]);

        $order = $s['order']->fresh();
        RawMaterialCacheService::recalculateOrder($order);

        $order->refresh();
        expect((int) $order->total_qty)->toBe(15);
    });

    it('updates order total_price as sum of item total_price plus other_expense', function () {
        $s    = rcsSetup();
        // total_price=5_000_000, other_expense=1000 → contributes 5_001_000
        rcsItem($s, ['total_qty' => 10, 'price' => 500, 'total_price' => 5_000_000, 'other_expense' => 1000]);

        $order = $s['order']->fresh();
        RawMaterialCacheService::recalculateOrder($order);

        $order->refresh();
        expect((float) $order->total_price)->toBe(5_001_000.0);
    });

    it('updates order total_freight from sum of items', function () {
        $s    = rcsSetup();
        rcsItem($s, ['total_freight' => 300]);
        rcsItem($s, ['total_freight' => 200]);

        $order = $s['order']->fresh();
        RawMaterialCacheService::recalculateOrder($order);

        $order->refresh();
        expect((float) $order->total_freight)->toBe(500.0);
    });

    it('skips cancelled orders (status=3)', function () {
        $s     = rcsSetup();
        $order = $s['order'];
        $order->update(['status' => 3, 'total_qty' => 99]);

        RawMaterialCacheService::recalculateOrder($order->fresh());

        $order->refresh();
        expect((int) $order->total_qty)->toBe(99); // unchanged
    });

    it('sets order status to 0 when no items', function () {
        $s     = rcsSetup();
        $order = $s['order'];

        RawMaterialCacheService::recalculateOrder($order);

        $order->refresh();
        expect((int) $order->status)->toBe(0);
    });
});

// ─────────────────────────────────────────────

describe('recalculateItemPriceAvg', function () {
    it('returns zero when received_qty is 0', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 0, 'received_price' => 0, 'total_freight' => 0]);

        RawMaterialCacheService::recalculateItemPriceAvg($item);

        $item->refresh();
        expect((float) $item->price_avg)->toBe(0.0);
    });

    it('calculates price_avg as (received_price + total_freight) / (received_qty * 1000)', function () {
        $s    = rcsSetup();
        // received_price=2_500_000, total_freight=500, received_qty=5
        // price_avg = (2_500_000 + 500) / (5 * 1000) = 2_500_500 / 5000 = 500.1
        $item = rcsItem($s, [
            'received_qty'   => 5,
            'received_price' => 2_500_000,
            'total_freight'  => 500,
        ]);

        RawMaterialCacheService::recalculateItemPriceAvg($item);

        $item->refresh();
        expect((float) $item->price_avg)->toBe(500.1);
    });
});

// ─────────────────────────────────────────────

describe('applyReceive', function () {
    it('increments received_qty and decrements pending_qty', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s, ['total_qty' => 10, 'pending_qty' => 10, 'received_qty' => 0]);
        $receive = rcsReceive($s, $item, ['qty' => 5, 'freight' => 100, 'status' => 0]);

        RawMaterialCacheService::applyReceive($receive);

        $item->refresh();
        expect((int) $item->received_qty)->toBe(5)
            ->and((int) $item->pending_qty)->toBe(5);
    });

    it('adds freight amount to item total_freight', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s, ['total_qty' => 10, 'pending_qty' => 10, 'received_qty' => 0, 'total_freight' => 0]);
        $receive = rcsReceive($s, $item, ['qty' => 5, 'freight' => 100]);

        RawMaterialCacheService::applyReceive($receive);

        $item->refresh();
        // freight=100/ton × qty=5 = 500
        expect((float) $item->total_freight)->toBe(500.0);
    });

    it('increments material stock', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s);
        $receive = rcsReceive($s, $item, ['qty' => 3]);

        $s['material']->update(['total_stock' => 10, 'available_stock' => 10]);

        RawMaterialCacheService::applyReceive($receive);

        $s['material']->refresh();
        expect((float) $s['material']->total_stock)->toBe(13.0)
            ->and((float) $s['material']->available_stock)->toBe(13.0);
    });
});

// ─────────────────────────────────────────────

describe('reverseReceive', function () {
    it('decrements received_qty and increments pending_qty', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s, ['total_qty' => 10, 'pending_qty' => 5, 'received_qty' => 5, 'received_price' => 2_500_000]);
        $receive = rcsReceive($s, $item, ['qty' => 5, 'freight' => 100, 'status' => 1]);

        RawMaterialCacheService::reverseReceive($receive);

        $item->refresh();
        expect((int) $item->received_qty)->toBe(0)
            ->and((int) $item->pending_qty)->toBe(10);
    });

    it('decrements material stock', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s, ['received_qty' => 5, 'pending_qty' => 5, 'received_price' => 2_500_000]);
        $receive = rcsReceive($s, $item, ['qty' => 3]);

        $s['material']->update(['total_stock' => 10, 'available_stock' => 10]);

        RawMaterialCacheService::reverseReceive($receive);

        $s['material']->refresh();
        expect((float) $s['material']->total_stock)->toBe(7.0)
            ->and((float) $s['material']->available_stock)->toBe(7.0);
    });

    it('does not let stock go below zero', function () {
        $s       = rcsSetup();
        $item    = rcsItem($s, ['received_qty' => 5, 'pending_qty' => 5, 'received_price' => 2_500_000]);
        $receive = rcsReceive($s, $item, ['qty' => 5]);

        $s['material']->update(['total_stock' => 2, 'available_stock' => 2]);

        RawMaterialCacheService::reverseReceive($receive);

        $s['material']->refresh();
        expect((float) $s['material']->total_stock)->toBe(0.0)
            ->and((float) $s['material']->available_stock)->toBe(0.0);
    });
});

// ─────────────────────────────────────────────

describe('recalculateItemFreightFromReceives', function () {
    it('rebuilds total_freight from approved receives only', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['total_freight' => 999, 'received_qty' => 5, 'received_price' => 2_500_000]);

        // Approved receive: freight=100, qty=5 → 500
        rcsReceive($s, $item, ['qty' => 5, 'freight' => 100, 'status' => 1]);
        // Pending receive (status=0): should be excluded
        rcsReceive($s, $item, ['qty' => 5, 'freight' => 200, 'status' => 0]);

        RawMaterialCacheService::recalculateItemFreightFromReceives($item);

        $item->refresh();
        expect((float) $item->total_freight)->toBe(500.0);
    });

    it('sets total_freight to zero when no approved receives exist', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['total_freight' => 777]);

        RawMaterialCacheService::recalculateItemFreightFromReceives($item);

        $item->refresh();
        expect((float) $item->total_freight)->toBe(0.0);
    });

    it('sums freight from multiple approved receives', function () {
        $s    = rcsSetup();
        $item = rcsItem($s, ['received_qty' => 8, 'received_price' => 4_000_000]);

        rcsReceive($s, $item, ['qty' => 5, 'freight' => 100, 'status' => 1]); // 500
        rcsReceive($s, $item, ['qty' => 3, 'freight' => 200, 'status' => 1]); // 600

        RawMaterialCacheService::recalculateItemFreightFromReceives($item);

        $item->refresh();
        expect((float) $item->total_freight)->toBe(1100.0);
    });
});
