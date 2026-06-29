<?php

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
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

function rmcActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }

    return $user;
}

function mkRmCat(array $attrs = []): RawMaterialCategory
{
    return RawMaterialCategory::create(array_merge([
        'category_unique_id' => 'CAT-' . uniqid(),
        'name'               => 'Category-' . uniqid(),
        'status'             => 1,
    ], $attrs));
}

function mkRmMat(RawMaterialCategory $cat, array $attrs = []): RawMaterial
{
    return RawMaterial::create(array_merge([
        'raw_material_unique_id'   => 'RM-' . uniqid(),
        'raw_material_category_id' => $cat->id,
        'name'                     => 'Mat-' . uniqid(),
        'unit'                     => 'Ton',
        'status'                   => 1,
    ], $attrs));
}

function rmcPayload(array $overrides = []): array
{
    return array_merge([
        'name'   => 'NewCategory-' . uniqid(),
        'status' => 1,
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
        get(route('raw-material.category.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from create', function () {
        get(route('raw-material.category.create'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        post(route('raw-material.category.store'), rmcPayload())->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from show', function () {
        $cat = mkRmCat();
        get(route('raw-material.category.show', $cat))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from edit', function () {
        $cat = mkRmCat();
        get(route('raw-material.category.edit', $cat))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from destroy', function () {
        $cat = mkRmCat();
        delete(route('raw-material.category.destroy', $cat))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from toggleStatus', function () {
        $cat = mkRmCat();
        patch(route('raw-material.category.toggleStatus', $cat))->assertRedirect(route('login'));
    });

    it('returns 403 on index without view-raw-material-category', function () {
        actingAs(rmcActor())
            ->get(route('raw-material.category.index'))
            ->assertForbidden();
    });

    it('returns 200 on index with view-raw-material-category', function () {
        actingAs(rmcActor(['view-raw-material-category']))
            ->get(route('raw-material.category.index'))
            ->assertOk();
    });

    it('returns 403 on create without add-raw-material-category', function () {
        actingAs(rmcActor(['view-raw-material-category']))
            ->get(route('raw-material.category.create'))
            ->assertForbidden();
    });

    it('returns 403 on store without add-raw-material-category', function () {
        actingAs(rmcActor())
            ->post(route('raw-material.category.store'), rmcPayload())
            ->assertForbidden();
    });

    it('returns 403 on show without view-raw-material-category', function () {
        $cat = mkRmCat();
        actingAs(rmcActor())
            ->get(route('raw-material.category.show', $cat))
            ->assertForbidden();
    });

    it('returns 403 on edit without edit-raw-material-category', function () {
        $cat = mkRmCat();
        actingAs(rmcActor(['view-raw-material-category']))
            ->get(route('raw-material.category.edit', $cat))
            ->assertForbidden();
    });

    it('returns 403 on update without edit-raw-material-category', function () {
        $cat = mkRmCat();
        actingAs(rmcActor(['view-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload())
            ->assertForbidden();
    });

    it('returns 403 on toggleStatus without edit-raw-material-category', function () {
        $cat = mkRmCat();
        actingAs(rmcActor(['view-raw-material-category']))
            ->patch(route('raw-material.category.toggleStatus', $cat))
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-raw-material-category', function () {
        $cat = mkRmCat();
        actingAs(rmcActor())
            ->delete(route('raw-material.category.destroy', $cat))
            ->assertForbidden();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('renders category index view', function () {
        actingAs(rmcActor(['view-raw-material-category']))
            ->get(route('raw-material.category.index'))
            ->assertOk()
            ->assertViewIs('raw_material_category.index')
            ->assertViewHas('page_title');
    });

    it('returns DataTables JSON on AJAX request', function () {
        mkRmCat();
        actingAs(rmcActor(['view-raw-material-category']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.category.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('filters categories by status in AJAX response', function () {
        mkRmCat(['name' => 'ActiveCat', 'status' => 1]);
        mkRmCat(['name' => 'InactiveCat', 'status' => 0]);

        $response = actingAs(rmcActor(['view-raw-material-category']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.category.index', ['status' => 0]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });

    it('filters categories by search term in AJAX response', function () {
        mkRmCat(['name' => 'AlphaSearchCat', 'category_unique_id' => 'CAT-ALPHA-001']);
        mkRmCat(['name' => 'BetaOtherCat', 'category_unique_id' => 'CAT-BETA-002']);

        $response = actingAs(rmcActor(['view-raw-material-category']))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('raw-material.category.index', ['search' => ['value' => 'AlphaSearch']]))
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('create', function () {
    it('renders create view with permissions', function () {
        actingAs(rmcActor(['add-raw-material-category']))
            ->get(route('raw-material.category.create'))
            ->assertOk()
            ->assertViewIs('raw_material_category.create')
            ->assertViewHas('category_unique_id');
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('rejects missing name', function () {
        actingAs(rmcActor(['add-raw-material-category']))
            ->post(route('raw-material.category.store'), rmcPayload(['name' => '']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects duplicate name', function () {
        mkRmCat(['name' => 'UniqueCat']);
        actingAs(rmcActor(['add-raw-material-category']))
            ->post(route('raw-material.category.store'), rmcPayload(['name' => 'UniqueCat']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects invalid status', function () {
        actingAs(rmcActor(['add-raw-material-category']))
            ->post(route('raw-material.category.store'), rmcPayload(['status' => 5]))
            ->assertSessionHasErrors(['status']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates category and redirects with success', function () {
        $actor = rmcActor(['add-raw-material-category']);
        $name  = 'CatNew-' . uniqid();

        actingAs($actor)
            ->post(route('raw-material.category.store'), rmcPayload(['name' => $name]))
            ->assertRedirect(route('raw-material.category.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_categories', ['name' => $name]);
    });

    it('auto-generates category_unique_id on create', function () {
        $name = 'CatAutoId-' . uniqid();

        actingAs(rmcActor(['add-raw-material-category']))
            ->post(route('raw-material.category.store'), rmcPayload(['name' => $name]))
            ->assertRedirect();

        $category = RawMaterialCategory::where('name', $name)->first();
        expect($category)->not->toBeNull()
            ->and($category->category_unique_id)->not->toBeEmpty();
    });

    it('trims whitespace from name', function () {
        $name = 'TrimTest-' . uniqid();

        actingAs(rmcActor(['add-raw-material-category']))
            ->post(route('raw-material.category.store'), rmcPayload(['name' => "  {$name}  "]))
            ->assertRedirect();

        assertDatabaseHas('raw_material_categories', ['name' => $name]);
    });
});

// ─────────────────────────────────────────────

describe('show', function () {
    it('renders show view with category and its materials', function () {
        $actor = rmcActor(['view-raw-material-category']);
        $cat   = mkRmCat();

        actingAs($actor)
            ->get(route('raw-material.category.show', $cat))
            ->assertOk()
            ->assertViewIs('raw_material_category.show')
            ->assertViewHas('category')
            ->assertViewHas('materials');
    });

    it('passes category materials to the show view', function () {
        $cat = mkRmCat();
        $mat = mkRmMat($cat, ['name' => 'VisibleMaterial']);

        $response = actingAs(rmcActor(['view-raw-material-category']))
            ->get(route('raw-material.category.show', $cat));

        expect($response->viewData('materials'))->toHaveCount(1)
            ->and($response->viewData('materials')->first()->id)->toBe($mat->id);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('renders edit view', function () {
        $actor = rmcActor(['edit-raw-material-category']);
        $cat   = mkRmCat();

        actingAs($actor)
            ->get(route('raw-material.category.edit', $cat))
            ->assertOk()
            ->assertViewIs('raw_material_category.edit')
            ->assertViewHas('category');
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('rejects empty name on update', function () {
        $cat = mkRmCat();

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['name' => '']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects duplicate name (other category)', function () {
        mkRmCat(['name' => 'OtherCat']);
        $cat = mkRmCat();

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['name' => 'OtherCat']))
            ->assertSessionHasErrors(['name']);
    });

    it('rejects invalid status on update', function () {
        $cat = mkRmCat();

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['status' => 9]))
            ->assertSessionHasErrors(['status']);
    });

    it('allows keeping same name on update', function () {
        $cat = mkRmCat(['name' => 'SameCatName']);

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['name' => 'SameCatName']))
            ->assertSessionDoesntHaveErrors(['name'])
            ->assertRedirect(route('raw-material.category.index'));
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates category and redirects', function () {
        $cat     = mkRmCat();
        $newName = 'Updated-' . uniqid();

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['name' => $newName, 'status' => 0]))
            ->assertRedirect(route('raw-material.category.index'))
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_categories', ['id' => $cat->id, 'name' => $newName, 'status' => 0]);
    });

    it('trims whitespace from name on update', function () {
        $cat     = mkRmCat();
        $newName = 'UpdatedTrim-' . uniqid();

        actingAs(rmcActor(['edit-raw-material-category']))
            ->put(route('raw-material.category.update', $cat), rmcPayload(['name' => "  {$newName}  "]))
            ->assertRedirect();

        assertDatabaseHas('raw_material_categories', ['id' => $cat->id, 'name' => $newName]);
    });
});

// ─────────────────────────────────────────────

describe('toggleStatus', function () {
    it('toggles status from active to inactive', function () {
        $cat = mkRmCat(['status' => 1]);

        actingAs(rmcActor(['edit-raw-material-category']))
            ->patch(route('raw-material.category.toggleStatus', $cat))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_categories', ['id' => $cat->id, 'status' => 0]);
    });

    it('toggles status from inactive to active', function () {
        $cat = mkRmCat(['status' => 0]);

        actingAs(rmcActor(['edit-raw-material-category']))
            ->patch(route('raw-material.category.toggleStatus', $cat))
            ->assertRedirect()
            ->assertSessionHas('success');

        assertDatabaseHas('raw_material_categories', ['id' => $cat->id, 'status' => 1]);
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes category with no materials and redirects', function () {
        $cat = mkRmCat();

        actingAs(rmcActor(['delete-raw-material-category']))
            ->delete(route('raw-material.category.destroy', $cat))
            ->assertRedirect(route('raw-material.category.index'))
            ->assertSessionHas('success');

        assertSoftDeleted('raw_material_categories', ['id' => $cat->id]);
    });

    it('blocks delete when materials exist under category', function () {
        $cat = mkRmCat();
        mkRmMat($cat);

        actingAs(rmcActor(['delete-raw-material-category']))
            ->delete(route('raw-material.category.destroy', $cat))
            ->assertRedirect(route('raw-material.category.index'))
            ->assertSessionHas('error');

        assertDatabaseHas('raw_material_categories', ['id' => $cat->id, 'deleted_at' => null]);
    });
});

// ─────────────────────────────────────────────

describe('export', function () {
    it('returns 403 without export-raw-material-category permission', function () {
        actingAs(rmcActor())
            ->get(route('raw-material.category.export'))
            ->assertForbidden();
    });

    it('returns excel download with permission', function () {
        mkRmCat();
        actingAs(rmcActor(['export-raw-material-category']))
            ->get(route('raw-material.category.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('statusBadge returns active badge', function () {
        $cat = mkRmCat(['status' => 1]);
        expect($cat->statusBadge())->toContain('bg-success');
    });

    it('statusBadge returns inactive badge', function () {
        $cat = mkRmCat(['status' => 0]);
        expect($cat->statusBadge())->toContain('bg-danger');
    });

    it('has many materials', function () {
        $cat = mkRmCat();
        mkRmMat($cat);
        expect($cat->materials()->count())->toBe(1);
    });
});
