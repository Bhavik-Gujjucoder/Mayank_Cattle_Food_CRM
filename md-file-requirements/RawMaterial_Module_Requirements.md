# Raw Material Module — Requirements & AI Prompt
> Laravel 12 | Prepared for AI-assisted code generation

---

## 📁 Module Overview

**Module Name:** Raw Material
**Framework:** Laravel 12
**Sub-Modules:**
1. **Material** — Manage raw material master records
2. **Orders** — Manage purchase orders and their line items
3. **Received** — Manage delivery/receipt entries against order items

**Route name prefix:** `raw-material.*` (URL prefix `/raw-material`)

**Type identifiers (Spatie `permissions.type` column):**
- `raw-material-inventory` → Material (inventory)
- `raw-material-purchas-order` → Orders (note spelling: `purchas`, not `purchase`)
- `raw-material-receive` → Received

---

## 📅 Changelog — 2 Jun 2026 (permissions, routes, display, exports)

### Summary
Aligned the module with `RawMaterialPermissionSeeder` (view/export per submodule), grouped routes under `/raw-material`, enforced **2 decimal places** for all money fields in UI/PDF/Excel/JS, and documented `average_price` landed-cost formula.

### Highlights
| Area | Change |
|---|---|
| **Permissions** | Five permissions per submodule: `view-*`, `add-*`, `edit-*`, `delete-*`, `export-*`. List/show → `view-*`; exports → `export-*`; create/store → `add-*` only; edit/update → `edit-*` only (not `view-*`). Received uses `*-raw-material-receive`, not purchase-order permissions. |
| **Routes** | Single group: `Route::prefix('raw-material')->name('raw-material.')` with nested `order.*` and `receive.*`. |
| **Display** | Prices, freight, and money totals: **2 decimals** in DataTables, Blade show/PDF, Excel exports, order form JS (`toFixed(2)`, placeholders `0.00`). DB columns may remain `DECIMAL(15,3)`; storage precision ≠ display precision. |
| **Exports** | Filtered + full Excel/PDF for orders; per-order Excel/PDF; inventory and receive list exports — all gated by `export-*` permissions. |
| **Forms** | Shared `public/assets/js/raw-material-forms.js` — client validation + scroll-to-first-error (same pattern as Sales orders). Responsive partial: `resources/views/raw_material/partials/module-responsive.blade.php`. |
| **Seeder** | `database/seeders/RawMaterialPermissionSeeder.php` is **source of truth** — do not rename permissions in code without updating the seeder. Registered in `DatabaseSeeder`. |

### After deploy — run once (if permissions are stale)
```bash
php artisan db:seed --class="Database\Seeders\RawMaterialPermissionSeeder"
php artisan permission:cache-reset
```

---

## 🔐 Permissions (Spatie)

**Seeder:** `database/seeders/RawMaterialPermissionSeeder.php`  
- Deletes existing permissions matching the list, re-inserts, assigns all to `admin`, calls `forgetCachedPermissions()`.

### Material (`type: raw-material-inventory`)

| Permission | Used for |
|---|---|
| `view-raw-material-inventory` | List, show |
| `add-raw-material-inventory` | Create, store |
| `edit-raw-material-inventory` | Edit, update, toggle status |
| `delete-raw-material-inventory` | Destroy |
| `export-raw-material-inventory` | Excel export (filtered list) |

### Orders (`type: raw-material-purchas-order`)

| Permission | Used for |
|---|---|
| `view-raw-material-purchas-order` | List, show, order items AJAX |
| `add-raw-material-purchas-order` | Create, store |
| `edit-raw-material-purchas-order` | Edit, update, cancel |
| `delete-raw-material-purchas-order` | Destroy |
| `export-raw-material-purchas-order` | All order exports (filtered list, full module, per-order Excel/PDF, view PDF) |

### Received (`type: raw-material-receive`)

| Permission | Used for |
|---|---|
| `view-raw-material-receive` | List, show |
| `add-raw-material-receive` | Create, store |
| `edit-raw-material-receive` | Edit, update, mark received, cancel |
| `delete-raw-material-receive` | Destroy |
| `export-raw-material-receive` | Excel export (filtered list) |

### Enforcement layers
| Layer | Rule |
|---|---|
| **Routes** | `permission:*` middleware on each route (see Routes section below). |
| **Controllers** | DataTables action dropdowns: `@can` / `Gate::allows` per action (view, edit, delete, export). |
| **Blade / sidebar** | `@can` / `@canany` on menu items, buttons, and row actions. |

**Do not use** legacy names such as `view-raw-material-order` or `export-raw-material-order` — they are not in the seeder.

---

## 🗄️ Database Schema

### Table 1: `raw_materials`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| raw_material_unique_id | VARCHAR(20) | — | Format: `Raw-0001`, auto-generated |
| name | VARCHAR(255) | — | e.g. maize, soybean |
| unit | VARCHAR(50) | — | e.g. ton |
| total_stock | DECIMAL(12,2) | 0 | CACHED — see logic below |
| available_stock | DECIMAL(12,2) | 0 | CACHED — see logic below |
| used_stock | DECIMAL(12,2) | 0 | Reserved for future production module, ignore logic for now |
| last_purchase_price | DECIMAL(12,2) | 0 | CACHED — see logic below |
| average_price | DECIMAL(12,2) | 0 | CACHED — see logic below |
| status | TINYINT | 1 | 1 = active, 0 = inactive |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Cached Field Logic:**
- `total_stock` → On every `raw_material_receives` record where `status = 1`:
  `total_stock = total_stock + raw_material_receives.qty`
- `available_stock` → Same trigger as total_stock:
  `available_stock = available_stock + raw_material_receives.qty`
- `last_purchase_price` → After every new `raw_material_order_items` record:
  Pull the latest `price (rate)` for this `raw_material_id` from `raw_material_order_items`
- `average_price` → Recalculate when order items or receives change (landed cost per kg on received qty only):
  `average_price = SUM(raw_material_order_items.received_price + raw_material_order_items.total_freight) / (SUM(raw_material_order_items.received_qty) * 1000)`
  *(received_qty is in tons, result is per kg; 0 when no quantity has been received yet)*

---

### Table 2: `raw_material_orders`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| order_unique_id | VARCHAR(20) | — | Format: `RMO/2026-27/0001`, auto-generated |
| supplier_id | BIGINT | — | FK → `suppliers.id` |
| order_date | DATE | — | |
| total_qty | INTEGER | 0 | CACHED — sum of all order items `total_qty` |
| total_price | DECIMAL(15,3) | 0 | CACHED — sum of all order items `total_price` |
| total_freight | DECIMAL(15,3) | 0 | CACHED — sum of all order items `total_freight` |
| status | TINYINT | 0 | 0=pending, 1=partially received, 2=received, 3=cancelled |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Cached Field Logic:**
- `total_qty`, `total_price`, `total_freight` → Recalculate from all `raw_material_order_items` belonging to this order on every insert/update/delete of an order item
- `status` → Auto-update logic:
  - All items `pending` → `0 - pending`
  - Some items received, some pending → `1 - partially received`
  - All items fully received → `2 - received`
  - Manually set to `3 - cancelled`

---

### Table 3: `raw_material_order_items`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| raw_material_id | BIGINT | — | FK → `raw_materials.id` |
| raw_material_order_id | BIGINT | — | FK → `raw_material_orders.id` |
| total_qty | INTEGER | 0 | In tons |
| pending_qty | INTEGER | 0 | CACHED — starts = total_qty |
| received_qty | INTEGER | 0 | CACHED — starts = 0 |
| price | DECIMAL(15,3) | 0 | Rate per kg |
| price_avg | DECIMAL(15,3) | 0 | CACHED — avg landed cost per kg |
| total_price | DECIMAL(15,3) | 0 | Calculated: total_qty * 1000 * price |
| pending_price | DECIMAL(15,3) | 0 | CACHED — starts = total_price |
| received_price | DECIMAL(15,3) | 0 | CACHED — starts = 0 |
| total_freight | DECIMAL(15,3) | 0 | CACHED — accumulated from receives |
| status | TINYINT | 0 | 0=pending, 1=partially received, 2=received, 3=cancelled |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Cached Field Logic:**
- On `raw_material_receives` insert where `status = 1`:
  - `pending_qty = pending_qty - raw_material_receives.qty`
  - `received_qty = received_qty + raw_material_receives.qty`
  - `pending_price = pending_price - (raw_material_receives.qty * 1000 * price)`
  - `received_price = received_price + (raw_material_receives.qty * 1000 * price)`
  - `total_freight = total_freight + (raw_material_receives.freight * raw_material_receives.qty)`
  - `price_avg = (received_price + total_freight) / (received_qty * 1000)`
- `status` → Auto-update:
  - `received_qty = 0` → `0 - pending`
  - `0 < received_qty < total_qty` → `1 - partially received`
  - `received_qty = total_qty` → `2 - received`

---

### Table 4: `raw_material_receives`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| raw_material_id | BIGINT | — | FK → `raw_materials.id` |
| raw_material_order_id | BIGINT | — | FK → `raw_material_orders.id` |
| raw_material_order_item_id | BIGINT | — | FK → `raw_material_order_items.id` |
| qty | INTEGER | 0 | Received quantity in tons |
| freight | DECIMAL(15,3) | 0 | Freight charges for this delivery |
| received_date | DATE | — | Date of physical receipt |
| status | TINYINT | 0 | 0=on road, 1=unloading/received, 2=cancelled |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

**Business Logic:**
- Cached field updates on `raw_material_order_items` and `raw_materials` are only triggered when `status = 1` (unloading/received)
- When status changes from `1` back to `0` or `2`, reverse the cached values accordingly
- `qty` cannot exceed the `pending_qty` of the linked `raw_material_order_item`

---

## ⚙️ Business Rules

### Auto-Generated IDs
- `raw_material_unique_id` → Format: `Raw-0001`, `Raw-0002` … (4-digit zero-padded, sequential)
- `order_unique_id` → Format: `RMO/YYYY-YY/0001` (financial year based, resets each year)

### Units
- All `qty` fields stored in **tons**
- All `price` fields stored **per kg**
- Conversion: `kg = ton * 1000`

### Display formatting (money)
- Database columns for order/receive money may use `DECIMAL(15,3)`; **user-facing output uses 2 decimals** (`number_format(..., 2)`, export formatters, PDF/Blade, JS `toFixed(2)`).
- Applies to: `price`, `price_avg`, `total_price`, `pending_price`, `received_price`, `total_freight`, `freight`, `last_purchase_price`, `average_price`, and order header totals.

### Soft Deletes
- All 4 tables use soft deletes (`deleted_at`)
- Deleting an order should not be allowed if any `raw_material_receives` record exists under it

### Validation Rules
- `raw_material_receives.qty` ≤ `raw_material_order_items.pending_qty`
- `raw_material_order_items` must have at least 1 item when creating an order
- `price` must be > 0 on order items
- `order_date` cannot be a future date

---

## 🖥️ Sub-Module: 1 — Material

### Pages / Screens
1. **List Page**
   - Table with columns: `raw_material_unique_id`, `name`, `unit`, `total_stock`, `available_stock`, `last_purchase_price`, `average_price`, `status`
   - **Search:** by name
   - **Filters:**
     - Status → All / Active / Inactive
   - **Actions per row:** View, Edit, Toggle Status (activate/deactivate), Delete
   - **Page-level Actions:** Add New Material button, Export Excel button
   - **Export Excel:**
     - Exports only the currently filtered/searched records (not all records always)
     - File name: `raw-materials-YYYY-MM-DD.xlsx`
     - Export columns: `Material ID` | `Name` | `Unit` | `Total Stock` | `Available Stock` | `Last Price/kg` | `Avg Price/kg` | `Status`
     - Show record count on button: e.g. `Export (24)`
   - **Delete Rule:** Soft delete only. Block delete if any `raw_material_order_items` exist for this material — show error message instead.

2. **Add / Edit Form**
   - Fields: `name`, `unit`, `status`
   - `raw_material_unique_id` → auto-generated, shown as read-only
   - Cached fields (`total_stock`, `available_stock`, `last_purchase_price`, `average_price`) → not editable, display only on edit form

3. **View / Detail Page**
   - Show all fields including all cached stock and price fields
   - Show related order history table (list of `raw_material_order_items` for this material with order date, qty, price, status)

---

## 🖥️ Sub-Module: 2 — Orders

### Pages / Screens
1. **List Page**
   - Table columns in this exact order:
     `Sr No` | `Order ID` | `Supplier` | `Order Date` | `Total Qty` | `Total Price` | `Total Freight` | `Status` | `Action`
   - *(Total Qty column is added after Order Date)*
   - **Search:** by `order_unique_id`, supplier name
   - **Filters:**
     - Status → All / Pending / Partially Received / Received / Cancelled
     - Order Date → date range picker (from date, to date)
     - Supplier → dropdown
   - **Actions per row:** View, Edit (only if status = pending), Cancel (only if status = pending or partially received), Export Excel, Export PDF, Delete
   - **Per Order Export (row-level) — Excel:**
     - Triggered from row action menu (⋮) → Export Excel
     - Single .xlsx file with 3 sheets for that ONE order:
       - Sheet 1 `Order Details`: Order ID, Supplier, Order Date, Total Qty, Total Price, Total Freight, Status
       - Sheet 2 `Order Items`: Sr No | Material | Total Qty (tons) | Pending Qty | Received Qty | Price/kg | Avg Price/kg | Total Price | Pending Price | Received Price | Freight | Status
       - Sheet 3 `Receive Entries`: Sr No | Material | Qty (tons) | Freight | Received Date | Status
     - File name: `order-{order_unique_id}.xlsx`
   - **Per Order Export (row-level) — PDF:**
     - Triggered from row action menu (⋮) → Export PDF
     - Single formatted PDF document with 3 sections for that ONE order:
       - Section 1: Purchase Order header info
       - Section 2: Order Items table
       - Section 3: Receive Entries table
     - File name: `order-{order_unique_id}.pdf`
   - **Page-level Actions:** Add New Order button, Export Orders (filtered) dropdown, Full Export dropdown
   - **Export Orders — Filtered (top of list):**
     - Dropdown button with 2 options: Export Excel / Export PDF
     - Exports only currently filtered/searched records (respects all active filters)
     - Excel → single sheet, file name: `raw-material-orders-YYYY-MM-DD.xlsx`
       - Columns: `Order ID` | `Supplier` | `Order Date` | `Total Qty (tons)` | `Total Price` | `Total Freight` | `Status`
     - PDF → simple table document of filtered orders list
       - File name: `raw-material-orders-YYYY-MM-DD.pdf`
     - Show record count on button: e.g. `Export Orders (12)`
   - **Full Export — All Data (top of list):**
     - Dropdown button with 2 options: Export Excel / Export PDF
     - No filters applied — always exports ALL records
     - Excel → single .xlsx file with 3 sheets:
       - Sheet 1 `All Orders`: Order ID | Supplier | Order Date | Total Qty | Total Price | Total Freight | Status
       - Sheet 2 `All Order Items`: Order ID | Material | Total Qty (tons) | Pending Qty | Received Qty | Price/kg | Avg Price/kg | Total Price | Pending Price | Received Price | Freight | Status
       - Sheet 3 `All Receives`: Order ID | Material | Qty (tons) | Freight | Received Date | Status
       - File name: `raw-material-full-export-YYYY-MM-DD.xlsx`
     - PDF → single document with 3 sections (same data as Excel)
       - File name: `raw-material-full-export-YYYY-MM-DD.pdf`
       - Note: may be a large document depending on total records
     - Use queued export (Laravel queue) for both Excel and PDF if total records > 1000
   - **Delete Rule:** Soft delete only. Block delete if any `raw_material_receives` records exist under this order — show error message instead.

2. **Add / Edit Order Form**
   - Header fields: `supplier_id` (dropdown from suppliers), `order_date`
   - `order_unique_id` → auto-generated, shown as read-only
   - **Order Items section (inline / dynamic rows):**
     - Each row: `raw_material_id` (dropdown), `total_qty` (tons), `price` (per kg)
     - Auto-calculate and display: `total_price = total_qty * 1000 * price`
     - Add row button, Remove row button per row
     - Minimum 1 item required
   - Order totals (auto-calculated, read-only): `total_qty`, `total_price`
   - Edit allowed only if order `status = 0` (pending)

3. **View / Detail Page**
   - Show order header details
   - **Export PDF button** on this page (top right, next to Back button)
     - Generates a Purchase Order document with: order header info + order items table
     - File name: `purchase-order-{order_unique_id}.pdf`
     - Useful for sharing with supplier
   - Show all order items in a table with the following columns in this exact order:
     `Sr No` | `Material` | `Total Qty (tons)` | `Pending Qty` | `Received Qty` | `Price/kg` | `Avg Price/kg` | `Total Price` | `Pending Price` | `Received Price` | `Freight` | `Status`
   - *(Avg Price/kg added after Price/kg — Pending Price & Received Price added after Total Price)*
   - Show all `raw_material_receives` entries linked to this order in a sub-table
   - Show order `status` as a visual badge/indicator

---

## 🖥️ Sub-Module: 3 — Received

### Pages / Screens
1. **List Page**
   - Table with columns: `id`, `order_unique_id`, `raw_material name`, `qty`, `freight`, `received_date`, `status`
   - **Search:** by order unique id, material name
   - **Filters:**
     - Status → All / On Road / Received / Cancelled
     - Received Date → date range picker (from date, to date)
     - Raw Material → dropdown
     - Order → dropdown (searchable)
   - **Actions per row:** View, Edit (only if status = on road), Mark as Received (only if status = on road), Cancel (only if status = on road), Delete
   - **Page-level Actions:** Add New Entry button, Export Excel button
   - **Export Excel:**
     - Exports only the currently filtered/searched records
     - File name: `raw-material-receives-YYYY-MM-DD.xlsx`
     - Export columns: `Sr No` | `Order ID` | `Material` | `Qty (tons)` | `Freight` | `Received Date` | `Status`
     - Show record count on button: e.g. `Export (8)`
   - **Delete Rule:** Soft delete only. Block delete if `status = 1` (already received) — show error message. Allow delete only if `status = 0` (on road) or `status = 2` (cancelled).

2. **Add Form**
   - Fields: `raw_material_order_id` (dropdown — shows only pending/partially received orders), `raw_material_order_item_id` (dropdown — filtered dynamically by selected order, shows only pending items with pending qty info), `qty`, `freight`, `received_date`, `status`
   - Auto-populate `raw_material_id` (read-only) from selected order item
   - Show helper text: "Pending Qty: X tons" beside the qty field for selected order item
   - Validate `qty` ≤ `pending_qty`

3. **Edit Form**
   - Allow editing only if `status = 0` (on road)
   - Same fields as add form

4. **Mark as Received Action**
   - One-click action button (confirmation popup before executing)
   - Changes `status` from `0 → 1`
   - Triggers all cached field updates on `raw_material_order_items` and `raw_materials`

5. **Cancel Action**
   - One-click action button (confirmation popup before executing)
   - Changes `status` to `2 - cancelled`
   - If previous status was `1` (received), reverse all cached field updates

---

## 🎨 Design Requirements

### Design Philosophy
- **Do NOT use a generic/default Bootstrap theme**
- Study the existing project's UI carefully and match it exactly
- Match: color scheme, sidebar style, navbar style, card style, button styles, badge styles, table styles, form input styles, spacing, and font

### How to Capture Existing Design (Instructions for AI)
Before generating any Blade views, the AI must:
1. Open and read the existing Blade layout file (typically `resources/views/layouts/app.blade.php` or `resources/views/layouts/master.blade.php`)
2. Open 1–2 existing module views (e.g., any existing index.blade.php and create.blade.php in the project) to understand the exact patterns used
3. Note the following from existing views:
   - CSS framework version and any custom CSS classes used
   - How page titles and breadcrumbs are structured
   - How cards/panels are used to wrap content
   - How tables are styled (striped, bordered, hover, responsive wrapper, etc.)
   - How action buttons are placed (inline in table, dropdown menu, icon buttons, etc.)
   - How forms are laid out (label position, input size, grid columns)
   - How flash messages / alerts are shown (success, error)
   - How modals are used (if any — for delete confirmation, status change, etc.)
   - How pagination is rendered
   - How status badges/pills are colored
   - How filters/search bar is positioned (above table, inline, sidebar)
4. Use ALL of the above patterns consistently across every view generated for this module

### Specific Design Rules
- **List pages:** Match existing table layout exactly — same wrapper divs, same classes, same action button style
- **Forms:** Match existing form layout — same card structure, same label/input pattern, same submit button placement
- **Delete confirmation:** Use the same confirmation method already in the project (modal popup OR inline form with confirmation prompt — check existing code)
- **Status badges:** Use same color mapping style as existing badges in the project
- **Flash messages:** Reuse existing session flash alert pattern (`session('success')`, `session('error')`)
- **Breadcrumbs:** Follow existing breadcrumb structure if present in the project
- **Sidebar menu:** Add the Raw Material module menu items in the sidebar following the same format as existing menu items

### Dynamic Order Items Form Design
- The dynamic add/remove rows section for order items must visually match the overall form style
- Show a clean summary row at the bottom of the items table: Total Qty | Total Price
- Each item row should have a remove (×) button aligned consistently with the row

---

## 🛠️ Laravel 12 Technical Requirements

### Architecture
- Use **Laravel 12** with standard MVC pattern
- Use **Eloquent ORM** with Model relationships
- Use **Laravel Migrations** for all tables
- Use **Soft Deletes** (`SoftDeletes` trait) on all models
- Use **Laravel Observers** or **Model Events** for all cached field updates
- Use **Form Requests** for validation
- Use **Resource Controllers** for all CRUD operations
- Use **Laravel Policies** for authorization (if auth is already set up)

### Models & Relationships
```
RawMaterial
  - hasMany(RawMaterialOrderItem)
  - hasMany(RawMaterialReceive)

RawMaterialOrder
  - belongsTo(Supplier)
  - hasMany(RawMaterialOrderItem)
  - hasMany(RawMaterialReceive)

RawMaterialOrderItem
  - belongsTo(RawMaterial)
  - belongsTo(RawMaterialOrder)
  - hasMany(RawMaterialReceive)

RawMaterialReceive
  - belongsTo(RawMaterial)
  - belongsTo(RawMaterialOrder)
  - belongsTo(RawMaterialOrderItem)
```

### Routes
All routes live under `routes/web.php` inside `Route::prefix('raw-material')->name('raw-material.')`.

| URL path | Route name | Middleware (permission) |
|---|---|---|
| `GET /raw-material` | `raw-material.index` | `view-raw-material-inventory` |
| `GET /raw-material/create` | `raw-material.create` | `add-raw-material-inventory` |
| `POST /raw-material` | `raw-material.store` | `add-raw-material-inventory` |
| `GET /raw-material/{id}` | `raw-material.show` | `view-raw-material-inventory` |
| `GET /raw-material/{id}/edit` | `raw-material.edit` | `edit-raw-material-inventory` |
| `PUT/PATCH /raw-material/{id}` | `raw-material.update` | `edit-raw-material-inventory` |
| `DELETE /raw-material/{id}` | `raw-material.destroy` | `delete-raw-material-inventory` |
| `GET /raw-material/export` | `raw-material.export` | `export-raw-material-inventory` |
| `PATCH /raw-material/{id}/toggle-status` | `raw-material.toggleStatus` | `edit-raw-material-inventory` |
| `GET /raw-material/order` | `raw-material.order.index` | `view-raw-material-purchas-order` |
| `GET /raw-material/order/create` | `raw-material.order.create` | `add-raw-material-purchas-order` |
| `POST /raw-material/order` | `raw-material.order.store` | `add-raw-material-purchas-order` |
| `GET /raw-material/order/{id}` | `raw-material.order.show` | `view-raw-material-purchas-order` |
| `GET /raw-material/order/{id}/edit` | `raw-material.order.edit` | `edit-raw-material-purchas-order` |
| `GET /raw-material/order/export` (+ full/filtered/per-order PDF routes) | `raw-material.order.*` | `export-raw-material-purchas-order` |
| `GET /raw-material/receive` | `raw-material.receive.index` | `view-raw-material-receive` |
| `GET /raw-material/receive/create` | `raw-material.receive.create` | `add-raw-material-receive` |
| `POST /raw-material/receive` | `raw-material.receive.store` | `add-raw-material-receive` |
| `PATCH /raw-material/receive/{id}/mark-received` | `raw-material.receive.markReceived` | `edit-raw-material-receive` |
| `GET /raw-material/receive/export` | `raw-material.receive.export` | `export-raw-material-receive` |

Register **order** and **receive** route groups before material wildcard routes (`{raw_material}`).

### Key Migrations
- All tables include: `id`, `created_at`, `updated_at`, `deleted_at`
- Foreign keys with `constrained()` and `cascadeOnDelete()` where appropriate

### Observers (Cached Field Updates)
Create a `RawMaterialReceiveObserver` that:
- On `created` / `status updated to 1` → update `raw_material_order_items` and `raw_materials`
- On `status updated to 2 (cancelled)` → reverse the cached values if previously status was 1

Create a `RawMaterialOrderItemObserver` that:
- On `created` / `updated` → update `raw_material_orders` totals and `raw_materials` price fields

### Frontend
- Use **Blade templates** with **Bootstrap 5** (or whatever UI framework is already in the project)
- Order items in the order form should be dynamic (add/remove rows) using **Alpine.js** or **vanilla JS**
- Show real-time calculated totals in the order form as user types qty/price

---

## 🤖 AI Generation Prompt

> Copy everything below this line and share it with an AI agent along with the Database.xlsx file or the schema above.

---

```
You are a Laravel 12 developer. I need you to generate a complete "Raw Material" module for my Laravel 12 application.

## Context
This is a cattle feed manufacturing company's ERP system. The Raw Material module tracks:
1. Raw material master data (maize, soybean, etc.)
2. Purchase orders placed to suppliers
3. Physical receipt/delivery of ordered materials

## Database Schema
[Attach Database.xlsx — sheet: "Raw Materials Final"]

The schema has 4 tables:
- raw_materials
- raw_material_orders
- raw_material_order_items
- raw_material_receives

## What to Generate

Generate the following complete Laravel 12 files:

### 1. Migrations
- create_raw_materials_table
- create_raw_material_orders_table
- create_raw_material_order_items_table
- create_raw_material_receives_table

### 2. Models
- RawMaterial (with SoftDeletes, relationships, fillable)
- RawMaterialOrder (with SoftDeletes, relationships, fillable)
- RawMaterialOrderItem (with SoftDeletes, relationships, fillable)
- RawMaterialReceive (with SoftDeletes, relationships, fillable)

### 3. Observers
- RawMaterialOrderItemObserver
  - On created/updated: recalculate raw_material_orders (total_qty, total_price, total_freight) and update raw_materials (last_purchase_price; average_price from received landed cost)
- RawMaterialReceiveObserver
  - On created/updated: when status=1, update raw_material_order_items (pending_qty, received_qty, pending_price, received_price, total_freight, price_avg, status) and update raw_materials (total_stock, available_stock, average_price)
  - If status changes to cancelled (2), reverse previous cached updates

### 4. Form Requests (Validation)
- StoreRawMaterialRequest
- UpdateRawMaterialRequest
- StoreRawMaterialOrderRequest (validates header + items array)
- UpdateRawMaterialOrderRequest
- StoreRawMaterialReceiveRequest (validates qty <= pending_qty)
- UpdateRawMaterialReceiveRequest

### 5. Controllers (Resource Controllers)
- RawMaterialController (index, create, store, show, edit, update, destroy, toggleStatus)
- RawMaterialOrderController (index, create, store, show, edit, update, destroy, cancel)
- RawMaterialReceiveController (index, create, store, show, edit, update, destroy, markReceived)

### 6. Routes
Add all routes in a route group with prefix 'raw-materials' inside web.php

### 7. Blade Views
For each sub-module generate:
- index.blade.php (list/table with search, filters, and delete action)
- create.blade.php
- edit.blade.php
- show.blade.php

For the Orders create/edit view, include dynamic order items rows (add/remove) using Alpine.js with real-time total calculation.

## Business Rules to Implement

### Auto ID Generation
- raw_material_unique_id → format: Raw-0001 (sequential, zero-padded 4 digits)
- order_unique_id → format: RMO/2026-27/0001 (financial year based, resets yearly)

### Units
- All qty fields are stored in TONS
- All price fields are stored PER KG
- Conversion: 1 ton = 1000 kg

### Cached Fields — DO NOT let users edit these directly:
On raw_material_order_items:
- total_price = total_qty * 1000 * price
- pending_price (starts = total_price, decreases as receives come in)
- received_price (starts = 0, increases as receives come in)
- pending_qty (starts = total_qty, decreases as receives come in)
- received_qty (starts = 0, increases as receives come in)
- total_freight (accumulated from all receives: total_freight += freight * qty per receive)
- price_avg = (received_price + total_freight) / (received_qty * 1000)
- status: 0=pending, 1=partially received, 2=received (auto-calculated based on qty)

On raw_material_orders:
- total_qty, total_price, total_freight = SUM of all order items
- status: auto-calculated based on all items status

On raw_materials:
- total_stock += qty (when receive status = 1)
- available_stock += qty (when receive status = 1)
- last_purchase_price = most recent price from raw_material_order_items for this material
- average_price = SUM(received_price + total_freight) / (SUM(received_qty) * 1000) across all order items for this material (0 if SUM(received_qty) = 0)

### Filters — implement on every list page
**Material list:**
- Search by name (text input)
- Filter by status (All / Active / Inactive)

**Orders list:**
- Search by order_unique_id or supplier name (text input)
- Filter by status (All / Pending / Partially Received / Received / Cancelled)
- Filter by order_date range (from date + to date)
- Filter by supplier (dropdown)
- Orders list table column order: Sr No → Order ID → Supplier → Order Date → Total Qty → Total Price → Total Freight → Status → Action

**Received list:**
- Search by order_unique_id or raw material name (text input)
- Filter by status (All / On Road / Received / Cancelled)
- Filter by received_date range (from date + to date)
- Filter by raw material (dropdown)
- Filter by order (searchable dropdown)

### Delete Rules
- All deletes are SOFT DELETES only (never hard delete)
- raw_materials: Block delete if any raw_material_order_items exist for this material
- raw_material_orders: Block delete if any raw_material_receives exist under this order
- raw_material_order_items: Block delete if any raw_material_receives exist for this item
- raw_material_receives: Block delete if status = 1 (already received). Allow delete only if status = 0 or 2.
- On blocked delete: return a clear error message explaining why deletion is not allowed
- On all delete actions: show a confirmation popup/modal before executing

### Validation
- raw_material_receives.qty must not exceed raw_material_order_items.pending_qty
- An order must have at least 1 order item
- price must be > 0 on order items
- Cannot delete an order if any receives exist for it
- Can only edit an order if status = 0 (pending)
- Can only edit a receive if status = 0 (on road)

### Soft Deletes
All 4 tables use soft deletes.

## Design Instructions — IMPORTANT

**Do NOT generate generic Bootstrap views. Follow these steps before writing any Blade view:**

### Step 1 — Read the existing layout file
Read: `resources/views/layouts/app.blade.php` (or the main layout used in this project)
Understand the base HTML structure, sidebar, navbar, and how @yield sections are defined.

### Step 2 — Read 2 existing module views
Read any existing index.blade.php and create.blade.php from another module already in the project.
Identify:
- How page header / title / breadcrumb is written
- How the filter bar and search box are structured
- How the data table is wrapped and styled
- How action buttons (edit, delete, view) are placed in table rows
- How forms are laid out (card wrapper, label position, button placement)
- How delete confirmation is handled (modal or JS confirm)
- How flash success/error messages are displayed
- How pagination is rendered
- How status badges are colored

### Step 3 — Match everything exactly
Every view in this module must look like it was built by the same developer who built the rest of the project.
- Use the exact same CSS classes, wrapper divs, and component patterns
- Do not introduce any new CSS framework or library not already present in the project
- Add the Raw Material module links in the sidebar navigation following the same format as other menu items already there

### Step 4 — Status badge colors
Use these consistent colors for status badges (match to project's existing badge style):
- Pending → warning / yellow
- Partially Received → info / blue
- Received → success / green
- Cancelled → danger / red
- On Road → secondary / gray
- Active → success / green
- Inactive → danger / red

## Tech Stack Assumptions
- Laravel 12
- Bootstrap 5 for UI
- Alpine.js for dynamic order items form
- Standard Blade templating
- MySQL database

## Additional Notes
- suppliers table already exists with columns: id, name, mobile, email, address, status
- **Money display:** Show prices, freight, and line/order totals with **2 decimal places** everywhere users read them (lists, detail pages, PDFs, Excel, order form calculations). Quantity/stock in tons may use 0–2 decimals as appropriate.
- Use Laravel Observers for all cached field update logic
- Keep controllers thin — move business logic to Service classes if needed
- Add proper indexes on foreign key columns in migrations
- All list pages must support filter state persistence via GET query params (so filters remain after page reload)
- View Order page — order items table column order: Sr No | Material | Total Qty (tons) | Pending Qty | Received Qty | Price/kg | Avg Price/kg | Total Price | Pending Price | Received Price | Freight | Status

### Export Features
Use Laravel Excel (maatwebsite/excel) package for all Excel exports.

**Material List — Export Excel**
- Button placement: top right of list page, next to Add button
- Exports currently filtered/searched records only (respect all active filters)
- File name: raw-materials-YYYY-MM-DD.xlsx
- Columns: Material ID | Name | Unit | Total Stock | Available Stock | Last Price/kg | Avg Price/kg | Status
- Show record count on button: Export (N)

**Orders — Per Order Export (row action menu ⋮)**
- Export Excel:
  - 3-sheet .xlsx file for that single order
  - Sheet 1 "Order Details": order header fields
  - Sheet 2 "Order Items": all items of that order with all columns
  - Sheet 3 "Receive Entries": all receives of that order
  - File name: order-{order_unique_id}.xlsx
- Export PDF:
  - Single formatted PDF with 3 sections: order header + items table + receives table
  - File name: order-{order_unique_id}.pdf
  - Use barryvdh/laravel-dompdf

**Orders List — Export Orders Filtered (dropdown button on list page)**
- 2 options: Export Excel / Export PDF
- Exports only currently filtered/searched records (respect all active filters)
- Excel: single sheet, columns: Order ID | Supplier | Order Date | Total Qty (tons) | Total Price | Total Freight | Status, file name: raw-material-orders-YYYY-MM-DD.xlsx
- PDF: simple table of filtered orders, file name: raw-material-orders-YYYY-MM-DD.pdf
- Show record count on button: Export Orders (N)

**Orders List — Full Export (dropdown button on list page)**
- 2 options: Export Excel / Export PDF
- No filters — always exports ALL records
- Excel: 3-sheet .xlsx file:
  - Sheet 1 "All Orders": all order headers
  - Sheet 2 "All Order Items": all items across all orders (include Order ID column)
  - Sheet 3 "All Receives": all receives across all orders (include Order ID column)
  - File name: raw-material-full-export-YYYY-MM-DD.xlsx
- PDF: single document with 3 sections (same data as Excel)
  - File name: raw-material-full-export-YYYY-MM-DD.pdf
  - May be a large document depending on total records
- Use queued export (Laravel queue) for both Excel and PDF if total records > 1000

**View Order Page — Export PDF**
- Button placement: top right of view page, next to Back button
- Same as per-order PDF export above
- File name: purchase-order-{order_unique_id}.pdf
- Use barryvdh/laravel-dompdf

**Received List — Export Excel**
- Button placement: top right of list page, next to Add button
- Exports currently filtered/searched records only (respect all active filters)
- File name: raw-material-receives-YYYY-MM-DD.xlsx
- Columns: Sr No | Order ID | Material | Qty (tons) | Freight | Received Date | Status
- Show record count on button: Export (N)

**General Export Rules**
- All exports must respect active filters — never export all records when filters are applied
- Use queued exports for large datasets if records > 1000
- Excel files should have a styled header row (bold, background color)
```
