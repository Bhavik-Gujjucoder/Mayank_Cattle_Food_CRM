<?php

use App\Models\BrandManagement;
use App\Models\Product;
use App\Models\User;

// ─────────────────────────────────────────────
//  Helpers
// ─────────────────────────────────────────────

function prodActor(array $perms = []): User
{
    $user = User::factory()->create(['status' => 1]);
    $user->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
    if (! empty($perms)) {
        grantPermissions($user, $perms);
    }
    return $user;
}

function mkProdBrand(array $attrs = []): BrandManagement
{
    return BrandManagement::create(array_merge(['name' => 'PBrand-' . uniqid(), 'status' => 1], $attrs));
}

function mkProd(int $brandId, array $attrs = []): Product
{
    return Product::create(array_merge([
        'name'     => 'Product-' . uniqid(),
        'brand_id' => $brandId,
        'unit'     => 'Bag',
        'price'    => 100.00,
        'status'   => 1,
    ], $attrs));
}

function prodPayload(int $brandId, array $overrides = []): array
{
    return array_merge([
        'name'     => 'NewProduct-' . uniqid(),
        'brand_id' => $brandId,
        'unit'     => 'Bag',
        'price'    => 150.00,
        'status'   => 1,
    ], $overrides);
}

// ─────────────────────────────────────────────

beforeEach(function () {
    foreach (['super admin', 'admin', 'broker', 'dealer'] as $r) {
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
    }
});

// ─────────────────────────────────────────────

describe('access-control', function () {
    it('redirects unauthenticated user from index', function () {
        $this->get(route('product.index'))->assertRedirect(route('login'));
    });

    it('redirects unauthenticated user from store', function () {
        $this->post(route('product.store'), [])->assertRedirect(route('login'));
    });

    it('returns 403 on store without add-product permission', function () {
        $this->actingAs(prodActor())
            ->post(route('product.store'), prodPayload(mkProdBrand()->id))
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-product permission', function () {
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);
        $this->actingAs(prodActor())
            ->delete(route('product.destroy', $product))
            ->assertForbidden();
    });

    it('returns 403 on update without edit-product permission', function () {
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);
        $this->actingAs(prodActor())
            ->put(route('product.update', $product), prodPayload($brand->id))
            ->assertForbidden();
    });

    it('returns 200 on index for authenticated user', function () {
        $this->actingAs(prodActor())->get(route('product.index'))->assertOk();
    });
});

// ─────────────────────────────────────────────

describe('index', function () {
    it('renders product index view with brands', function () {
        $this->actingAs(prodActor())
            ->get(route('product.index'))
            ->assertOk()
            ->assertViewIs('product.index')
            ->assertViewHas('brands');
    });

    it('returns DataTables JSON on AJAX request', function () {
        mkProd(mkProdBrand()->id);
        $this->actingAs(prodActor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('product.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('filters by brand_id in AJAX response', function () {
        $brand1 = mkProdBrand();
        $brand2 = mkProdBrand();
        mkProd($brand1->id);
        mkProd($brand2->id);

        $response = $this->actingAs(prodActor())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('product.index') . "?brand_id={$brand1->id}")
            ->assertOk();

        expect($response->json('recordsFiltered'))->toBe(1);
    });
});

// ─────────────────────────────────────────────

describe('store-validation', function () {
    it('rejects empty name', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['name' => '']))
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects duplicate name (ignoring soft-deleted)', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        mkProd($brand->id, ['name' => 'UniqueProduct']);

        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['name' => 'UniqueProduct']))
            ->assertJsonValidationErrors(['name']);
    });

    it('allows reusing soft-deleted product name', function () {
        $actor   = prodActor(['add-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id, ['name' => 'DeletedProd']);
        $product->delete();

        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['name' => 'DeletedProd']))
            ->assertJsonMissing(['errors' => ['name']]);
    });

    it('rejects invalid brand_id', function () {
        $actor = prodActor(['add-product']);
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload(99999))
            ->assertJsonValidationErrors(['brand_id']);
    });

    it('rejects inactive brand_id', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand(['status' => 0]);
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id))
            ->assertJsonValidationErrors(['brand_id']);
    });

    it('rejects invalid unit', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['unit' => 'InvalidUnit']))
            ->assertJsonValidationErrors(['unit']);
    });

    it('rejects negative price', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['price' => -1]))
            ->assertJsonValidationErrors(['price']);
    });

    it('rejects invalid status', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['status' => 5]))
            ->assertJsonValidationErrors(['status']);
    });
});

// ─────────────────────────────────────────────

describe('store-persistence', function () {
    it('creates product and returns JSON success', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $name  = 'NewP-' . uniqid();

        $response = $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['name' => $name]))
            ->assertOk();

        expect($response->json('success'))->toBeTrue();
        $this->assertDatabaseHas('products', ['name' => $name, 'brand_id' => $brand->id]);
    });

    it('stores all unit values: Bag, Ton, KG', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();

        foreach (['Bag', 'Ton', 'KG'] as $unit) {
            $name = 'Unit-' . $unit . '-' . uniqid();
            $this->actingAs($actor)
                ->postJson(route('product.store'), prodPayload($brand->id, ['name' => $name, 'unit' => $unit]))
                ->assertOk()
                ->assertJsonFragment(['success' => true]);
            $this->assertDatabaseHas('products', ['name' => $name, 'unit' => $unit]);
        }
    });

    it('allows null price', function () {
        $actor = prodActor(['add-product']);
        $brand = mkProdBrand();
        $name  = 'NullPrice-' . uniqid();

        $this->actingAs($actor)
            ->postJson(route('product.store'), prodPayload($brand->id, ['name' => $name, 'price' => null]))
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('products', ['name' => $name]);
    });
});

// ─────────────────────────────────────────────

describe('edit', function () {
    it('returns product JSON', function () {
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);

        $this->actingAs(prodActor())
            ->getJson(route('product.edit', $product))
            ->assertOk()
            ->assertJsonFragment(['id' => $product->id, 'name' => $product->name]);
    });

    it('returns 404 for soft-deleted product', function () {
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);
        $product->delete();

        $this->actingAs(prodActor())
            ->getJson(route('product.edit', $product->id))
            ->assertNotFound();
    });
});

// ─────────────────────────────────────────────

describe('update-validation', function () {
    it('rejects missing name on update', function () {
        $actor   = prodActor(['edit-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);

        $this->actingAs($actor)
            ->putJson(route('product.update', $product), prodPayload($brand->id, ['name' => '']))
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects duplicate name on update (excluding self)', function () {
        $actor   = prodActor(['edit-product']);
        $brand   = mkProdBrand();
        $other   = mkProd($brand->id, ['name' => 'OtherProd']);
        $product = mkProd($brand->id);

        $this->actingAs($actor)
            ->putJson(route('product.update', $product), prodPayload($brand->id, ['name' => 'OtherProd']))
            ->assertJsonValidationErrors(['name']);
    });

    it('allows keeping same name on update', function () {
        $actor   = prodActor(['edit-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id, ['name' => 'SameName']);

        $this->actingAs($actor)
            ->putJson(route('product.update', $product), prodPayload($brand->id, ['name' => 'SameName']))
            ->assertJsonMissing(['errors' => ['name']]);
    });
});

// ─────────────────────────────────────────────

describe('update-persistence', function () {
    it('updates product and returns JSON success', function () {
        $actor   = prodActor(['edit-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);
        $newName = 'Updated-' . uniqid();

        $response = $this->actingAs($actor)
            ->putJson(route('product.update', $product), prodPayload($brand->id, ['name' => $newName, 'status' => 0]))
            ->assertOk();

        expect($response->json('success'))->toBeTrue();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $newName, 'status' => 0]);
    });
});

// ─────────────────────────────────────────────

describe('destroy', function () {
    it('soft-deletes the product and redirects', function () {
        $actor   = prodActor(['delete-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);

        $this->actingAs($actor)
            ->delete(route('product.destroy', $product))
            ->assertRedirect(route('product.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    });

    it('returns 404 for nonexistent product', function () {
        $actor = prodActor(['delete-product']);
        $this->actingAs($actor)
            ->delete(route('product.destroy', 99999))
            ->assertNotFound();
    });

    it('soft-deleted product does not appear in default DataTables query', function () {
        $actor   = prodActor(['delete-product']);
        $brand   = mkProdBrand();
        $product = mkProd($brand->id, ['name' => 'ToBeDeleted']);
        $product->delete();

        $response = $this->actingAs($actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson(route('product.index'));
        $names    = collect($response->json('data'))->pluck('name');

        expect($names->contains('ToBeDeleted'))->toBeFalse();
    });
});

// ─────────────────────────────────────────────

describe('bulkDelete', function () {
    it('returns 400 when ids array is empty', function () {
        $actor = prodActor(['delete-product']);
        $this->actingAs($actor)
            ->postJson(route('product.bulkDelete'), ['ids' => []])
            ->assertStatus(400);
    });

    it('soft-deletes all given products', function () {
        $actor = prodActor(['delete-product']);
        $brand = mkProdBrand();
        $p1    = mkProd($brand->id);
        $p2    = mkProd($brand->id);

        $this->actingAs($actor)
            ->postJson(route('product.bulkDelete'), ['ids' => [$p1->id, $p2->id]])
            ->assertOk();

        $this->assertSoftDeleted('products', ['id' => $p1->id]);
        $this->assertSoftDeleted('products', ['id' => $p2->id]);
    });

    it('does not affect products not in ids list', function () {
        $actor = prodActor(['delete-product']);
        $brand = mkProdBrand();
        $keep  = mkProd($brand->id);
        $del   = mkProd($brand->id);

        $this->actingAs($actor)
            ->postJson(route('product.bulkDelete'), ['ids' => [$del->id]])
            ->assertOk();

        $this->assertDatabaseHas('products', ['id' => $keep->id, 'deleted_at' => null]);
    });
});

// ─────────────────────────────────────────────

describe('model-methods', function () {
    it('statusBadge returns active badge for status=1', function () {
        $product = mkProd(mkProdBrand()->id, ['status' => 1]);
        expect($product->statusBadge())->toContain('bg-success');
    });

    it('statusBadge returns inactive badge for status=0', function () {
        $product = mkProd(mkProdBrand()->id, ['status' => 0]);
        expect($product->statusBadge())->toContain('bg-danger');
    });

    it('belongs to brand', function () {
        $brand   = mkProdBrand();
        $product = mkProd($brand->id);
        expect($product->brand->id)->toBe($brand->id);
    });
});
