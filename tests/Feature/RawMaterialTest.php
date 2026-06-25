<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
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

function rmActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }

    return $user;
}

function mkRmCategory(array $attrs = []): RawMaterialCategory
{
    return RawMaterialCategory::create(array_merge([
        'category_unique_id' => 'CAT-RM-' . uniqid(),
        'name'               => 'RMCat-' . uniqid(),
        'status'             => 1,
    ], $attrs));
}

function mkRm(int $categoryId, array $attrs = []): RawMaterial
{
    return RawMaterial::create(array_merge([
        'raw_material_unique_id'   => 'RM-' . uniqid(),
        'raw_material_category_id' => $categoryId,
        'name'                     => 'Material-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ], $attrs));
}

function rmPayload(int $categoryId, array $overrides = []): array
{
    return array_merge([
        'name'                     => 'NewMaterial-' . uniqid(),
        'raw_material_category_id' => $categoryId,
        'unit'                     => 'Ton',
        'status'                   => 1,
    ], $overrides);
}

function rmAttachOrderItem(RawMaterial $rm): void
{
    $broker   = SupplierBroker::create(['name' => 'SB-' . uniqid(), 'status' => 1]);
    $supplier = Supplier::create([
        'supplier_broker_id' => $broker->id,
        'name'               => 'Sup-' . uniqid(),
        'email'              => uniqid() . '@test.com',
        'status'             => 1,
    ]);
    $order = RawMaterialOrder::create([
        'order_unique_id'    => 'ORD-' . uniqid(),
        'supplier_broker_id' => $broker->id,
        'supplier_id'        => $supplier->id,
        'order_date'         => now()->toDateString(),
        'price_basis'        => 'FOR + GST',
    ]);
    $order->items()->create([
        'raw_material_id' => $rm->id,
        'total_qty'       => 10,
        'price'           => 100,
        'other_expense'   => 0,
        'pending_qty'     => 10,
    ]);
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
        get(route('raw-material.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from create', function () {
        get(route('raw-material.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $cat = mkRmCategory();
        post(route('raw-material.store'), rmPayload($cat->id))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from show', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        get(route('raw-material.show', $rm))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        get(route('raw-material.edit', $rm))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        delete(route('raw-material.destroy', $rm))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from toggleStatus', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        patch(route('raw-material.toggleStatus', $rm))->assertRedirect(route('login'));
    });

    it('returns 403 on index without view-raw-material-inventory', function () {
        actingAs(rmActor())
            ->get(route('raw-material.index'))
            ->assertForbidden();
    });

    it('returns 200 on index with view-raw-material-inventory', function () {
        actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.index'))
            ->assertOk();
    });

    it('returns 403 on create without add-raw-material-inventory', function () {
        actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.create'))
            ->assertForbidden();
    });

    it('returns 403 on store without add-raw-material-inventory', function () {
        $cat = mkRmCategory();
        actingAs(rmActor())
            ->post(route('raw-material.store'), rmPayload($cat->id))
            ->assertForbidden();
    });

    it('returns 403 on show without view-raw-material-inventory', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        actingAs(rmActor())
            ->get(route('raw-material.show', $rm))
            ->assertForbidden();
    });

    it('returns 403 on edit without edit-raw-material-inventory', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.edit', $rm))
            ->assertForbidden();
    });

    it('returns 403 on update without edit-raw-material-inventory', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        actingAs(rmActor(['view-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($cat->id))
            ->assertForbidden();
    });

    it('returns 403 on toggleStatus without edit-raw-material-inventory', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        actingAs(rmActor(['view-raw-material-inventory']))
            ->patch(route('raw-material.toggleStatus', $rm))
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-raw-material-inventory', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        actingAs(rmActor())
            ->delete(route('raw-material.destroy', $rm))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('renders raw material index view with page title', function () {
        actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.index'))
            ->assertOk()
            ->assertViewIs('raw_material.index')
            ->assertViewHas('page_title');
    });

    it('returns DataTables JSON on AJAX request', function () {
        $cat = mkRmCategory();
        mkRm($cat->id);
        actingAs(rmActor(['view-raw-material-inventory']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('filters materials by status in AJAX response', function () {
        $cat = mkRmCategory();
        mkRm($cat->id, ['status' => 1]);
        mkRm($cat->id, ['status' => 0]);

        $response = actingAs(rmActor(['view-raw-material-inventory']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.index', ['status' => 1]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters materials by category in AJAX response', function () {
        $catA = mkRmCategory();
        $catB = mkRmCategory();
        mkRm($catA->id);
        mkRm($catB->id);

        $response = actingAs(rmActor(['view-raw-material-inventory']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.index', ['raw_material_category_id' => $catA->id]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters materials by search term in AJAX response', function () {
        $cat = mkRmCategory();
        mkRm($cat->id, ['name' => 'SEARCH-UNIQUE-MAT-888']);

        $response = actingAs(rmActor(['view-raw-material-inventory']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.index', ['search' => ['value' => 'SEARCH-UNIQUE']]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('renders create view with categories and generated unique id', function () {
        actingAs(rmActor(['add-raw-material-inventory']))
            ->get(route('raw-material.create'))
            ->assertOk()
            ->assertViewIs('raw_material.create')
            ->assertViewHas('categories')
            ->assertViewHas('raw_material_unique_id');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('rejects missing name', function () {
        $cat = mkRmCategory();
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['name' => '']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects duplicate name (ignoring soft-deleted)', function () {
        $cat = mkRmCategory();
        mkRm($cat->id, ['name' => 'UniqueMat']);
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['name' => 'UniqueMat']))
            ->assertSessionHasErrors(['name']);
    });

    it('allows reusing soft-deleted material name', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id, ['name' => 'SoftDelMat']);
        $rm->delete();
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['name' => 'SoftDelMat']))
            ->assertSessionDoesntHaveErrors(['name']);
    });

    it('rejects missing category', function () {
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload(99999))
            ->assertSessionHasErrors(['raw_material_category_id']);
    });

    it('rejects missing unit', function () {
        $cat = mkRmCategory();
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['unit' => '']))
            ->assertSessionHasErrors(['unit']);
    });

    it('rejects invalid status', function () {
        $cat = mkRmCategory();
        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['status' => 5]))
            ->assertSessionHasErrors(['status']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates raw material and redirects with success', function () {
        $cat  = mkRmCategory();
        $name = 'StoreMat-' . uniqid();

        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['name' => $name]))
            ->assertRedirect(route('raw-material.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_materials', ['name' => $name, 'raw_material_category_id' => $cat->id]);
    });

    it('auto-generates raw_material_unique_id on store', function () {
        $cat  = mkRmCategory();
        $name = 'AutoIdMat-' . uniqid();

        actingAs(rmActor(['add-raw-material-inventory']))
            ->post(route('raw-material.store'), rmPayload($cat->id, ['name' => $name]))
            ->assertRedirect();

        $rm = RawMaterial::where('name', $name)->first();
        expect($rm->raw_material_unique_id)->not->toBeEmpty();
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('renders show view with raw material and order items', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.show', $rm))
            ->assertOk()
            ->assertViewIs('raw_material.show')
            ->assertViewHas('raw_material')
            ->assertViewHas('order_items');
    });

    it('eager loads category on show', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        $response = actingAs(rmActor(['view-raw-material-inventory']))
            ->get(route('raw-material.show', $rm));

        $loaded = $response->viewData('raw_material');
        expect($loaded->relationLoaded('category'))->toBeTrue()
            ->and($loaded->category->id)->toBe($cat->id);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('renders edit view with categories', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->get(route('raw-material.edit', $rm))
            ->assertOk()
            ->assertViewIs('raw_material.edit')
            ->assertViewHas('raw_material')
            ->assertViewHas('categories');
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('rejects duplicate name on update', function () {
        $cat = mkRmCategory();
        mkRm($cat->id, ['name' => 'ExistingMat']);
        $rm = mkRm($cat->id);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($cat->id, ['name' => 'ExistingMat']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects missing name on update', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($cat->id, ['name' => '']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects invalid status on update', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($cat->id, ['status' => 9]))
            ->assertSessionHasErrors(['status']);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates raw material and redirects', function () {
        $cat     = mkRmCategory();
        $rm      = mkRm($cat->id);
        $newName = 'UpdatedMat-' . uniqid();

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($cat->id, ['name' => $newName, 'status' => 0]))
            ->assertRedirect(route('raw-material.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_materials', ['id' => $rm->id, 'name' => $newName, 'status' => 0]);
    });

    it('updates category assignment on update', function () {
        $catA = mkRmCategory();
        $catB = mkRmCategory();
        $rm   = mkRm($catA->id);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->put(route('raw-material.update', $rm), rmPayload($catB->id))
            ->assertRedirect();

        assertDatabaseHas('raw_materials', ['id' => $rm->id, 'raw_material_category_id' => $catB->id]);
    });
});

// ─────────────────────────────────────────────

describe('toggleStatus', function () {
    it('toggles active to inactive', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id, ['status' => 1]);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->patch(route('raw-material.toggleStatus', $rm))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_materials', ['id' => $rm->id, 'status' => 0]);
    });

    it('toggles inactive to active', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id, ['status' => 0]);

        actingAs(rmActor(['edit-raw-material-inventory']))
            ->patch(route('raw-material.toggleStatus', $rm))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_materials', ['id' => $rm->id, 'status' => 1]);
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes material with no order items and redirects', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);

        actingAs(rmActor(['delete-raw-material-inventory']))
            ->delete(route('raw-material.destroy', $rm))
            ->assertRedirect(route('raw-material.index'))
            ->assertSessionHas('success');

        assertSoftDeleted('raw_materials', ['id' => $rm->id]);
    });

    it('blocks delete when order items exist for this material', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        rmAttachOrderItem($rm);

        actingAs(rmActor(['delete-raw-material-inventory']))
            ->delete(route('raw-material.destroy', $rm))
            ->assertRedirect(route('raw-material.index'))
            ->assertSessionHas('error');

        assertDatabaseHas('raw_materials', ['id' => $rm->id, 'deleted_at' => null]);
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('returns 403 without export-raw-material-inventory permission', function () {
        actingAs(rmActor())
            ->get(route('raw-material.export'))
            ->assertForbidden();
    });

    it('returns excel download with permission', function () {
        $cat = mkRmCategory();
        mkRm($cat->id);
        actingAs(rmActor(['export-raw-material-inventory']))
            ->get(route('raw-material.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('statusBadge returns active badge for status=1', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id, ['status' => 1]);
        expect($rm->statusBadge())->toContain('bg-success');
    });

    it('statusBadge returns inactive badge for status=0', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id, ['status' => 0]);
        expect($rm->statusBadge())->toContain('bg-danger');
    });

    it('belongs to category', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        expect($rm->category->id)->toBe($cat->id);
    });

    it('has many order items', function () {
        $cat = mkRmCategory();
        $rm  = mkRm($cat->id);
        rmAttachOrderItem($rm);
        expect($rm->orderItems()->count())->toBe(1);
    });
});
