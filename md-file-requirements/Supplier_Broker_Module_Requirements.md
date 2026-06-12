# Supplier Broker Module — Requirements & Implementation Reference
> Laravel 12 | Spatie Permissions | Users & Permissions submenu

---

## 📁 Module Overview

**Module Name:** Supplier Broker Management  
**Menu Location:** Users & Permissions → **Supplier Broker** (direct link, above Supplier)  
**Route prefix:** `/supplier-broker`  
**Permission type (DB `type` column):** `supplier-broker`

Supplier brokers are master records that group suppliers. Each supplier must belong to one supplier broker. This module provides CRUD for the `supplier_brokers` table using the same field set and UI patterns as the Supplier module.

---

## 📅 Changelog — 12 Jun 2026 (List filter reset)

### Summary
- **Reset** button added on the list page filter bar (`#resetSupplierBrokerFilters`)
- Clears search, Status, State, and City filters and redraws the DataTable
- City filter returns to disabled “All Cities” state (same as initial page load)

---

## 📅 Changelog — 12 Jun 2026 (Initial module)

### Summary
- New **Supplier Broker** CRUD module under Users & Permissions
- Permissions: `view-supplier-broker`, `add-supplier-broker`, `edit-supplier-broker`, `delete-supplier-broker`
- Sidebar shows **Supplier Broker** and **Supplier** as sibling links (not nested)
- Suppliers table extended with `supplier_broker_id` FK

---

## 🗄️ Database Schema

### Table: `supplier_brokers`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| name | VARCHAR(255) | — | Supplier broker name (indexed) |
| mobile | VARCHAR(20) | NULL | Contact number |
| email | VARCHAR(255) | NULL | Email address |
| address | TEXT | NULL | Full address |
| opening_balance | DECIMAL(12,2) | 0 | Opening account balance |
| state_id | BIGINT | NULL | FK → `state_management.id` |
| city_id | BIGINT | NULL | FK → `city_management.id` |
| status | TINYINT | 1 | `1` = Active, `0` = Inactive |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Migrations:**
- `database/migrations/2026_06_12_000001_create_supplier_brokers_table.php`

**Notes:**
- Soft deletes enabled (`SoftDeletes` trait).
- State/city columns use the same General Settings master tables as Supplier.

### Related tables (FK references)

| Table | Column | Usage |
|---|---|---|
| `suppliers` | `supplier_broker_id` | Each supplier belongs to one supplier broker |

---

## 🔐 Permissions (Spatie)

| Permission | `type` (DB) | Used for |
|---|---|---|
| `view-supplier-broker` | `supplier-broker` | List page (`supplier-broker.index`); controller middleware on `index` |
| `add-supplier-broker` | `supplier-broker` | Add modal + `supplier-broker.store` |
| `edit-supplier-broker` | `supplier-broker` | Edit action + `supplier-broker.update`; AJAX `supplier-broker.edit` |
| `delete-supplier-broker` | `supplier-broker` | Row delete + bulk delete; `supplier-broker.destroy`, `supplier-broker.bulkDelete` |

### Seeder: `SupplierBrokerPermissionSeeder`

- Path: `database/seeders/SupplierBrokerPermissionSeeder.php`
- Creates/updates permissions via `updateOrCreate` (idempotent)
- **Assigns all four permissions to `admin` and `super admin` roles**
- Registered in `DatabaseSeeder::run()`

**Run seeder:**

```bash
php artisan db:seed --class=SupplierBrokerPermissionSeeder
```

### Enforcement layers

| Layer | Implementation |
|---|---|
| **Controller** | `$this->middleware('permission:view-supplier-broker')->only(['index'])` |
| **Routes** | `add-supplier-broker` / `edit-supplier-broker` / `delete-supplier-broker` on store, update, destroy, bulkDelete |
| **Blade** | `@can` / `@canany` on Add button, action column, checkboxes, sidebar link |
| **DataTables** | Edit/Delete buttons gated with `auth()->user()->can('edit-supplier-broker')` / `can('delete-supplier-broker')` |

---

## 🛣️ Routes

Defined in `routes/web.php` (auth + verified middleware group):

| Method | URI | Route name | Middleware |
|---|---|---|---|
| GET | `/supplier-broker` | `supplier-broker.index` | `view-supplier-broker` (controller) |
| POST | `/supplier-broker` | `supplier-broker.store` | `permission:add-supplier-broker` |
| GET | `/supplier-broker/{supplier_broker}/edit` | `supplier-broker.edit` | auth only (JSON for modal) |
| PUT/PATCH | `/supplier-broker/{supplier_broker}` | `supplier-broker.update` | `permission:edit-supplier-broker` |
| DELETE | `/supplier-broker/{supplier_broker}` | `supplier-broker.destroy` | `permission:delete-supplier-broker` |
| POST | `/supplier-broker/bulk-delete` | `supplier-broker.bulkDelete` | `permission:delete-supplier-broker` |

Resource routes `create` and `show` are registered but unused (modal-based CRUD, same pattern as Supplier).

---

## 🖥️ UI — Supplier Broker Management List

**View:** `resources/views/supplier_broker/index.blade.php`  
**Page title:** Supplier Broker Management

### Table columns (order)

`Checkbox` (delete permission only) | `Sr No` | `Name` | `Mobile` | `Email` | `Address` | `City` | `Status` | `Action`

### Filters (above table)

| Filter | Options |
|---|---|
| Search | Custom text box (name, mobile, email via DataTables) |
| Status | All / Active / Inactive |
| State | All active states |
| City | All cities for selected state (AJAX via `get.cities`) |
| Reset | Button `#resetSupplierBrokerFilters` — clears all filters and search |

### Reset filters behaviour

Clicking **Reset** (`btn btn-light`, refresh icon):

1. Clears `#customSearch` and DataTables search
2. Sets `#filterStatus` and `#filterState` to `all`
3. Resets `#filterCity` to disabled with single option “All Cities”
4. Triggers `change.select2` on `.search-dropdown`
5. Redraws `#supplier_broker_table`

### Features

- Server-side **DataTables** with custom search box
- **Reset filters** button on filter bar (same pattern as Soda/Order and Raw Material Order lists)
- **Default sort:** descending by `id` (hidden column)
- **Add Supplier Broker** button (top right) — `@can('add-supplier-broker')`
- **Add / Edit modal** — single form (`#supplierBrokerForm`, `#supplierBrokerModal`)
- **Row actions (⋮):** Edit | Delete — permission-gated
- **Bulk delete** — checkbox column + “Delete Selected” (`@can('delete-supplier-broker')`)
- **Delete confirmation** — SweetAlert2

### Modal fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Name | Text (`name`) | Yes | Max 255 |
| Mobile | Text (`mobile`) | No | Max 10 digits in UI |
| Email | Email (`email`) | No | Unique among non-deleted rows |
| Address | Textarea (`address`) | No | |
| State | Dropdown (`state_id`) | Yes | Active states from `state_management` |
| City | Dropdown (`city_id`) | Yes | Loaded via AJAX when state changes; disabled until state selected |
| Status | Radio | No | Active (`1`, default) / Inactive (`0`) |

**Note:** Opening Balance field exists in DB/schema but is hidden in the UI (same as Supplier module).

### Validation (store / update)

- `name` → required, string, max 255
- `mobile` → nullable, string, max 20
- `email` → nullable, email, max 255, unique on `supplier_brokers.email` (ignore current id on update, `whereNull('deleted_at')`)
- `address` → nullable, string
- `opening_balance` → nullable, numeric, min 0 (defaults to 0)
- `state_id` → required, exists: `state_management,id`
- `city_id` → required, exists: `city_management,id`; must belong to selected state and be active
- `status` → nullable, in: `0`, `1` (defaults to 1)

---

## ⚙️ Business Rules

### Status

- `1` = Active — green badge (`statusBadge()` on model)
- `0` = Inactive — red badge

### State → City dependency

- City dropdown disabled until a state is selected
- On state change: AJAX `POST` to `route('get.cities')` with `state_id`
- City list resets when state changes
- Server validates city belongs to state and city is active (`status = 1`)

### Delete behaviour

- **Soft delete** on destroy
- **Block delete** if any linked `suppliers` exist (single delete → redirect with error flash; bulk delete → 422 JSON with broker names)
- Bulk delete skips brokers that have suppliers and returns an error listing blocked names

### Relationship to Supplier module

```
SupplierBroker
  - belongsTo(StateManagement, 'state_id')
  - belongsTo(CityManagement, 'city_id')
  - hasMany(Supplier)

Supplier
  - belongsTo(SupplierBroker, 'supplier_broker_id')
```

- Supplier create/edit forms load active supplier brokers: `SupplierBroker::where('status', 1)->orderBy('name')`
- Create at least one supplier broker before adding suppliers

---

## 🛠️ Laravel Files

| File | Purpose |
|---|---|
| `app/Models/SupplierBroker.php` | Model, relationships, `statusBadge()` |
| `app/Http/Controllers/SupplierBrokerController.php` | index, store, edit, update, destroy, bulkDelete |
| `resources/views/supplier_broker/index.blade.php` | List + modal + DataTables JS |
| `resources/views/layouts/sidebar.blade.php` | Users & Permissions → Supplier Broker link |
| `database/seeders/SupplierBrokerPermissionSeeder.php` | Permissions + role assignment |
| `database/migrations/2026_06_12_000001_create_supplier_brokers_table.php` | Table creation |
| `routes/web.php` | Supplier broker routes |

---

## 📱 Sidebar

**Parent menu:** Users & Permissions  
**Link label:** Supplier Broker  
**Route:** `supplier-broker.index`  
**Position:** Direct child under Users & Permissions, **above** Supplier  
**Visibility:** `@canany(['view-supplier-broker', 'add-supplier-broker', 'edit-supplier-broker', 'delete-supplier-broker'])`  
**Active state:** `request()->routeIs('supplier-broker*')`

---

## 🔄 AJAX / JSON responses

| Action | Response |
|---|---|
| store / update | `{ success: true, message: "..." }` |
| edit | Full supplier broker JSON (`id`, `name`, `mobile`, `email`, `address`, `state_id`, `city_id`, `status`, …) |
| bulkDelete | `{ message: "Selected supplier brokers deleted successfully." }` or 422 with blocked names |
| destroy | Redirect to `supplier-broker.index` with session flash (success or error) |

Validation errors return standard Laravel 422 JSON for modal field display.

---

## 🧪 Test checklist

- [ ] User with `view-supplier-broker` only can open list; cannot add/edit/delete
- [ ] User with `add-supplier-broker` can create via modal
- [ ] User with `edit-supplier-broker` can open edit modal and update
- [ ] User with `delete-supplier-broker` can single-delete and bulk-delete
- [ ] Cannot delete broker with linked suppliers (single + bulk)
- [ ] State/city AJAX works in modal and list filters
- [ ] City validation rejects city not in selected state
- [ ] Sidebar shows Supplier Broker above Supplier under Users & Permissions
- [ ] Seeder assigns permissions to admin and super admin
- [ ] Reset button clears search, status, state, and city filters and refreshes the table

---

## 📎 Related documentation

- `md-file-requirements/Supplier_Module_Requirements.md` — Supplier CRUD, `supplier_broker_id` usage
- `md-file-requirements/RawMaterial_Module_Requirements.md` — raw material orders reference `supplier_id`
