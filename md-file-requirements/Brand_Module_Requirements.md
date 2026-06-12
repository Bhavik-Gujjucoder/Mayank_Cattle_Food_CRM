# Brand Module — Requirements & Implementation Reference
> Laravel 12 | Spatie Permissions | Users & Permissions submenu

---

## 📁 Module Overview

**Module Name:** Brand Management  
**Menu Location:** Users & Permissions → **Brand** (first submenu item)  
**Route prefix:** `/brand`  
**Permission type (DB `type` column):** `brand`

Brands are master data used across Sales and Product modules (orders, dealers, products, dispatch pending payments). This module provides CRUD for the `brand_management` table.

---

## 📅 Changelog — 01 Jun 2026 (Quick Add Brand from Soda/Order create)

### Summary
Users with `add-brand` can create a brand inline from **Add Soda/Order** without leaving the order form.

| Method | Route | Name | Permission |
|---|---|---|---|
| GET | `brand/quick-create-form` | `brand.quickCreateForm` | `add-brand` |
| POST | `brand` (JSON) | `brand.store` | `add-brand` |

- Route registered **before** `Route::resource('brand', …)`
- Partial: `resources/views/brand/partials/quick-create-form.blade.php`
- **“Add Brand” link** on order create — visible when broker is selected
- On success: `{ success, message, brand: { id, name, status } }`; new brand auto-selected in `#brand_id`
- See `Sales_Module_Requirements.md` for full UI flow

---

## 📅 Changelog — 01 Jun 2026 (Active brands only in dropdowns)

### Summary
All brand **dropdowns** show only **active** brands (`status = 1`). Inactive brands remain on the Brand Management list for admin maintenance.

### Implementation
- `BrandManagement::activeForDropdown()` — query `where('status', 1)->ordered()`
- `BrandManagement::isActive($id)` — validates active brand for filters and form posts
- `App\Support\ActiveDropdownValidation::brandId()` — used on Order, Dealer, Product forms
- `BrandManagementController@store` — `status` nullable; defaults to `1` when omitted (quick-add modal has no Status field)

### Used in
- Soda/Order create/edit/list filters (`SalesScope::filterableBrands()`)
- Dealer create/edit/list filters
- Product create/edit/list filters
- Dispatch Pending Payments brand filter

---

## 🗄️ Database Schema

### Table: `brand_management`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| name | VARCHAR(255) | — | Brand name (unique) |
| status | INTEGER | 1 | `1` = Active, `0` = Inactive |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |

**Migration:** `database/migrations/2026_05_12_110619_create_brand_management_table.php`

**Notes:**
- No soft deletes on this table (hard delete on destroy / bulk delete).
- List and dropdowns use **ascending order by `id`** (database storage order).

### Related tables (FK references)

| Table | Column | Usage |
|---|---|---|
| `order_management` | `brand_id` | Order header brand |
| `dealer_management` | `brand_id` | Dealer belongs to a brand |
| `products` | `brand_id` | Product belongs to a brand |

---

## 🔐 Permissions (Spatie)

| Permission | `type` (DB) | Used for |
|---|---|---|
| `view-brand` | `brand` | Brand list (`brand.index`); controller middleware on `index` |
| `add-brand` | `brand` | Add modal + `brand.store`; Quick Add Brand on Soda/Order create |
| `edit-brand` | `brand` | Edit action + `brand.update`; AJAX `brand.edit` |
| `delete-brand` | `brand` | Row delete + bulk delete; `brand.destroy`, `brand.bulkDelete` |

### Seeder: `BrandPermissionSeeder`

- Path: `database/seeders/BrandPermissionSeeder.php`
- Creates/updates permissions via `updateOrCreate` (idempotent)
- **Assigns all four permissions to `admin` and `super admin` roles**
- Registered in `DatabaseSeeder::run()`

**Run seeder:**

```bash
php artisan db:seed --class=BrandPermissionSeeder
```

**Super admin:** bypasses all permission checks via `Gate::before` in `AppServiceProvider`.

### Enforcement layers

| Layer | Implementation |
|---|---|
| **Controller** | `$this->middleware('permission:view-brand')->only(['index'])` |
| **Routes** | `add-brand` / `edit-brand` / `delete-brand` on store, update, destroy, bulkDelete |
| **Blade** | `@can` / `@canany` on Add button, action column, checkboxes, sidebar link |
| **DataTables** | Edit/Delete buttons gated with `auth()->user()->can('edit-brand')` / `can('delete-brand')` |

---

## 🛣️ Routes

Defined in `routes/web.php` (auth + verified middleware group):

| Method | URI | Route name | Middleware |
|---|---|---|---|
| GET | `/brand/quick-create-form` | `brand.quickCreateForm` | `permission:add-brand` |
| GET | `/brand` | `brand.index` | `view-brand` (controller) |
| POST | `/brand` | `brand.store` | `permission:add-brand` |
| GET | `/brand/{brand}/edit` | `brand.edit` | auth only (JSON for modal) |
| PUT/PATCH | `/brand/{brand}` | `brand.update` | `permission:edit-brand` |
| DELETE | `/brand/{brand}` | `brand.destroy` | `permission:delete-brand` |
| POST | `/brand/bulk-delete` | `brand.bulkDelete` | `permission:delete-brand` |

Resource routes `create` and `show` are registered but unused (modal-based CRUD, same pattern as State/City/Truck).

---

## 🖥️ UI — Brand Management List

**View:** `resources/views/brand/index.blade.php`  
**Page title:** Brand Management

### Table columns (order)

`Checkbox` (delete permission only) | `Sr no` | `Brand Name` | `Status` | `Action`

### Features

- Server-side **DataTables** with custom search box
- **Default sort:** ascending by `id`
- **Add Brand** button (top right) — `@can('add-brand')`
- **Add / Edit modal** — single form (`#brandForm`, `#brandModal`)
- **Row actions (⋮):** Edit | Delete — permission-gated
- **Bulk delete** — checkbox column + “Delete Selected” (`@can('delete-brand')`)
- **Delete confirmation** — SweetAlert2 (`confirmDeletion`)

### Modal fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Brand Name | Text (`name`) | Yes | Unique in `brand_management` |
| Status | Radio | Yes | Active (`1`, default) / Inactive (`0`) |

### Validation (store / update)

**Brand Management modal (index page):**
- `name` → required, string, max 255, unique on `brand_management.name`
- `status` → required, in: `0`, `1`

**Quick Add Brand modal (Soda/Order create):**
- `name` only in UI; `status` omitted → server defaults to `1`
- `status` → nullable, in: `0`, `1` on `store()`

**Forms referencing brand_id (Order, Dealer, Product):**
- `ActiveDropdownValidation::brandId()` — must be active (`status = 1`)

---

## ⚙️ Business Rules

### Status

- `1` = Active — green badge (`statusBadge()` on model)
- `0` = Inactive — red badge

### Listing order

- Query uses `BrandManagement::query()->ordered()` → `orderBy('id')` ascending
- DataTable client default: column 0 (`id`) ascending

### Dropdown usage elsewhere

Active brands for selects use:

```php
BrandManagement::activeForDropdown();
```

Returns active brands (`status = 1`) in ascending `id` order.

```php
BrandManagement::isActive($id); // true only when status = 1
```

**Used in:**

- `DealerManagementController` — create / edit / list filters / quick-create / `getDealersByBrokerBrand`
- `ProductController` — create / edit / list filter
- `OrderManagementController` — create / edit / list filter (`SalesScope::filterableBrands()`)
- `DeliveryPendingPaymentsController` — report brand filter
- `App\Support\SalesScope` — brand scope for broker vs global roles
- `App\Support\ActiveDropdownValidation` — form validation

### Broker brand scope (Sales)

From `SalesScope`:

- **super admin / admin / staff:** all active brands
- **Broker:** only brands linked to that broker’s dealers/orders (scoped query)

Brand CRUD itself is not broker-scoped; only sales dropdowns apply scope.

---

## 🛠️ Laravel Files

| File | Purpose |
|---|---|
| `app/Models/BrandManagement.php` | Model, `ordered()` scope, `activeForDropdown()`, `isActive()`, `statusBadge()` |
| `app/Http/Controllers/BrandManagementController.php` | index, `quickCreateForm()`, store, edit, update, destroy, bulkDelete |
| `resources/views/brand/partials/quick-create-form.blade.php` | Quick-add brand form (order create modal) |
| `resources/views/brand/index.blade.php` | List + modal + DataTables JS |
| `app/Support/ActiveDropdownValidation.php` | `brandId()` validation rule |
| `resources/views/layouts/sidebar.blade.php` | Users & Permissions → Brand link |
| `database/seeders/BrandPermissionSeeder.php` | Permissions + role assignment |
| `routes/web.php` | Brand routes |

### Model helpers

```php
// Scope: ascending by primary key
BrandManagement::query()->ordered();

// Active brands for dropdowns
BrandManagement::activeForDropdown(['id', 'name']);

// Check active brand (forms / filters)
BrandManagement::isActive($brandId);
```

### Relationships (consumed by other modules)

```
BrandManagement
  ← hasMany via FK (implicit):
      OrderManagement (brand_id)
      DealerManagement (brand_id)
      Product (brand_id)
```

---

## 📱 Sidebar

**Parent menu:** Users & Permissions  
**Link label:** Brand  
**Route:** `brand.index`  
**Visibility:** `@canany(['view-brand', 'add-brand', 'edit-brand', 'delete-brand'])`  
**Active state:** `request()->routeIs('brand*')`

---

## 🔄 AJAX / JSON responses

| Action | Response |
|---|---|
| store / update | `{ success: true, message: "...", brand?: { id, name, status } }` (brand object on create) |
| quickCreateForm | HTML partial for order-create modal |
| edit | Full brand JSON (`id`, `name`, `status`, …) |
| bulkDelete | `{ message: "Selected brands deleted successfully." }` |
| destroy | Redirect to `brand.index` with session flash |

Validation errors return standard Laravel 422 JSON for modal field display.

---

## ⚠️ Delete behaviour

- Single and bulk delete perform **hard delete** (`$brand->delete()`).
- No FK cascade block is implemented in the controller; deleting a brand that is referenced by orders/dealers/products may fail at the database level if FK constraints exist, or leave orphaned references if not constrained.

---

## 🧪 Test checklist

- [ ] User with `view-brand` only can open list; cannot add/edit/delete
- [ ] User with `add-brand` can create via modal; duplicate name rejected
- [ ] User with `edit-brand` can open edit modal and update
- [ ] User with `delete-brand` can single-delete and bulk-delete
- [ ] List sorts ascending by id
- [ ] Inactive brand hidden from `activeForDropdown()` but visible in admin list
- [ ] Inactive brand rejected by `ActiveDropdownValidation::brandId()` on order/dealer/product forms
- [ ] Quick Add Brand from order create: no Status field; saved as Active
- [ ] Quick Add Brand link hidden until broker selected on order create
- [ ] Sidebar shows Brand under Users & Permissions when permitted
- [ ] Seeder assigns permissions to admin and super admin

---

## 📎 Related documentation

- `md-file-requirements/Sales_Module_Requirements.md` — order/dealer brand usage, `SalesScope`
- `md-file-requirements/Dispatch_Pending_Payments_Module_Requirements.md` — brand column grouping
- `md-file-requirements/Dashboard_Module_Requirements.md` — brand references
