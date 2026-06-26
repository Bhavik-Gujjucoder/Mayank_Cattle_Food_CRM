<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;
use App\Support\RawMaterialOrderPriceBasis;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function rmoActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }

    return $user;
}

function mkRmoBroker(array $attrs = []): SupplierBroker
{
    return SupplierBroker::create(array_merge(['name' => 'SB-' . uniqid(), 'status' => 1], $attrs));
}

function mkRmoSupplier(int $brokerId, array $attrs = []): Supplier
{
    return Supplier::create(array_merge([
        'supplier_broker_id' => $brokerId,
        'name'               => 'Sup-' . uniqid(),
        'email'              => uniqid() . '@rmo.test',
        'status'             => 1,
    ], $attrs));
}

function mkRmoCategory(): RawMaterialCategory
{
    return RawMaterialCategory::create([
        'category_unique_id' => 'CAT-O-' . uniqid(),
        'name'               => 'RMOCat-' . uniqid(),
        'status'             => 1,
    ]);
}

function mkRmoMaterial(int $categoryId): RawMaterial
{
    return RawMaterial::create([
        'raw_material_unique_id'   => 'RM-O-' . uniqid(),
        'raw_material_category_id' => $categoryId,
        'name'                     => 'RMOMat-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ]);
}

function mkRmoOrder(int $brokerId, int $supplierId, array $attrs = []): RawMaterialOrder
{
    return RawMaterialOrder::create(array_merge([
        'order_unique_id'    => 'ORD-' . uniqid(),
        'supplier_broker_id' => $brokerId,
        'supplier_id'        => $supplierId,
        'order_date'         => now()->toDateString(),
        'price_basis'        => RawMaterialOrderPriceBasis::FOR_GST,
        'status'             => 0,
    ], $attrs));
}

function mkRmoOrderItem(int $orderId, int $materialId, array $attrs = []): RawMaterialOrderItem
{
    return RawMaterialOrderItem::create(array_merge([
        'raw_material_order_id' => $orderId,
        'raw_material_id'       => $materialId,
        'total_qty'             => 10,
        'price'                 => 500,
        'other_expense'         => 0,
        'pending_qty'           => 10,
        'status'                => 0,
    ], $attrs));
}

function rmoPayload(int $brokerId, int $supplierId, int $materialId, array $overrides = []): array
{
    return array_merge([
        'order_unique_id'    => 'ORD-' . uniqid(),
        'supplier_broker_id' => $brokerId,
        'supplier_id'        => $supplierId,
        'supplier_order_id'  => null,
        'order_date'         => now()->toDateString(),
        'price_basis'        => RawMaterialOrderPriceBasis::FOR_GST,
        'raw_material_id'    => [$materialId],
        'total_qty'          => [10],
        'price'              => ['500.00'],
        'other_expense'      => ['0'],
    ], $overrides);
}

/** Full setup: broker → supplier → category → material → order */
function rmoSetup(): array
{
    $broker   = mkRmoBroker();
    $supplier = mkRmoSupplier($broker->id);
    $category = mkRmoCategory();
    $material = mkRmoMaterial($category->id);
    $order    = mkRmoOrder($broker->id, $supplier->id);
    $item     = mkRmoOrderItem($order->id, $material->id);

    return compact('broker', 'supplier', 'category', 'material', 'order', 'item');
}

// ─────────────────────────────────────────────

beforeEach(function () {
    foreach (['super admin', 'admin'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
});

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from index', function () {
        get(route('raw-material.order.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from create', function () {
        get(route('raw-material.order.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $s = rmoSetup();
        post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id))
            ->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from show', function () {
        $s = rmoSetup();
        get(route('raw-material.order.show', $s['order']))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $s = rmoSetup();
        get(route('raw-material.order.edit', $s['order']))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $s = rmoSetup();
        delete(route('raw-material.order.destroy', $s['order']))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from cancel', function () {
        $s = rmoSetup();
        patch(route('raw-material.order.cancel', $s['order']))->assertRedirect(route('login'));
    });

    it('returns 401 for unauthenticated orderItems JSON request', function () {
        $s = rmoSetup();
        getJson(route('raw-material.order.items', $s['order']))->assertUnauthorized();
    });

    it('returns 403 on index without view-raw-material-purchas-order', function () {
        actingAs(rmoActor())
            ->get(route('raw-material.order.index'))
            ->assertForbidden();
    });

    it('returns 200 on index with view-raw-material-purchas-order', function () {
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.index'))
            ->assertOk();
    });

    it('returns 403 on create without add-raw-material-purchas-order', function () {
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.create'))
            ->assertForbidden();
    });

    it('returns 403 on store without add-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor())
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id))
            ->assertForbidden();
    });

    it('returns 403 on show without view-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor())
            ->get(route('raw-material.order.show', $s['order']))
            ->assertForbidden();
    });

    it('returns 403 on edit without edit-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.edit', $s['order']))
            ->assertForbidden();
    });

    it('returns 403 on update without edit-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->put(route('raw-material.order.update', $s['order']), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id))
            ->assertForbidden();
    });

    it('returns 403 on cancel without edit-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor())
            ->delete(route('raw-material.order.destroy', $s['order']))
            ->assertForbidden();
    });

    it('returns 403 on orderItems without view-raw-material-purchas-order', function () {
        $s = rmoSetup();
        actingAs(rmoActor())
            ->getJson(route('raw-material.order.items', $s['order']))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('renders order index view with suppliers', function () {
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.index'))
            ->assertOk()
            ->assertViewIs('raw_material_order.index')
            ->assertViewHas('suppliers')
            ->assertViewHas('page_title');
    });

    it('returns DataTables JSON on AJAX request', function () {
        rmoSetup();
        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.order.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('filters orders by status in AJAX response', function () {
        $s = rmoSetup();
        mkRmoOrder($s['broker']->id, $s['supplier']->id, ['status' => 2]);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.order.index', ['status' => 2]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters orders by supplier in AJAX response', function () {
        $s = rmoSetup();
        $otherSupplier = mkRmoSupplier($s['broker']->id);
        mkRmoOrder($s['broker']->id, $otherSupplier->id);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.order.index', ['supplier_id' => $s['supplier']->id]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters orders by search term in AJAX response', function () {
        rmoSetup();
        mkRmoOrder(mkRmoBroker()->id, mkRmoSupplier(mkRmoBroker()->id)->id, [
            'order_unique_id' => 'ORD-SEARCH-UNIQUE-999',
        ]);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.order.index', ['search' => ['value' => 'SEARCH-UNIQUE']]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('renders create view with required form data', function () {
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->get(route('raw-material.order.create'))
            ->assertOk()
            ->assertViewIs('raw_material_order.create')
            ->assertViewHas('order_unique_id')
            ->assertViewHas('suppliers')
            ->assertViewHas('categories')
            ->assertViewHas('supplier_brokers')
            ->assertViewHas('price_basis_options');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('rejects missing supplier_broker_id', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['supplier_broker_id' => '']))
            ->assertSessionHasErrors(['supplier_broker_id']);
    });

    it('rejects missing supplier_id', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['supplier_id' => '']))
            ->assertSessionHasErrors(['supplier_id']);
    });

    it('rejects supplier not belonging to the selected broker', function () {
        $broker1  = mkRmoBroker();
        $broker2  = mkRmoBroker();
        $supplier = mkRmoSupplier($broker1->id);
        $material = mkRmoMaterial(mkRmoCategory()->id);

        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($broker2->id, $supplier->id, $material->id))
            ->assertSessionHasErrors(['supplier_id']);
    });

    it('rejects missing order_date', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['order_date' => '']))
            ->assertSessionHasErrors(['order_date']);
    });

    it('rejects future order_date', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['order_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['order_date']);
    });

    it('rejects invalid price_basis', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['price_basis' => 'Invalid']))
            ->assertSessionHasErrors(['price_basis']);
    });

    it('rejects zero quantity', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['total_qty' => [0]]))
            ->assertSessionHasErrors(['total_qty.0']);
    });

    it('rejects zero price', function () {
        $s = rmoSetup();
        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id, ['price' => ['0']]))
            ->assertSessionHasErrors(['price.0']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates order with items and redirects with success', function () {
        $broker   = mkRmoBroker();
        $supplier = mkRmoSupplier($broker->id);
        $material = mkRmoMaterial(mkRmoCategory()->id);
        $orderId  = 'ORD-TEST-' . uniqid();

        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($broker->id, $supplier->id, $material->id, ['order_unique_id' => $orderId]))
            ->assertRedirect(route('raw-material.order.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_orders', ['order_unique_id' => $orderId]);
    });

    it('creates line items for the new order', function () {
        $broker   = mkRmoBroker();
        $supplier = mkRmoSupplier($broker->id);
        $material = mkRmoMaterial(mkRmoCategory()->id);
        $orderId  = 'ORD-ITEMS-' . uniqid();

        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($broker->id, $supplier->id, $material->id, ['order_unique_id' => $orderId]))
            ->assertRedirect();

        $order = RawMaterialOrder::where('order_unique_id', $orderId)->first();
        expect($order)->not->toBeNull()
            ->and($order->items()->count())->toBe(1)
            ->and($order->items()->first()->raw_material_id)->toBe($material->id);
    });

    it('accepts Ex-Factory + GST price basis', function () {
        $broker   = mkRmoBroker();
        $supplier = mkRmoSupplier($broker->id);
        $material = mkRmoMaterial(mkRmoCategory()->id);
        $orderId  = 'ORD-EXF-' . uniqid();

        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($broker->id, $supplier->id, $material->id, [
                'order_unique_id' => $orderId,
                'price_basis'     => RawMaterialOrderPriceBasis::EX_FACTORY_GST,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        assertDatabaseHas('raw_material_orders', [
            'order_unique_id' => $orderId,
            'price_basis'     => RawMaterialOrderPriceBasis::EX_FACTORY_GST,
        ]);
    });

    it('stores null supplier_order_id when field is blank', function () {
        $broker   = mkRmoBroker();
        $supplier = mkRmoSupplier($broker->id);
        $material = mkRmoMaterial(mkRmoCategory()->id);
        $orderId  = 'ORD-SUPID-' . uniqid();

        actingAs(rmoActor(['add-raw-material-purchas-order']))
            ->post(route('raw-material.order.store'), rmoPayload($broker->id, $supplier->id, $material->id, [
                'order_unique_id'   => $orderId,
                'supplier_order_id' => '',
            ]))
            ->assertRedirect();

        expect(RawMaterialOrder::where('order_unique_id', $orderId)->value('supplier_order_id'))->toBeNull();
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('renders show view with order loaded', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.show', $s['order']))
            ->assertOk()
            ->assertViewIs('raw_material_order.show')
            ->assertViewHas('order');
    });

    it('eager loads supplier, items, and receives on show', function () {
        $s = rmoSetup();
        RawMaterialReceive::create([
            'raw_material_id'            => $s['material']->id,
            'raw_material_order_id'      => $s['order']->id,
            'raw_material_order_item_id' => $s['item']->id,
            'qty'                        => 2,
            'freight'                    => 0,
            'received_date'              => now()->toDateString(),
            'status'                     => 0,
        ]);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->get(route('raw-material.order.show', $s['order']));

        $order = $response->viewData('order');
        expect($order->relationLoaded('supplier'))->toBeTrue()
            ->and($order->relationLoaded('items'))->toBeTrue()
            ->and($order->relationLoaded('receives'))->toBeTrue()
            ->and($order->receives)->toHaveCount(1);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('renders edit view for pending order', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->get(route('raw-material.order.edit', $s['order']))
            ->assertOk()
            ->assertViewIs('raw_material_order.edit')
            ->assertViewHas('order')
            ->assertViewHas('old_rows');
    });

    it('redirects to show for non-pending (status != 0) order', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 1]);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->get(route('raw-material.order.edit', $s['order']))
            ->assertRedirect(route('raw-material.order.show', $s['order']))
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('update', function () {
    it('rejects editing a non-pending order', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 2]);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->put(route('raw-material.order.update', $s['order']), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id))
            ->assertRedirect(route('raw-material.order.index'))
            ->assertSessionHas('error');
    });

    it('updates pending order and redirects', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->put(route('raw-material.order.update', $s['order']), rmoPayload($s['broker']->id, $s['supplier']->id, $s['material']->id))
            ->assertRedirect(route('raw-material.order.index'))
            ->assertSessionHas('success');
    });

    it('replaces order line items on update', function () {
        $s = rmoSetup();
        $newMaterial = mkRmoMaterial($s['category']->id);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->put(route('raw-material.order.update', $s['order']), rmoPayload($s['broker']->id, $s['supplier']->id, $newMaterial->id))
            ->assertRedirect();

        $items = $s['order']->fresh()->items;
        expect($items)->toHaveCount(1)
            ->and($items->first()->raw_material_id)->toBe($newMaterial->id);
    });
});

// ─────────────────────────────────────────────

describe('cancel', function () {
    it('cancels pending order (status=0)', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_orders', ['id' => $s['order']->id, 'status' => 3]);
    });

    it('cancels order line items when order is cancelled', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertRedirect();

        assertDatabaseHas('raw_material_order_items', ['id' => $s['item']->id, 'status' => 3]);
    });

    it('cancels partially-received order (status=1)', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 1]);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_orders', ['id' => $s['order']->id, 'status' => 3]);
    });

    it('blocks cancelling an already cancelled order (status=3)', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 3]);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('blocks cancelling a fully-received order (status=2)', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 2]);

        actingAs(rmoActor(['edit-raw-material-purchas-order']))
            ->patch(route('raw-material.order.cancel', $s['order']))
            ->assertRedirect()
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('deletes order with no receives and redirects', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['delete-raw-material-purchas-order']))
            ->delete(route('raw-material.order.destroy', $s['order']))
            ->assertRedirect(route('raw-material.order.index'))
            ->assertSessionHas('success');

        assertSoftDeleted('raw_material_orders', ['id' => $s['order']->id]);
    });

    it('blocks delete when receives exist for this order', function () {
        $s = rmoSetup();

        RawMaterialReceive::create([
            'raw_material_id'            => $s['material']->id,
            'raw_material_order_id'      => $s['order']->id,
            'raw_material_order_item_id' => $s['item']->id,
            'qty'                        => 5,
            'freight'                    => 0,
            'received_date'              => now()->toDateString(),
            'status'                     => 0,
        ]);

        actingAs(rmoActor(['delete-raw-material-purchas-order']))
            ->delete(route('raw-material.order.destroy', $s['order']))
            ->assertRedirect(route('raw-material.order.index'))
            ->assertSessionHas('error');

        assertDatabaseHas('raw_material_orders', ['id' => $s['order']->id, 'deleted_at' => null]);
    });
});

// ─────────────────────────────────────────────

describe('orderItems', function () {
    it('returns JSON list of pending order items', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->getJson(route('raw-material.order.items', $s['order']))
            ->assertOk()
            ->assertJsonStructure([['id', 'label', 'raw_material_id', 'pending_qty']]);
    });

    it('excludes items with zero pending_qty', function () {
        $s = rmoSetup();
        $s['item']->update(['pending_qty' => 0]);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->getJson(route('raw-material.order.items', $s['order']));

        expect($response->json())->toBeEmpty();
    });

    it('excludes cancelled line items', function () {
        $s = rmoSetup();
        $s['item']->update(['status' => 3]);

        $response = actingAs(rmoActor(['view-raw-material-purchas-order']))
            ->getJson(route('raw-material.order.items', $s['order']));

        expect($response->json())->toBeEmpty();
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('returns 403 without export-raw-material-purchas-order permission', function () {
        actingAs(rmoActor())
            ->get(route('raw-material.order.export'))
            ->assertForbidden();
    });

    it('returns excel download with permission', function () {
        rmoSetup();
        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns single-order excel download with permission', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-order-excel', $s['order']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns purchase order pdf download with permission', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.exportPdf', $s['order']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('returns 403 for single-order export without permission', function () {
        $s = rmoSetup();

        actingAs(rmoActor())
            ->get(route('raw-material.order.export-order-excel', $s['order']))
            ->assertForbidden();
    });

    it('returns PDF order list download with permission and records', function () {
        rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-list-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('redirects with error when no orders exist for PDF list export', function () {
        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-list-pdf'))
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('returns 403 for PDF list export without permission', function () {
        actingAs(rmoActor())
            ->get(route('raw-material.order.export-list-pdf'))
            ->assertForbidden();
    });

    it('returns full Excel download with permission', function () {
        rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-full'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns 403 for full export without permission', function () {
        actingAs(rmoActor())
            ->get(route('raw-material.order.export-full'))
            ->assertForbidden();
    });

    it('returns full PDF download with permission', function () {
        rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-full-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('returns 403 for full PDF export without permission', function () {
        actingAs(rmoActor())
            ->get(route('raw-material.order.export-full-pdf'))
            ->assertForbidden();
    });

    it('returns single-order full PDF download with permission', function () {
        $s = rmoSetup();

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-order-pdf', $s['order']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('returns 403 for single-order PDF export without permission', function () {
        $s = rmoSetup();

        actingAs(rmoActor())
            ->get(route('raw-material.order.export-order-pdf', $s['order']))
            ->assertForbidden();
    });

    it('queues ExportRawMaterialFullPdfJob when order count exceeds 1000', function () {
        Bus::fake();

        $broker   = SupplierBroker::create(['name' => 'SB-BULK-' . uniqid(), 'status' => 1]);
        $supplier = Supplier::create([
            'supplier_broker_id' => $broker->id,
            'name'               => 'Sup-BULK-' . uniqid(),
            'email'              => uniqid() . '@bulk.test',
            'status'             => 1,
        ]);

        $rows = array_map(fn ($i) => [
            'order_unique_id' => 'RMO-BULK-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
            'supplier_id'     => $supplier->id,
            'order_date'      => '2026-01-01',
            'status'          => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], range(1, 1001));

        DB::table('raw_material_orders')->insert($rows);

        actingAs(rmoActor(['export-raw-material-purchas-order']))
            ->get(route('raw-material.order.export-full-pdf'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Bus::assertDispatched(\App\Jobs\ExportRawMaterialFullPdfJob::class);
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('isEditable returns true for pending order (status=0)', function () {
        $s = rmoSetup();
        expect($s['order']->isEditable())->toBeTrue();
    });

    it('isEditable returns false for non-pending order', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 1]);
        expect($s['order']->fresh()->isEditable())->toBeFalse();
    });

    it('statusBadge contains Pending for status=0', function () {
        $s = rmoSetup();
        expect($s['order']->statusBadge())->toContain('Pending');
    });

    it('statusBadge contains Partially Received for status=1', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 1]);
        expect($s['order']->fresh()->statusBadge())->toContain('Partially Received');
    });

    it('statusBadge contains Received for status=2', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 2]);
        expect($s['order']->fresh()->statusBadge())->toContain('Received');
    });

    it('statusBadge contains Cancelled for status=3', function () {
        $s = rmoSetup();
        $s['order']->update(['status' => 3]);
        expect($s['order']->fresh()->statusBadge())->toContain('Cancelled');
    });

    it('has many items', function () {
        $s = rmoSetup();
        expect($s['order']->items()->count())->toBeGreaterThanOrEqual(1);
    });

    it('belongs to supplier and supplierBroker', function () {
        $s = rmoSetup();
        expect($s['order']->supplier->id)->toBe($s['supplier']->id);
        expect($s['order']->supplierBroker->id)->toBe($s['broker']->id);
    });
});
