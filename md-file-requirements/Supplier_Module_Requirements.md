# Supplier Module — Requirements & Implementation Reference
> Laravel 12 | Spatie Permissions | Users & Permissions submenu

---

## 📁 Module Overview

**Module Name:** Supplier Management  
**Menu Location:** Users & Permissions → **Supplier** (direct link, below Supplier Broker)  
**Route prefix:** `/supplier`  
**Permission type (DB `type` column):** `supplier`

Suppliers are master records used in the Raw Material purchase order flow. Each supplier belongs to a **Supplier Broker** (`supplier_broker_id`). This module provides CRUD for the `suppliers` table with the same UI patterns as Supplier Broker.

---

## 📅 Changelog — 12 Jun 2026 (List filter reset)

### Summary
- **Reset** button added on the list page filter bar (`#resetSupplierFilters`)
- Clears search, Supplier Broker, Status, State, and City filters and redraws the DataTable
- City filter returns to disabled “All Cities” state (same as initial page load)

---

## 📅 Changelog — 12 Jun 2026 (Supplier Broker relationship)

### Summary
- Suppliers now require a **Supplier Broker** (`supplier_broker_id` FK → `supplier_brokers`)
- List page adds **Supplier Broker** column and filter
- Add/Edit modal adds **Supplier Broker** dropdown (active brokers only)
- Sidebar: **Supplier Broker** and **Supplier** shown as sibling links under Users & Permissions

See `Supplier_Broker_Module_Requirements.md` for the parent broker module.

---

## 🗄️ Database Schema

### Table: `suppliers`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| supplier_broker_id | BIGINT | NULL | FK → `supplier_brokers.id` (required on create/update) |
| name | VARCHAR(255) | — | Supplier name (indexed) |
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
- `database/migrations/2026_04_21_110715_create_suppliers_table.php`
- `database/migrations/2026_06_01_000001_add_state_city_to_suppliers_table.php`
- `database/migrations/2026_06_12_000002_add_supplier_broker_id_to_suppliers_table.php`

### Related tables (FK references)

| Table | Column | Usage |
|---|---|---|
| `supplier_brokers` | `supplier_broker_id` | Parent broker for this supplier |
| `raw_material_orders` | `supplier_id` | Purchase orders reference supplier |

---

## 🔐 Permissions (Spatie)

| Permission | `type` (DB) | Used for |
|---|---|---|
| `add-supplier` | `supplier` | Add modal + `supplier.store` |
| `edit-supplier` | `supplier` | Edit action + `supplier.update`; AJAX `supplier.edit` |
| `delete-supplier` | `supplier` | Row delete + bulk delete; `supplier.destroy`, `supplier.bulkDelete` |

**Note:** There is no `view-supplier` permission. Any authenticated user with route access can open the list; add/edit/delete are gated separately (same pattern as before Supplier Broker was added).

### Enforcement layers

| Layer | Implementation |
|---|---|
| **Routes** | `add-supplier` / `edit-supplier` / `delete-supplier` on store, update, destroy, bulkDelete |
| **Blade** | `@can` / `@canany` on Add button, action column, checkboxes, sidebar link |
| **DataTables** | Edit/Delete buttons gated with `auth()->user()->can('edit-supplier')` / `can('delete-supplier')` |

---

## 🛣️ Routes

Defined in `routes/web.php` (auth + verified middleware group):

| Method | URI | Route name | Middleware |
|---|---|---|---|
| GET | `/supplier` | `supplier.index` | auth only |
| POST | `/supplier` | `supplier.store` | `permission:add-supplier` |
| GET | `/supplier/{supplier}/edit` | `supplier.edit` | auth only (JSON for modal) |
| PUT/PATCH | `/supplier/{supplier}` | `supplier.update` | `permission:edit-supplier` |
| DELETE | `/supplier/{supplier}` | `supplier.destroy` | `permission:delete-supplier` |
| POST | `/supplier/bulk-delete` | `supplier.bulkDelete` | `permission:delete-supplier` |

**Cities AJAX (shared with Supplier Broker):**

| Method | URI | Route name |
|---|---|---|
| POST | `/get-cities` | `get.cities` |

---

## 🖥️ UI — Supplier Management List

**View:** `resources/views/supplier/index.blade.php`  
**Page title:** Supplier Management

### Table columns (order)

`Checkbox` (delete permission only) | `Sr No` | `Supplier Broker` | `Name` | `Mobile` | `Email` | `Address` | `City` | `Status` | `Action`

### Filters (above table)

| Filter | Options |
|---|---|
| Search | Custom text box (name, mobile, email, supplier broker name via DataTables) |
| Supplier Broker | All / active supplier brokers |
| Status | All / Active / Inactive |
| State | All active states |
| City | All cities for selected state (AJAX via `get.cities`) |
| Reset | Button `#resetSupplierFilters` — clears all filters and search |

### Reset filters behaviour

Clicking **Reset** (`btn btn-light`, refresh icon):

1. Clears `#customSearch` and DataTables search
2. Sets `#filterSupplierBroker`, `#filterStatus`, and `#filterState` to `all`
3. Resets `#filterCity` to disabled with single option “All Cities”
4. Triggers `change.select2` on `.search-dropdown`
5. Redraws `#supplier_table`

### Features

- Server-side **DataTables** with custom search box
- **Reset filters** button on filter bar (same pattern as Soda/Order and Raw Material Order lists)
- **Default sort:** descending by `id` (hidden column)
- **Add Supplier** button (top right) — `@can('add-supplier')`
- **Add / Edit modal** — single form (`#supplierForm`, `#supplierModal`)
- **Row actions (⋮):** Edit | Delete — permission-gated
- **Bulk delete** — checkbox column + “Delete Selected” (`@can('delete-supplier')`)
- **Delete confirmation** — SweetAlert2

### Modal fields

| Field | Type | Required | Notes |
|---|---|---|---|
| Supplier Broker | Dropdown (`supplier_broker_id`) | Yes | Active brokers only (`status = 1`) |
| Name | Text (`name`) | Yes | Max 255 |
| Mobile | Text (`mobile`) | No | Max 10 digits in UI |
| Email | Email (`email`) | No | Unique among non-deleted rows |
| Address | Textarea (`address`) | No | |
| State | Dropdown (`state_id`) | Yes | Active states from `state_management` |
| City | Dropdown (`city_id`) | Yes | Loaded via AJAX when state changes; disabled until state selected |
| Status | Radio | No | Active (`1`, default) / Inactive (`0`) |

**Note:** Opening Balance field exists in DB/schema but is hidden in the UI.

### Validation (store / update)

- `supplier_broker_id` → required, exists: `supplier_brokers,id`
- `name` → required, string, max 255
- `mobile` → nullable, string, max 20
- `email` → nullable, email, max 255, unique on `suppliers.email` (ignore current id on update, `whereNull('deleted_at')`)
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

### Supplier Broker assignment

- Every new supplier must be linked to an active supplier broker
- Dropdown on create/edit shows only brokers with `status = 1`, ordered by name
- Legacy rows may have `supplier_broker_id = NULL` until edited and saved with a broker

### State → City dependency

- City dropdown disabled until a state is selected
- On state change: AJAX `POST` to `route('get.cities')` with `state_id`
- City list resets when state changes
- Server validates city belongs to state and city is active (`status = 1`)

### Delete behaviour

- **Soft delete** on destroy and bulk delete
- No controller-level block for linked `raw_material_orders` (DB FK on `raw_material_orders.supplier_id` may restrict delete depending on migration constraints)

### Models & relationships

```
Supplier
  - belongsTo(SupplierBroker, 'supplier_broker_id')
  - belongsTo(StateManagement, 'state_id')
  - belongsTo(CityManagement, 'city_id')
  - hasMany(RawMaterialOrder)

SupplierBroker
  - hasMany(Supplier)
```

### Dropdown usage elsewhere

Active suppliers for raw material order forms:

```php
Supplier::where('status', 1)->orderBy('name')->get();
```

Used in `RawMaterialOrderController` create/edit/index filters.

---

## 🛠️ Laravel Files

| File | Purpose |
|---|---|
| `app/Models/Supplier.php` | Model, relationships, `statusBadge()` |
| `app/Http/Controllers/SupplierController.php` | index, store, edit, update, destroy, bulkDelete |
| `resources/views/supplier/index.blade.php` | List + modal + DataTables JS |
| `resources/views/layouts/sidebar.blade.php` | Users & Permissions → Supplier link |
| `routes/web.php` | Supplier routes |

---

## 📱 Sidebar

**Parent menu:** Users & Permissions  
**Link label:** Supplier  
**Route:** `supplier.index`  
**Position:** Direct child under Users & Permissions, **below** Supplier Broker  
**Visibility:** `@canany(['add-supplier', 'edit-supplier', 'delete-supplier'])`  
**Active state:** `request()->routeIs('supplier.*')` (does not match `supplier-broker*` routes)

### Users & Permissions submenu order

1. Brand  
2. **Supplier Broker**  
3. **Supplier**  
4. Broker (sales user role)  
5. Dealer  
6. Transporter  
7. Truck Management  
8. Users / Roles (as configured)

---

## 🔄 AJAX / JSON responses

| Action | Response |
|---|---|
| store / update | `{ success: true, message: "..." }` |
| edit | Full supplier JSON including `supplier_broker_id`, `state_id`, `city_id`, … |
| bulkDelete | `{ message: "Selected suppliers deleted successfully." }` |
| destroy | Redirect to `supplier.index` with session flash |

Validation errors return standard Laravel 422 JSON for modal field display.

---

## 🧪 Test checklist

- [ ] User with `add-supplier` can create supplier with required broker
- [ ] User with `edit-supplier` can open edit modal and update
- [ ] User with `delete-supplier` can single-delete and bulk-delete
- [ ] Supplier Broker filter and column work on list
- [ ] Cannot save without `supplier_broker_id`
- [ ] State/city AJAX works in modal and list filters
- [ ] City validation rejects city not in selected state
- [ ] Sidebar shows Supplier below Supplier Broker
- [ ] Raw material order supplier dropdown shows active suppliers only
- [ ] Reset button clears search, supplier broker, status, state, and city filters and refreshes the table

---

## 📎 Related documentation

- `md-file-requirements/Supplier_Broker_Module_Requirements.md` — parent broker CRUD and permissions
- `md-file-requirements/RawMaterial_Module_Requirements.md` — purchase orders use `supplier_id`
