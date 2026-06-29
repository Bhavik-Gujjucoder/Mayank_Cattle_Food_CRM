<?php

use App\Models\BrandManagement;
use App\Models\User;

/* =========================================================================
 * Helpers
 * ========================================================================= */

/**
 * Create an authenticated user and optionally grant permissions.
 */
function brandActor(array $permissions = []): User
{
    $user = User::factory()->create();

    if ($permissions) {
        grantPermissions($user, $permissions);
    }

    return $user;
}

/**
 * Create a BrandManagement record with sensible defaults.
 */
function mkBrand(array $attributes = []): BrandManagement
{
    return BrandManagement::create(array_merge([
        'name'   => 'Test Brand ' . uniqid(),
        'status' => 1,
    ], $attributes));
}

/* =========================================================================
 * Access Control
 * ========================================================================= */

describe('access-control', function () {

    it('redirects guests away from brand index', function () {
        $this->get(route('brand.index'))
            ->assertRedirect(route('login'));
    });

    it('returns 403 on index without view-brand permission', function () {
        $user = brandActor();
        $this->actingAs($user)
            ->get(route('brand.index'))
            ->assertForbidden();
    });

    it('returns 403 on quickCreateForm without add-brand permission', function () {
        $user = brandActor();
        $this->actingAs($user)
            ->get(route('brand.quickCreateForm'))
            ->assertForbidden();
    });

    it('returns 403 on store without add-brand permission', function () {
        $user = brandActor();
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'SomeBrand'])
            ->assertForbidden();
    });

    it('returns 403 on update without edit-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor();
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'NewName', 'status' => '1'])
            ->assertForbidden();
    });

    it('returns 403 on destroy without delete-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor();
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand))
            ->assertForbidden();
    });

    it('returns 403 on bulkDelete without delete-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor();
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$brand->id]])
            ->assertForbidden();
    });

    it('allows index with view-brand permission', function () {
        $user = brandActor(['view-brand']);
        $this->actingAs($user)
            ->get(route('brand.index'))
            ->assertOk();
    });

    it('allows quickCreateForm with add-brand permission', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->get(route('brand.quickCreateForm'))
            ->assertOk();
    });

    it('allows store with add-brand permission', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'Permitted Brand'])
            ->assertOk();
    });

    it('allows update with edit-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'Updated Name', 'status' => '1'])
            ->assertOk();
    });

    it('allows destroy with delete-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand))
            ->assertRedirect(route('brand.index'));
    });

    it('allows bulkDelete with delete-brand permission', function () {
        $brand = mkBrand();
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$brand->id]])
            ->assertOk();
    });
});

/* =========================================================================
 * Index
 * ========================================================================= */

describe('index', function () {

    it('returns the brand index view for regular requests', function () {
        $user = brandActor(['view-brand']);
        $this->actingAs($user)
            ->get(route('brand.index'))
            ->assertOk()
            ->assertViewIs('brand.index');
    });

    it('returns DataTables JSON for AJAX requests', function () {
        $user = brandActor(['view-brand']);
        $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
    });

    it('includes brand rows in DataTables response', function () {
        $brand = mkBrand(['name' => 'AjaxBrand']);
        $user  = brandActor(['view-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('AjaxBrand');
    });

    it('shows edit-btn in action column when user has edit-brand permission', function () {
        mkBrand(['name' => 'EditableBrand']);
        $user = brandActor(['view-brand', 'edit-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action')->implode('');
        expect($actions)->toContain('edit-btn');
    });

    it('hides edit-btn in action column when user lacks edit-brand permission', function () {
        mkBrand(['name' => 'NonEditableBrand']);
        $user = brandActor(['view-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action')->implode('');
        expect($actions)->not->toContain('edit-btn');
    });

    it('shows deleteBrand in action column when user has delete-brand permission', function () {
        mkBrand(['name' => 'DeletableBrand']);
        $user = brandActor(['view-brand', 'delete-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action')->implode('');
        expect($actions)->toContain('deleteBrand');
    });

    it('hides deleteBrand in action column when user lacks delete-brand permission', function () {
        mkBrand(['name' => 'NonDeletableBrand']);
        $user = brandActor(['view-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $actions = collect($response->json('data'))->pluck('action')->implode('');
        expect($actions)->not->toContain('deleteBrand');
    });
});

/* =========================================================================
 * quickCreateForm
 * ========================================================================= */

describe('quickCreateForm', function () {

    it('returns the quick-create-form partial view', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->get(route('brand.quickCreateForm'))
            ->assertOk()
            ->assertViewIs('brand.partials.quick-create-form');
    });
});

/* =========================================================================
 * Store — Validation
 * ========================================================================= */

describe('store-validation', function () {

    it('fails when name is missing', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('returns custom message when name is missing', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), [])
            ->assertUnprocessable()
            ->assertJsonFragment(['name' => ['The brand name field is required.']]);
    });

    it('fails when name exceeds 255 characters', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => str_repeat('A', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when name already exists (unique rule)', function () {
        mkBrand(['name' => 'DuplicateBrand']);
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'DuplicateBrand'])
            ->assertUnprocessable()
            ->assertJsonFragment(['name' => ['This brand name already exists.']]);
    });

    it('fails when status is an invalid value', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'ValidName', 'status' => '5'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('accepts status 0 as valid', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'InactiveBrand', 'status' => '0'])
            ->assertOk();
    });

    it('accepts status 1 as valid', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'ActiveBrand', 'status' => '1'])
            ->assertOk();
    });

    it('accepts missing status (nullable)', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'NoStatusBrand'])
            ->assertOk();
    });
});

/* =========================================================================
 * Store — Persistence
 * ========================================================================= */

describe('store-persistence', function () {

    it('creates a brand record in the database', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'PersistBrand', 'status' => '1'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['name' => 'PersistBrand', 'status' => 1]);
    });

    it('trims whitespace from the name on create', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => '  TrimmedBrand  ', 'status' => '1'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['name' => 'TrimmedBrand']);
        $this->assertDatabaseMissing('brand_management', ['name' => '  TrimmedBrand  ']);
    });

    it('defaults status to 1 when not provided', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'DefaultStatusBrand'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['name' => 'DefaultStatusBrand', 'status' => 1]);
    });

    it('stores status 0 when explicitly provided', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'InactiveStoreBrand', 'status' => '0'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['name' => 'InactiveStoreBrand', 'status' => 0]);
    });

    it('returns success JSON with brand data on create', function () {
        $user = brandActor(['add-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'JsonResponseBrand', 'status' => '1'])
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'brand' => ['id', 'name', 'status']])
            ->assertJsonFragment([
                'success' => true,
                'name'    => 'JsonResponseBrand',
                'status'  => 1,
            ]);
    });

    it('returns integer status in JSON response', function () {
        $user = brandActor(['add-brand']);
        $response = $this->actingAs($user)
            ->postJson(route('brand.store'), ['name' => 'IntStatusBrand', 'status' => '0'])
            ->assertOk();

        expect($response->json('brand.status'))->toBe(0);
    });
});

/* =========================================================================
 * Edit
 * ========================================================================= */

describe('edit', function () {

    it('returns brand JSON for an existing brand', function () {
        $brand = mkBrand(['name' => 'EditableBrandJson', 'status' => 1]);
        $user  = brandActor(['view-brand']);

        $this->actingAs($user)
            ->getJson(route('brand.edit', $brand))
            ->assertOk()
            ->assertJsonFragment([
                'id'   => $brand->id,
                'name' => 'EditableBrandJson',
            ]);
    });

    it('returns 404 for a non-existent brand', function () {
        $user = brandActor(['view-brand']);
        $this->actingAs($user)
            ->getJson(route('brand.edit', 99999))
            ->assertNotFound();
    });

    it('returns brand status in JSON response', function () {
        $brand = mkBrand(['name' => 'StatusBrandEdit', 'status' => 0]);
        $user  = brandActor(['view-brand']);

        $response = $this->actingAs($user)
            ->getJson(route('brand.edit', $brand))
            ->assertOk();

        expect($response->json('status'))->toBe(0);
    });
});

/* =========================================================================
 * Update — Validation
 * ========================================================================= */

describe('update-validation', function () {

    it('fails when name is missing on update', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['status' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('returns custom required message on update', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['status' => '1'])
            ->assertUnprocessable()
            ->assertJsonFragment(['name' => ['The brand name field is required.']]);
    });

    it('fails when name is duplicate of another brand on update', function () {
        $existing = mkBrand(['name' => 'ExistingUnique']);
        $brand    = mkBrand(['name' => 'OtherBrand']);
        $user     = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'ExistingUnique', 'status' => '1'])
            ->assertUnprocessable()
            ->assertJsonFragment(['name' => ['This brand name already exists.']]);
    });

    it('allows keeping the same name on update (ignores own id in unique)', function () {
        $brand = mkBrand(['name' => 'SameNameBrand']);
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'SameNameBrand', 'status' => '1'])
            ->assertOk();
    });

    it('fails when status is missing on update (required)', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'ValidName'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('fails when status is invalid on update', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'ValidName', 'status' => '9'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('fails when name exceeds 255 characters on update', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => str_repeat('X', 256), 'status' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

/* =========================================================================
 * Update — Persistence
 * ========================================================================= */

describe('update-persistence', function () {

    it('updates the brand name in the database', function () {
        $brand = mkBrand(['name' => 'OriginalName']);
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'UpdatedName', 'status' => '1'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['id' => $brand->id, 'name' => 'UpdatedName']);
        $this->assertDatabaseMissing('brand_management', ['id' => $brand->id, 'name' => 'OriginalName']);
    });

    it('trims whitespace from name on update', function () {
        $brand = mkBrand(['name' => 'UntrimmedBrand']);
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => '  TrimmedUpdate  ', 'status' => '1'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['id' => $brand->id, 'name' => 'TrimmedUpdate']);
    });

    it('updates the brand status in the database', function () {
        $brand = mkBrand(['status' => 1]);
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => $brand->name, 'status' => '0'])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['id' => $brand->id, 'status' => 0]);
    });

    it('returns success JSON on update', function () {
        $brand = mkBrand();
        $user  = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', $brand), ['name' => 'UpdateJsonBrand', 'status' => '1'])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Brand updated successfully.']);
    });

    it('returns 404 when updating a non-existent brand', function () {
        $user = brandActor(['edit-brand']);
        $this->actingAs($user)
            ->putJson(route('brand.update', 99999), ['name' => 'GhostBrand', 'status' => '1'])
            ->assertNotFound();
    });
});

/* =========================================================================
 * Destroy
 * ========================================================================= */

describe('destroy', function () {

    it('hard-deletes the brand from the database', function () {
        $brand = mkBrand(['name' => 'ToBeDeleted']);
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand))
            ->assertRedirect(route('brand.index'));

        $this->assertDatabaseMissing('brand_management', ['id' => $brand->id]);
    });

    it('redirects to brand index after destroy', function () {
        $brand = mkBrand();
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand))
            ->assertRedirect(route('brand.index'));
    });

    it('flashes a success message after destroy', function () {
        $brand = mkBrand();
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand))
            ->assertSessionHas('success', 'Brand deleted successfully.');
    });

    it('returns 404 when destroying a non-existent brand', function () {
        $user = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', 99999))
            ->assertNotFound();
    });

    it('does not leave a soft-delete record (no deleted_at column)', function () {
        $brand   = mkBrand(['name' => 'HardDeleteBrand']);
        $brandId = $brand->id;
        $user    = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->delete(route('brand.destroy', $brand));

        // BrandManagement has no SoftDeletes — record is gone entirely
        $this->assertDatabaseMissing('brand_management', ['id' => $brandId]);
        expect(BrandManagement::find($brandId))->toBeNull();
    });
});

/* =========================================================================
 * Bulk Delete
 * ========================================================================= */

describe('bulkDelete', function () {

    it('returns 400 with message when ids are empty', function () {
        $user = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => []])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('returns 400 when ids key is missing', function () {
        $user = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), [])
            ->assertStatus(400)
            ->assertJson(['message' => 'No records selected.']);
    });

    it('hard-deletes selected brands', function () {
        $brand1 = mkBrand(['name' => 'BulkBrand1']);
        $brand2 = mkBrand(['name' => 'BulkBrand2']);
        $user   = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$brand1->id, $brand2->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected brands deleted successfully.']);

        $this->assertDatabaseMissing('brand_management', ['id' => $brand1->id]);
        $this->assertDatabaseMissing('brand_management', ['id' => $brand2->id]);
    });

    it('does not delete brands not in the ids list', function () {
        $target    = mkBrand(['name' => 'TargetBrand']);
        $untouched = mkBrand(['name' => 'UntouchedBrand']);
        $user      = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$target->id]])
            ->assertOk();

        $this->assertDatabaseHas('brand_management', ['id' => $untouched->id]);
    });

    it('returns 200 with success message when brands are deleted', function () {
        $brand = mkBrand();
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$brand->id]])
            ->assertOk()
            ->assertJson(['message' => 'Selected brands deleted successfully.']);
    });

    it('hard-deletes a single brand via bulk delete', function () {
        $brand = mkBrand(['name' => 'SingleBulkBrand']);
        $user  = brandActor(['delete-brand']);
        $this->actingAs($user)
            ->postJson(route('brand.bulkDelete'), ['ids' => [$brand->id]]);

        $this->assertDatabaseMissing('brand_management', ['id' => $brand->id]);
    });
});

/* =========================================================================
 * Model Methods
 * ========================================================================= */

describe('model-methods', function () {

    it('statusBadge returns bg-success badge for active brand', function () {
        $brand = mkBrand(['status' => 1]);
        expect($brand->statusBadge())
            ->toContain('bg-success')
            ->toContain('badge-pill');
    });

    it('statusBadge returns bg-danger badge for inactive brand', function () {
        $brand = mkBrand(['status' => 0]);
        expect($brand->statusBadge())
            ->toContain('bg-danger')
            ->toContain('badge-pill');
    });

    it('statusBadge contains Active text for active brand', function () {
        $brand = mkBrand(['status' => 1]);
        expect($brand->statusBadge())->toContain('Active');
    });

    it('statusBadge contains Inactive text for inactive brand', function () {
        $brand = mkBrand(['status' => 0]);
        expect($brand->statusBadge())->toContain('Inactive');
    });

    it('ordered scope returns brands ordered by id', function () {
        $b1 = mkBrand(['name' => 'Alpha']);
        $b2 = mkBrand(['name' => 'Beta']);
        $b3 = mkBrand(['name' => 'Gamma']);

        $ids = BrandManagement::ordered()->pluck('id')->toArray();

        expect(array_search($b1->id, $ids))
            ->toBeLessThan(array_search($b2->id, $ids));
        expect(array_search($b2->id, $ids))
            ->toBeLessThan(array_search($b3->id, $ids));
    });

    it('activeForDropdown returns only active brands', function () {
        mkBrand(['name' => 'ActiveOne', 'status' => 1]);
        mkBrand(['name' => 'InactiveOne', 'status' => 0]);

        $result = BrandManagement::activeForDropdown();

        expect($result->pluck('name'))->toContain('ActiveOne');
        expect($result->pluck('name'))->not->toContain('InactiveOne');
    });

    it('activeForDropdown returns a collection', function () {
        mkBrand(['status' => 1]);
        $result = BrandManagement::activeForDropdown();
        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('activeForDropdown returns empty collection when no active brands exist', function () {
        mkBrand(['status' => 0]);
        $result = BrandManagement::activeForDropdown();
        expect($result)->toBeEmpty();
    });

    it('isActive returns true for an active brand', function () {
        $brand = mkBrand(['status' => 1]);
        expect(BrandManagement::isActive($brand->id))->toBeTrue();
    });

    it('isActive returns false for an inactive brand', function () {
        $brand = mkBrand(['status' => 0]);
        expect(BrandManagement::isActive($brand->id))->toBeFalse();
    });

    it('isActive returns false for a non-existent id', function () {
        expect(BrandManagement::isActive(99999))->toBeFalse();
    });
});
