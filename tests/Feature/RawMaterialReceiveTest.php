<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Models\Supplier;
use App\Models\SupplierBroker;
use App\Models\User;

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

function rmrActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }

    return $user;
}

/** Build a complete receive scenario: broker → supplier → category → material → order → orderItem */
function rmrSetup(int $pendingQty = 10): array
{
    $broker   = SupplierBroker::create(['name' => 'SBR-' . uniqid(), 'status' => 1]);
    $supplier = Supplier::create([
        'supplier_broker_id' => $broker->id,
        'name'               => 'SupR-' . uniqid(),
        'email'              => uniqid() . '@rmr.test',
        'status'             => 1,
    ]);
    $category = RawMaterialCategory::create([
        'category_unique_id' => 'CATR-' . uniqid(),
        'name'               => 'RMRCat-' . uniqid(),
        'status'             => 1,
    ]);
    $material = RawMaterial::create([
        'raw_material_unique_id'   => 'RMR-' . uniqid(),
        'raw_material_category_id' => $category->id,
        'name'                     => 'RMRMat-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ]);
    $order = RawMaterialOrder::create([
        'order_unique_id'    => 'ORDR-' . uniqid(),
        'supplier_broker_id' => $broker->id,
        'supplier_id'        => $supplier->id,
        'order_date'         => now()->toDateString(),
        'price_basis'        => 'FOR + GST',
        'status'             => 0,
    ]);
    $item = $order->items()->create([
        'raw_material_id' => $material->id,
        'total_qty'       => $pendingQty,
        'price'           => 500,
        'other_expense'   => 0,
        'pending_qty'     => $pendingQty,
        'status'          => 0,
    ]);

    return compact('broker', 'supplier', 'category', 'material', 'order', 'item');
}

function rmrReceive(array $s, array $attrs = []): RawMaterialReceive
{
    return RawMaterialReceive::create(array_merge([
        'raw_material_id'            => $s['material']->id,
        'raw_material_order_id'      => $s['order']->id,
        'raw_material_order_item_id' => $s['item']->id,
        'qty'                        => 5,
        'freight'                    => 0,
        'received_date'              => now()->toDateString(),
        'status'                     => 0,
    ], $attrs));
}

function rmrPayload(array $s, array $overrides = []): array
{
    return array_merge([
        'raw_material_order_id'      => $s['order']->id,
        'raw_material_order_item_id' => $s['item']->id,
        'qty'                        => 5,
        'freight'                    => 100,
        'received_date'              => now()->toDateString(),
        'status'                     => 0,
    ], $overrides);
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
        get(route('raw-material.receive.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from create', function () {
        get(route('raw-material.receive.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $s = rmrSetup();
        post(route('raw-material.receive.store'), rmrPayload($s))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from show', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        get(route('raw-material.receive.show', $receive))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        get(route('raw-material.receive.edit', $receive))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        delete(route('raw-material.receive.destroy', $receive))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from markReceived', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        patch(route('raw-material.receive.markReceived', $receive))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from cancel', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        patch(route('raw-material.receive.cancel', $receive))->assertRedirect(route('login'));
    });

    it('returns 403 on index without view-raw-material-receive', function () {
        actingAs(rmrActor())
            ->get(route('raw-material.receive.index'))
            ->assertForbidden();
    });

    it('returns 200 on index with view-raw-material-receive', function () {
        actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.index'))
            ->assertOk();
    });

    it('returns 403 on create without add-raw-material-receive', function () {
        actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.create'))
            ->assertForbidden();
    });

    it('returns 403 on store without add-raw-material-receive', function () {
        $s = rmrSetup();
        actingAs(rmrActor())
            ->post(route('raw-material.receive.store'), rmrPayload($s))
            ->assertForbidden();
    });

    it('returns 403 on show without view-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor())
            ->get(route('raw-material.receive.show', $receive))
            ->assertForbidden();
    });

    it('returns 403 on edit without edit-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.edit', $receive))
            ->assertForbidden();
    });

    it('returns 403 on update without edit-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor(['view-raw-material-receive']))
            ->put(route('raw-material.receive.update', $receive), rmrPayload($s))
            ->assertForbidden();
    });

    it('returns 403 on markReceived without edit-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor(['view-raw-material-receive']))
            ->patch(route('raw-material.receive.markReceived', $receive))
            ->assertForbidden();
    });

    it('returns 403 on cancel without edit-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor(['view-raw-material-receive']))
            ->patch(route('raw-material.receive.cancel', $receive))
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-raw-material-receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        actingAs(rmrActor())
            ->delete(route('raw-material.receive.destroy', $receive))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('renders receive index view with raw materials and orders', function () {
        actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.index'))
            ->assertOk()
            ->assertViewIs('raw_material_receive.index')
            ->assertViewHas('raw_materials')
            ->assertViewHas('orders')
            ->assertViewHas('page_title');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $s = rmrSetup();
        rmrReceive($s);
        actingAs(rmrActor(['view-raw-material-receive']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.receive.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('filters receives by status in AJAX response', function () {
        $s = rmrSetup();
        rmrReceive($s, ['status' => 0]);
        rmrReceive($s, ['status' => 1]);

        $response = actingAs(rmrActor(['view-raw-material-receive']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.receive.index', ['status' => 1]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters receives by raw material in AJAX response', function () {
        $s = rmrSetup();
        rmrReceive($s);

        $other = rmrSetup();
        rmrReceive($other);

        $response = actingAs(rmrActor(['view-raw-material-receive']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.receive.index', ['raw_material_id' => $s['material']->id]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters receives by search term in AJAX response', function () {
        $s = rmrSetup();
        $s['order']->update(['order_unique_id' => 'ORDR-SEARCH-UNIQUE-777']);
        rmrReceive($s);

        $response = actingAs(rmrActor(['view-raw-material-receive']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.receive.index', ['search' => ['value' => 'SEARCH-UNIQUE']]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('renders create view with orders', function () {
        actingAs(rmrActor(['add-raw-material-receive']))
            ->get(route('raw-material.receive.create'))
            ->assertOk()
            ->assertViewIs('raw_material_receive.create')
            ->assertViewHas('orders');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('rejects missing order id', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['raw_material_order_id' => '']))
            ->assertSessionHasErrors(['raw_material_order_id']);
    });

    it('rejects missing order item id', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['raw_material_order_item_id' => '']))
            ->assertSessionHasErrors(['raw_material_order_item_id']);
    });

    it('rejects zero qty', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['qty' => 0]))
            ->assertSessionHasErrors(['qty']);
    });

    it('rejects qty exceeding pending_qty', function () {
        $s = rmrSetup(5);
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['qty' => 99]))
            ->assertSessionHasErrors(['qty']);
    });

    it('rejects missing received_date', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['received_date' => '']))
            ->assertSessionHasErrors(['received_date']);
    });

    it('rejects invalid status', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['status' => 5]))
            ->assertSessionHasErrors(['status']);
    });

    it('rejects negative freight', function () {
        $s = rmrSetup();
        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['freight' => -1]))
            ->assertSessionHasErrors(['freight']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates receive entry and redirects with success', function () {
        $s = rmrSetup();

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['qty' => 3]))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_receives', [
            'raw_material_order_id'      => $s['order']->id,
            'raw_material_order_item_id' => $s['item']->id,
            'qty'                        => 3,
        ]);
    });

    it('stores receive with status=0 (on-road) correctly', function () {
        $s = rmrSetup();

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['status' => 0]))
            ->assertRedirect();

        $receive = RawMaterialReceive::latest()->first();
        expect((int) $receive->status)->toBe(0);
    });

    it('derives raw_material_id from the selected order item', function () {
        $s = rmrSetup();

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s))
            ->assertRedirect();

        $receive = RawMaterialReceive::latest()->first();
        expect($receive->raw_material_id)->toBe($s['material']->id);
    });

    it('stores zero freight when field is omitted', function () {
        $s = rmrSetup();
        $payload = rmrPayload($s);
        unset($payload['freight']);

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), $payload)
            ->assertRedirect();

        expect((float) RawMaterialReceive::latest()->value('freight'))->toBe(0.0);
    });

    it('does not reduce pending_qty for on-road store', function () {
        $s = rmrSetup(10);

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['qty' => 4, 'status' => 0]))
            ->assertRedirect();

        expect($s['item']->fresh()->pending_qty)->toBe(10);
    });

    it('reduces pending_qty when stored directly as received', function () {
        $s = rmrSetup(10);

        actingAs(rmrActor(['add-raw-material-receive']))
            ->post(route('raw-material.receive.store'), rmrPayload($s, ['qty' => 4, 'status' => 1]))
            ->assertRedirect();

        expect($s['item']->fresh()->pending_qty)->toBe(6);
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('renders show view with loaded receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);

        actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.show', $receive))
            ->assertOk()
            ->assertViewIs('raw_material_receive.show')
            ->assertViewHas('receive');
    });

    it('eager loads order, material, and order item on show', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);

        $response = actingAs(rmrActor(['view-raw-material-receive']))
            ->get(route('raw-material.receive.show', $receive));

        $loaded = $response->viewData('receive');
        expect($loaded->relationLoaded('order'))->toBeTrue()
            ->and($loaded->relationLoaded('rawMaterial'))->toBeTrue()
            ->and($loaded->relationLoaded('orderItem'))->toBeTrue();
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('renders edit view for on-road receive', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->get(route('raw-material.receive.edit', $receive))
            ->assertOk()
            ->assertViewIs('raw_material_receive.edit')
            ->assertViewHas('receive')
            ->assertViewHas('orders')
            ->assertViewHas('order_items');
    });

    it('redirects to show for received entry (status=1)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->get(route('raw-material.receive.edit', $receive))
            ->assertRedirect(route('raw-material.receive.show', $receive))
            ->assertSessionHas('error');
    });

    it('redirects to show for cancelled entry (status=2)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->get(route('raw-material.receive.edit', $receive))
            ->assertRedirect(route('raw-material.receive.show', $receive))
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('update', function () {
    it('rejects editing received entry (status=1)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->put(route('raw-material.receive.update', $receive), rmrPayload($s))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('error');
    });

    it('rejects editing cancelled entry (status=2)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->put(route('raw-material.receive.update', $receive), rmrPayload($s))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('error');
    });

    it('updates on-road receive and redirects', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->put(route('raw-material.receive.update', $receive), rmrPayload($s, ['qty' => 4, 'freight' => 200]))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_receives', ['id' => $receive->id, 'qty' => 4, 'freight' => 200]);
    });
});

// ─────────────────────────────────────────────

describe('markReceived', function () {
    it('marks on-road entry as received', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.markReceived', $receive))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_receives', ['id' => $receive->id, 'status' => 1]);
    });

    it('reduces pending_qty when entry is marked received', function () {
        $s = rmrSetup(10);
        $receive = rmrReceive($s, ['status' => 0, 'qty' => 4]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.markReceived', $receive))
            ->assertRedirect();

        expect($s['item']->fresh()->pending_qty)->toBe(6);
    });

    it('blocks marking already-received entry', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.markReceived', $receive))
            ->assertRedirect()
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('cancel', function () {
    it('cancels on-road entry', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.cancel', $receive))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_receives', ['id' => $receive->id, 'status' => 2]);
    });

    it('blocks cancelling already-received entry', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.cancel', $receive))
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    it('blocks cancelling already-cancelled entry', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);

        actingAs(rmrActor(['edit-raw-material-receive']))
            ->patch(route('raw-material.receive.cancel', $receive))
            ->assertRedirect()
            ->assertSessionHas('error');
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes on-road entry and redirects', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);

        actingAs(rmrActor(['delete-raw-material-receive']))
            ->delete(route('raw-material.receive.destroy', $receive))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('success');

        assertSoftDeleted('raw_material_receives', ['id' => $receive->id]);
    });

    it('soft-deletes cancelled entry', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);

        actingAs(rmrActor(['delete-raw-material-receive']))
            ->delete(route('raw-material.receive.destroy', $receive))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertSoftDeleted('raw_material_receives', ['id' => $receive->id]);
    });

    it('blocks deleting a received entry (status=1)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);

        actingAs(rmrActor(['delete-raw-material-receive']))
            ->delete(route('raw-material.receive.destroy', $receive))
            ->assertRedirect(route('raw-material.receive.index'))
            ->assertSessionHas('error');

        assertDatabaseHas('raw_material_receives', ['id' => $receive->id, 'deleted_at' => null]);
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('returns 403 without export-raw-material-receive permission', function () {
        actingAs(rmrActor())
            ->get(route('raw-material.receive.export'))
            ->assertForbidden();
    });

    it('returns excel download with permission', function () {
        $s = rmrSetup();
        rmrReceive($s);
        actingAs(rmrActor(['export-raw-material-receive']))
            ->get(route('raw-material.receive.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('returns pdf list download with permission', function () {
        $s = rmrSetup();
        rmrReceive($s);
        actingAs(rmrActor(['export-raw-material-receive']))
            ->get(route('raw-material.receive.export-list-pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('isEditable returns true for on-road (status=0)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);
        expect($receive->isEditable())->toBeTrue();
    });

    it('isEditable returns false for received (status=1)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);
        expect($receive->isEditable())->toBeFalse();
    });

    it('isEditable returns false for cancelled (status=2)', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);
        expect($receive->isEditable())->toBeFalse();
    });

    it('statusBadge contains On Road for status=0', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 0]);
        expect($receive->statusBadge())->toContain('On Road');
    });

    it('statusBadge contains Received for status=1', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 1]);
        expect($receive->statusBadge())->toContain('Received');
    });

    it('statusBadge contains Cancelled for status=2', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s, ['status' => 2]);
        expect($receive->statusBadge())->toContain('Cancelled');
    });

    it('belongs to order and rawMaterial', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        expect($receive->order->id)->toBe($s['order']->id);
        expect($receive->rawMaterial->id)->toBe($s['material']->id);
    });

    it('belongs to orderItem', function () {
        $s = rmrSetup();
        $receive = rmrReceive($s);
        expect($receive->orderItem->id)->toBe($s['item']->id);
    });
});
