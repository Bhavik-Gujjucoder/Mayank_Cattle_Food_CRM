# Sales Module — Requirements & AI Prompt
> Laravel 12 | Bootstrap 5 | Prepared for AI-assisted code generation

---

## 📁 Module Overview

**Module Name:** Sales (Soda / Order + Dispatch)
**Framework:** Laravel 12
**UI:** Bootstrap 5, jQuery, DataTables (Yajra), Select2, SweetAlert2, Flatpickr
**Sidebar:** Nested under **Sales** → **Soda / Order** | **Dispatch**

**Sub-Modules:**
1. **Soda / Order** — Create and manage dealer sales orders with line items (products, qty, unit price, payment status)
2. **Dispatch** — Record bag/ton dispatches against order line items; view global dispatch list and per-order dispatch history

**Type identifiers (used in routes/comments):**
- `soda-order` → Order routes (`order.*`)
- `dispatch` → Dispatch routes (`dispatch.*`)

---

## 📅 Changelog — 2 Jun 2026 (Sales module permissions)

### Summary
Implemented end-to-end Spatie permission enforcement for **Soda/Order** and **Dispatch**, fixed missing `add-dispatch` seeding, and aligned route/UI/controller checks.

### Files changed / added

| Area | File | Change |
|---|---|---|
| Base controller | `app/Http/Controllers/Controller.php` | Extends `Illuminate\Routing\Controller` so `$this->middleware()` works in controllers |
| Middleware aliases | `bootstrap/app.php` | Registers `permission`, `role`, `role_or_permission` (Spatie) |
| Seeder (new) | `database/seeders/SalesPermissionSeeder.php` | Creates sales permissions with `type` column; assigns all to `admin` role |
| Seeder registry | `database/seeders/DatabaseSeeder.php` | Calls `SalesPermissionSeeder` |
| Routes | `routes/web.php` | Dispatch resource uses `role_or_permission`; mutating routes use `permission:*` |
| Order controller | `app/Http/Controllers/OrderManagementController.php` | Constructor middleware + Dispatch action gated by `canAny` |
| Dispatch controller | `app/Http/Controllers/DispatchManagementController.php` | Dispatch list action column gated by `canAny` |
| Views | `resources/views/dispatch_management/history.blade.php` | Add button: `@canany(['add-dispatch'])` |
| Views | `resources/views/order_management/index.blade.php` | Action column visibility uses `canAny` |

### After deploy — run once
```bash
php artisan db:seed --class=Database\Seeders\SalesPermissionSeeder
php artisan permission:cache-reset
```

---

## 📅 Changelog — 2 Jun 2026 (Role-based sales data scope)

### Summary
Centralized **who can see which orders/dispatches** by role. Works together with Spatie permissions (permissions = *what actions*; scope = *which rows*).

### Roles & visibility

| Role | Orders / dispatches visible |
|---|---|
| **super admin**, **admin**, **staff** | All records (no broker/dealer filter) |
| **broker** | Only where `order_management.broker_id` = logged-in user id |
| **dealer** | Only where `dealer_management.user_id` = logged-in user id |
| **transporter** | Not scoped in sales module (uses other modules; no order ownership filter) |

**Priority:** If a user has `super admin`, `admin`, or `staff`, they get **global** access even if they also have broker/dealer role.

### Implementation: `App\Support\SalesScope`

| Method | Purpose |
|---|---|
| `hasGlobalAccess()` | true for super admin / admin / staff |
| `scopeOrders($query)` | Apply broker/dealer WHERE on `OrderManagement` |
| `scopeDispatches($query)` | Apply scope via `order` / `order.dealer` |
| `authorizeOrderAccess($order)` | `abort(403)` if order outside scope |
| `authorizeDispatchAccess($dispatch)` | Same via parent order |
| `enforceOrderAssignment($validated)` | Force `broker_id` / `dealer_id` on store & update |
| `authorizeDealerId($dealerId)` | Dealer role may only use own dealer id (AJAX) |
| `showBrokerFilter()` | false for broker & dealer (hide list filter) |

**Model scopes:** `OrderManagement::forUser()`, `DispatchManagement::forUser()` delegate to `SalesScope`.

### Where scope is applied

| Area | Applied |
|---|---|
| Order list (DataTables) | `SalesScope::scopeOrders()` |
| Order edit / update / destroy / deleteCheck / dispatchCheck | `authorizeOrderAccess()` |
| Order store / update | `enforceOrderAssignment()` + dealer id check |
| Order bulk delete | scoped query |
| Order create (dealer) | locked dealer field + server enforcement |
| Dispatch list + filter dropdown | `scopeOrders` / `scopeDispatches` |
| Dispatch history / store / update / destroy / form-data AJAX | `authorizeOrderAccess` / `authorizeDispatchAccess` |
| Dashboard (`HomeController`) | `scopeOrders` / `scopeDispatches` |

### Files added / updated

- `app/Support/SalesScope.php` (new)
- `app/Models/OrderManagement.php` — `scopeForUser`
- `app/Models/DispatchManagement.php` — `scopeForUser`
- `app/Http/Controllers/OrderManagementController.php`
- `app/Http/Controllers/DispatchManagementController.php`
- `app/Http/Controllers/HomeController.php`
- `resources/views/order_management/index.blade.php` — broker filter via `showBrokerFilter()`
- `resources/views/order_management/create.blade.php` — locked dealer for dealer role

---

## 🔐 Permissions (Spatie)

| Permission | `type` (DB) | Used for |
|---|---|---|
| `add-order` | `soda-order` | Order list + create (`index`, `create`); route `order.store` |
| `edit-order` | `soda-order` | Edit order (`edit`); route `order.update` |
| `delete-order` | `soda-order` | Delete + bulk delete; routes `order.destroy`, `order.bulkDelete` |
| `add-dispatch` | `dispatch` | Add dispatch modal on history page; route `dispatch.store` |
| `edit-dispatch` | `dispatch` | Edit dispatch modal on history page; route `dispatch.update` |
| `delete-dispatch` | `dispatch` | Route `dispatch.destroy` (UI delete button commented out in history view) |
| `view-dispatch-pending-payments` | `dispatch` | Delivery pending payments module (also seeded here) |

### Seeder: `SalesPermissionSeeder`
- Path: `database/seeders/SalesPermissionSeeder.php`
- Recreates the permissions above (detach + delete + insert pattern, same as `RawMaterialPermissionSeeder`)
- **Assigns all sales permissions to the `admin` role** by default
- Registered in `DatabaseSeeder::run()`
- **Note:** Other roles (e.g. broker) must be assigned permissions via Roles UI or a custom seeder

### Enforcement layers (route + controller + Blade)

| Layer | Order module | Dispatch module |
|---|---|---|
| **Routes** | `order.store` → `permission:add-order`; `order.update` → `edit-order`; `order.destroy` / `order.bulkDelete` → `delete-order` | Resource (`index`, `create`, `show`, `edit`) → `role_or_permission:add-dispatch\|edit-dispatch\|delete-dispatch`; `dispatch.store` → `add-dispatch`; `dispatch.update` → `edit-dispatch`; `dispatch.destroy` → `delete-dispatch` |
| **Controller constructor** | `add-order` → `index`, `create`; `edit-order` → `edit` | *(none — route middleware only)* |
| **DataTables / UI** | Dispatch row action → `canAny(['add-dispatch','edit-dispatch','delete-dispatch'])`; Edit/Delete → `can('edit-order')` / `can('delete-order')` | List action column → `canAny([...])` |
| **Blade** | Sidebar / index `@canany` | History: Add modal `@canany(['add-dispatch'])`; Edit `@can('edit-dispatch')` |

### `role_or_permission` vs `@canany`
- **Routes:** Use middleware `role_or_permission:add-dispatch|edit-dispatch|delete-dispatch` (pipe = OR). Equivalent to Blade `@canany`.
- **Blade:** `@canany(['add-dispatch', 'edit-dispatch'])` — do **not** use `@canany` on routes; it is view-only.
- **PHP:** `auth()->user()->canAny([...])` in DataTables closures.

### Routes without Spatie *permission* middleware (auth only)
These rely on `auth` + **`SalesScope`** in controllers (not permission middleware on the route):
- `order.lastItemPrice`, `order.dispatchCheck`, `order.deleteCheck`
- `dispatch.orderHistory`, `dispatch.orderFormData`, `dispatch.transporterTrucks`

**Role behaviour (data scope — see `SalesScope`):**
- **super admin / admin / staff:** All orders; broker filter on list; full create/edit.
- **Broker:** Own orders only; broker filter hidden; broker fixed on forms; dispatch/history limited to own orders.
- **Dealer:** Own dealer’s orders only; broker filter hidden; dealer fixed on create; dispatch/history limited to own orders.
- **Transporter:** Not filtered by sales scope (assign permissions separately if they need dispatch UI).

---

## 🗄️ Database Schema

### Table: `order_management`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| unique_order_id | VARCHAR | — | Unique. Format: `ORD/YYYY-YY/NNNN` (financial year, 4-digit seq) |
| broker_id | BIGINT | — | FK → `users.id` (user with `broker` role) |
| brand_id | BIGINT | — | FK → `brand_management.id` |
| dealer_id | BIGINT | — | FK → `dealer_management.id` |
| order_date | DATE | — | |
| delivery_address | TEXT | — | Auto-filled from dealer; editable |
| payment_status | ENUM | `unpaid` | `unpaid` \| `paid` \| `partial` |
| partial_paid_amount | DECIMAL(20,2) | NULL | Required when `payment_status = partial` |
| total_order_amount | DECIMAL(20,2) | 0 | Sum of line item `total_price` |
| grand_total | DECIMAL(20,2) | 0 | Currently same as `total_order_amount` |
| status | TINYINT | 1 | 1 = active, 0 = inactive (badge helper exists; list column commented out) |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

### Table: `order_items`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| order_id | BIGINT | — | FK → `order_management.id` |
| product_id | BIGINT | — | FK → `products.id` |
| qty | UNSIGNED INT | — | Quantity ordered (bags/ton per business label) |
| unit_price | DECIMAL(20,2) | — | Price at time of order |
| total_price | DECIMAL(20,2) | — | `qty × unit_price` |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | — | — | **Not used** (soft deletes commented out in migration/model) |

### Table: `dispatch_management`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| order_id | BIGINT | — | FK → `order_management.id` |
| order_item_id | BIGINT | — | FK → `order_items.id` |
| product_id | BIGINT | — | FK → `products.id` (denormalized from order item) |
| no_of_bags | UNSIGNED INT | — | Bags/ton dispatched in this entry |
| dispatch_date | DATE | — | |
| transport_id | BIGINT | — | FK → `users.id` (user with `transporter` role) |
| truck_number | VARCHAR(100) | — | Selected from transporter's trucks (stored as string) |
| driver_contact | VARCHAR(20) | — | Auto-filled from transporter phone; editable |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

### Related tables (already exist — do not recreate)

| Table | Role in Sales module |
|---|---|
| `users` | Brokers (`broker` role), transporters (`transporter` role) |
| `brand_management` | Order header brand |
| `dealer_management` | Order header dealer; linked to broker + brand |
| `products` | Order line items; active products only in dropdowns |
| `trucks` | Trucks per transporter (`transporter_id`, `truck_number`, `status`) |

---

## ⚙️ Business Rules

### Auto-Generated Order ID
- Format: `ORD/{financial-year}/{NNNN}` e.g. `ORD/2025-26/0001`
- Financial year: April–March (month ≥ 4 → current year start; else previous year)
- Sequence: `OrderManagement::withTrashed()->count() + 1`, zero-padded to 4 digits

### Dealer loading (Order form)
- Dealer dropdown **disabled** until broker **and** brand are selected
- Load dealers via AJAX: `GET /get-dealers?broker_id=X&brand_id=Y` (`route('get.dealers')`)
- On dealer select: auto-fill **delivery address** from dealer record

### Last unit price hint (Order form)
- On product select (when dealer is set): `GET /order-last-price?dealer_id=X&product_id=Y`
- Returns last `unit_price` for that dealer + product from most recent `order_items` row

### Order totals
- Per line: `total_price = qty × unit_price`
- Header: `total_order_amount` and `grand_total` = sum of line totals

### Payment status
- `unpaid` → red badge
- `paid` → green badge
- `partial` → yellow/warning badge; show `partial_paid_amount` field

### Sequential dispatch (critical)
- For a given **dealer**, orders must be dispatched in **creation order** (`order_management.id` ASC)
- An order cannot receive new dispatches until **all prior orders** for the same dealer are **fully dispatched**
- **Fully dispatched** = every `order_item` has `SUM(dispatch.no_of_bags) >= order_item.qty`
- Enforced in:
  - AJAX: `GET /order/{order}/dispatch-check` → JSON `{ eligible: true }` or `{ eligible: false, blocking_order: {...} }`
  - Order list: **Dispatch** action runs check first; if blocked → SweetAlert popup with pending items + link to blocking order history
  - Dispatch history page: shows blocked banner + disables **Add New Dispatch** when prior order incomplete
  - `DispatchManagementController@store`: server-side guard (blocks direct POST)

### Over-dispatch prevention
- Per order item: `no_of_bags` in a dispatch entry cannot exceed **pending qty** = `item.qty - SUM(existing dispatches for that item)`
- On **edit** dispatch: pending = `item.qty - SUM(other dispatches excluding current row)`

### Order edit restrictions (after dispatch started)
- If any line item has `dispatched bags > 0`:
  - Cannot change `broker_id`, `brand_id`, `dealer_id`
  - Cannot **remove** a line item that has dispatches
  - Cannot set `qty` below already-dispatched quantity for that line
- Can still add **new** line items (no dispatches yet)
- Removed line items (no dispatches) are soft-deleted from `order_items` on update

### Order delete rules
- **Block delete** if any order item has dispatch records (`sum(no_of_bags) > 0`)
- Pre-check AJAX: `GET /order/{order}/delete-check` → `{ can_delete, dispatched_items[] }`
- UI: SweetAlert shows dispatched product breakdown if blocked
- On allowed delete: soft-delete order + soft-delete all `order_items`
- **Bulk delete:** `POST /order-bulk-delete` with `ids[]`; returns 422 if any selected order has dispatches

### Dispatch list — order completion chip
- Order ID column links to `dispatch/order/{order}` (history)
- If **all items** on that order are fully dispatched → show green **Complete** chip beside order ID
- DataTables `createdRow` can highlight complete rows using `is_complete` flag

### Transporter → truck dropdown
- On transporter select: `GET /dispatch/transporter-trucks/{transporter}`
- Returns `{ trucks: [{id, truck_number}], phone }`
- Populate truck dropdown; auto-fill `driver_contact` from transporter `phone_no`

---

## 🖥️ Sub-Module 1 — Soda / Order

### Existing file references (design + logic)
| Purpose | Path |
|---|---|
| List | `resources/views/order_management/index.blade.php` |
| Create | `resources/views/order_management/create.blade.php` |
| Edit | `resources/views/order_management/edit.blade.php` |
| Controller | `app/Http/Controllers/OrderManagementController.php` |
| Model | `app/Models/OrderManagement.php`, `app/Models/OrderItem.php` |

### 1. List Page (`/order`)

- **Page title:** Soda/Order Management
- **Layout:** `layouts.main`, card with header filter bar + DataTable
- **Table columns (exact order):**
  `Sr No` | `Order ID` | `Broker` | `Brand` | `Dealer` | `Order Date` | `Payment Status` | `Action`
- *(Grand Total and Order Status columns exist in code but are commented out)*
- **Search:** DataTables global search on `unique_order_id` (custom search input `#customSearch`)
- **Filters:**
  - Brand → dropdown (`#BrandId`, All / specific brand)
  - Broker → dropdown (`#broker_id`, All / specific broker) — **hidden for broker role**
- **Server-side:** Yajra DataTables AJAX to `order.index`
- **Page-level action:** **Add Soda/Order** → `order.create` (requires `add-order` via controller middleware on `create`)
- **List access:** `order.index` requires `add-order` (controller middleware)
- **Row actions (⋮ dropdown):**
  - **Dispatch** — shown only if `canAny(['add-dispatch','edit-dispatch','delete-dispatch'])`; runs sequential dispatch check then navigates to `dispatch.orderHistory`
  - **Edit** — `order.edit` (permission `edit-order`)
  - **Delete** — AJAX delete-check then confirm (permission `delete-order`)
- **Bulk delete:** Route and button exist (`#bulk_delete_button`); row checkboxes are **commented out** in current UI

### 2. Add Order Page (`/order/create`)

- **Page title:** Add - Soda/Order
- **Form:** POST `order.store`, multipart (standard POST)

**Card 1 — Order Information**

| Field | Type | Required | Notes |
|---|---|---|---|
| Order ID | Text (readonly) | — | Pre-generated `ORD/YYYY-YY/NNNN` |
| Broker | Select (Select2) | Yes | Disabled/pre-selected for broker role |
| Brand | Select | Yes | |
| Dealer | Select | Yes | Loaded after broker + brand; disabled until then |
| Order Date | Date (Flatpickr) | Yes | Default today |
| Delivery Address | Textarea | Yes | Auto from dealer |

**Card 2 — Product Items (dynamic rows)**

| Column | Notes |
|---|---|
| S.No | Auto row index |
| Product Name | Select from active products; filter by brand in JS |
| QTY | Integer, min 1 |
| Unit Price | Decimal; shows "Last unit price" hint via AJAX |
| Action | Add New row / Remove row (min 1 row) |

- Hidden `total[]` column (calculated, not shown)
- Minimum **1** product row required

**Card 3 — Payment Status**

| Field | Notes |
|---|---|
| Payment Status | Radio: Unpaid (default) / Paid / Partial |
| Partial Paid Amount | Shown only when Partial selected |

**Footer:** Cancel (back to list) | Save Order

### 3. Edit Order Page (`/order/{order}/edit`)

- Same structure as Add; fields pre-populated
- Hidden `item_id[]` per row to track existing `order_items` on update
- Dealers pre-loaded for order's broker + brand
- Enforces dispatch-related edit guards (see Business Rules)

### 4. Delete Order

- Row ⋮ → Delete → `delete-check` AJAX → if blocked: custom SweetAlert with dispatch table; else `confirmDeletion()` → submit hidden DELETE form

---

## 🖥️ Sub-Module 2 — Dispatch

### Existing file references

| Purpose | Path |
|---|---|
| Global list | `resources/views/dispatch_management/index.blade.php` |
| Per-order history | `resources/views/dispatch_management/history.blade.php` |
| Controller | `app/Http/Controllers/DispatchManagementController.php` |
| Model | `app/Models/DispatchManagement.php` |

### 1. Dispatch List Page (`/dispatch`)

- **Page title:** Dispatch Management (custom header with truck icon)
- **Filter:** Order dropdown (Select2) — only orders that **have at least one dispatch** (`OrderManagement::has('dispatches')`)
- **Table columns:**
  `Sr No` | `Order ID` | `Product` | `Bags / Ton` | `Dealer Name` | `Dispatch Date` | `Transport` | `Truck Number` | `Driver Contact` | `Action`
- **Order ID column:** Link to order history; **Complete** chip when order fully dispatched
- **Server-side:** Yajra DataTables
- **Route access:** `dispatch.index` requires **any** of `add-dispatch`, `edit-dispatch`, `delete-dispatch` (`role_or_permission` on resource)
- **Row action:** View History → `dispatch.orderHistory` (action dropdown hidden unless `canAny` dispatch permissions)
- *(Edit from list is commented out)*

### 2. Dispatch History Page (`/dispatch/order/{order}`)

- **Page title:** Dispatch History
- **Header:** Order ID + dealer name; **Add New Dispatch** button (modal) if not blocked and user has `add-dispatch` (`@canany(['add-dispatch'])` in `history.blade.php`)
- **Blocked state:** Alert bar + pending item cards for **prior incomplete order**; CTA link to that order's history
- **Pending summary:** Per order item — Pending / Dispatched / Total + progress bar
- **Completion banner:** When all items 100% dispatched
- **History table:** All dispatch rows grouped by order items (Sr, Product, Bags, Date, Transport, Truck, Driver, Action)
- **Edit:** Inline button opens **Edit Dispatch** modal (`edit-dispatch`)
- **Delete:** UI commented out; route `dispatch.destroy` still exists
- **Back:** Link to `order.index`

### 3. Add Dispatch — Modal (on history page)

| Field | Type | Required | Notes |
|---|---|---|---|
| order_id | Hidden | Yes | Current order |
| order_item_id | Select | Yes | Only items with `pending > 0`; shows Ordered/Pending in label |
| product_id | Hidden | Yes | Set from selected order item |
| no_of_bags | Number | Yes | Min 1; max = pending qty |
| dispatch_date | Date (Flatpickr) | Yes | |
| transport_id | Select | Yes | Transporters (users with transporter role) |
| truck_number | Select | Yes | Loaded via AJAX after transporter |
| driver_contact | Text | Yes | Auto from transporter phone |

- Form POST → `dispatch.store` → redirect back to history with success flash
- jQuery Validate on form

### 4. Edit Dispatch — Modal

- Fields: `no_of_bags`, `dispatch_date`, `transport_id`, `truck_number`, `driver_contact`
- Product name shown read-only
- PUT `dispatch.update`
- Over-dispatch guard uses effective pending including current row's bags

---

## 🎨 Design Requirements

### Design philosophy
- **Do NOT** introduce a new UI framework
- Match the existing CRM: **Bootstrap 5**, Tabler icons (`ti ti-*`), project card/table classes (`card`, `cls-cardhed-part`, `custom-table`, `form-section-title`, etc.)
- Study existing Sales views before generating new screens

### How to capture existing design (instructions for AI)

Before generating any Blade view:

1. Read `resources/views/layouts/main.blade.php` (base layout)
2. Read **reference views** (in priority order):
   - `resources/views/order_management/index.blade.php`
   - `resources/views/order_management/create.blade.php`
   - `resources/views/dispatch_management/history.blade.php`
   - `resources/views/dispatch_management/index.blade.php`
3. Note patterns:
   - Card header filter layout (`cls-cardhed-part`, `cls-form-left`, `cls-form-right`)
   - DataTables + Select2 initialization in `@section('script')`
   - ⋮ action dropdown (`table-action`, `fa-ellipsis-v`)
   - Payment/dispatch badges (`bg-success-light`, `bg-warning-light`, etc.)
   - SweetAlert2 custom popups for blocked dispatch/delete (`dbp-*`, `od-*` CSS classes)
   - Dispatch history custom sections (`dh-*`, `pd-*` classes)
   - Modals: Bootstrap 5 `modal-dialog modal-lg`
   - Flatpickr on date fields
4. Reuse the same patterns for any new Sales screens

### Status / badge colors

| Context | Style |
|---|---|
| Payment Unpaid | `bg-danger-light text-danger` |
| Payment Paid | `bg-success-light text-success` |
| Payment Partial | `bg-warning-light text-warning` |
| Dispatch complete chip | `dispatch-complete-chip` with `ti-circle-check` |

### Sidebar menu

Under **Sales** submenu (see `resources/views/layouts/sidebar.blade.php`):

- **Soda / Order** → `route('order.index')` — permissions: `add-order`, `edit-order`, `delete-order`
- **Dispatch** → `route('dispatch.index')` — permissions: `add-dispatch`, `edit-dispatch`, `delete-dispatch`

---

## 📱 Responsive Requirements

- Follow the project’s Bootstrap 5 responsive grid and existing CRM patterns (same as Order/Dispatch pages).
- Filter/search header bars must wrap to multiple rows on small screens without breaking layout.
- DataTables must not cause full-page horizontal overflow; keep scroll contained to `table-responsive`.
- Modals (Add/Edit/Dispatch) must remain fully usable on mobile (scrollable modal body if needed, buttons stay reachable).
- Keep action dropdowns and key CTAs (Add, Export, Dispatch) accessible at `xs` widths.

---

## 🛠️ Laravel 12 Technical Requirements

### Architecture
- Laravel 12 MVC
- Eloquent ORM + `SoftDeletes` on `OrderManagement`, `DispatchManagement` (not on `OrderItem`)
- Controllers: `OrderManagementController`, `DispatchManagementController`
- Yajra DataTables for list pages (server-side)
- **Spatie permissions:** middleware aliases in `bootstrap/app.php`; route-level + controller constructor + Blade/`canAny` for UI
- Base `App\Http\Controllers\Controller` must extend `Illuminate\Routing\Controller` for `$this->middleware()` in constructors
- No separate Form Request classes in current code — validation inline in controllers

### Models & relationships

```
OrderManagement (order_management)
  - belongsTo(User, broker_id) → broker
  - belongsTo(BrandManagement, brand_id) → brand
  - belongsTo(DealerManagement, dealer_id) → dealer
  - hasMany(OrderItem, order_id) → items
  - hasMany(DispatchManagement, order_id) → dispatches
  - isFullyDispatched() helper

OrderItem (order_items)
  - belongsTo(OrderManagement, order_id) → order
  - belongsTo(Product, product_id) → product
  - hasMany(DispatchManagement, order_item_id) → dispatches
  - dispatchedQty(), pendingQty() helpers

DispatchManagement (dispatch_management)
  - belongsTo(OrderManagement, order_id) → order
  - belongsTo(OrderItem, order_item_id) → orderItem
  - belongsTo(Product, product_id) → product
  - belongsTo(User, transport_id) → transporter
```

### Routes (`routes/web.php`)

All sales routes are inside `Route::middleware(['auth', 'verified'])`.

```php
// Order / Soda-Order
Route::get('order-last-price', ...)->name('order.lastItemPrice');
Route::get('order/{order}/dispatch-check', ...)->name('order.dispatchCheck');
Route::get('order/{order}/delete-check', ...)->name('order.deleteCheck');
Route::resource('order', OrderManagementController::class)->except(['store','update','destroy']);
// OrderManagementController __construct:
//   permission:add-order → index, create
//   permission:edit-order → edit
Route::post('order', ...)->middleware('permission:add-order');
Route::match(['put','patch'], 'order/{order}', ...)->middleware('permission:edit-order');
Route::delete('order/{order}', ...)->middleware('permission:delete-order');
Route::post('order-bulk-delete', ...)->middleware('permission:delete-order');

// Dispatch — orderHistory BEFORE resource
Route::get('dispatch/order/{order}', ...)->name('dispatch.orderHistory');
Route::get('dispatch/order/{order}/form-data', ...)->name('dispatch.orderFormData');
Route::get('dispatch/transporter-trucks/{transporter}', ...)->name('dispatch.transporterTrucks');
Route::resource('dispatch', DispatchManagementController::class)
    ->except(['store','update','destroy'])
    ->middleware('role_or_permission:add-dispatch|edit-dispatch|delete-dispatch');
Route::post('dispatch', ...)->middleware('permission:add-dispatch');
Route::match(['put','patch'], 'dispatch/{dispatch}', ...)->middleware('permission:edit-dispatch');
Route::delete('dispatch/{dispatch}', ...)->middleware('permission:delete-dispatch');

// Shared (dealer loading on order form)
Route::get('/get-dealers', ...)->name('get.dealers');
```

### Middleware registration (`bootstrap/app.php`)

```php
$middleware->alias([
    'role' => RoleMiddleware::class,
    'permission' => PermissionMiddleware::class,
    'role_or_permission' => RoleOrPermissionMiddleware::class,
]);
```

### Key implementation files

| File | Responsibility |
|---|---|
| `OrderManagementController` | index (DataTables), create, store, edit, update, destroy, bulkDelete, lastItemPrice, deleteCheck, checkDispatchEligibility, validateOrder(); constructor middleware; Dispatch action `canAny`; **SalesScope** on queries & mutations |
| `DispatchManagementController` | index (DataTables), orderHistory, store, update, destroy, getOrderDispatchFormData, getTrucksByTransporter; **SalesScope** on list & authorize |
| `SalesPermissionSeeder` | Seed order + dispatch permissions; assign to `admin` |
| `SalesScope` | Central role-based row filtering for orders & dispatches |
| `app/Http/Controllers/Controller.php` | Laravel base controller (required for middleware in constructors) |

---

## 🤖 AI Generation Prompt

> Copy everything below this line and share it with an AI agent.

---

```
You are a Laravel 12 developer. Document and/or rebuild the existing "Sales" module (Soda/Order + Dispatch) for a cattle feed manufacturing ERP.

## Context
Sales tracks dealer orders placed by brokers (per brand) and physical dispatch of ordered products in bags/tons. Dispatch must follow strict sequential order per dealer.

## Tech Stack
- Laravel 12
- Bootstrap 5 (match existing project — NOT Tailwind, NOT generic admin themes)
- jQuery, Yajra DataTables (server-side), Select2, SweetAlert2, Flatpickr
- Spatie permissions
- Blade templates extending resources/views/layouts/main.blade.php
- MySQL

## Database Schema

### order_management
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| unique_order_id | string unique | ORD/YYYY-YY/NNNN financial year |
| broker_id | bigint | FK users (broker role) |
| brand_id | bigint | FK brand_management |
| dealer_id | bigint | FK dealer_management |
| order_date | date | |
| delivery_address | text | |
| payment_status | enum | unpaid, paid, partial |
| partial_paid_amount | decimal nullable | required if partial |
| total_order_amount | decimal | sum of items |
| grand_total | decimal | |
| status | tinyint default 1 | |
| timestamps + soft deletes | | |

### order_items
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | bigint | FK order_management |
| product_id | bigint | FK products |
| qty | unsigned int | bags/ton ordered |
| unit_price | decimal | |
| total_price | decimal | qty * unit_price |
| timestamps | | NO soft deletes |

### dispatch_management
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | bigint | |
| order_item_id | bigint | |
| product_id | bigint | |
| no_of_bags | unsigned int | |
| dispatch_date | date | |
| transport_id | bigint | FK users (transporter) |
| truck_number | string | from trucks table |
| driver_contact | string | |
| timestamps + soft deletes | | |

## What Exists — Match This Behaviour

### Sub-module A: Soda / Order

**List** (order.index)
- DataTable columns: Sr No, Order ID, Broker, Brand, Dealer, Order Date, Payment Status, Action
- Filters: Brand, Broker (broker users only see own rows; no broker filter)
- Search on Order ID
- Actions: Dispatch (with sequential check), Edit, Delete (with delete-check AJAX)
- Add button → create page

**Create/Edit**
- Header: Order ID (readonly), Broker, Brand, Dealer (AJAX /get-dealers), Order Date, Delivery Address
- Dynamic product rows: product_id[], qty[], price[], optional item_id[] on edit
- Last price AJAX: GET /order-last-price?dealer_id&product_id
- Payment: radio unpaid/paid/partial + partial_paid_amount
- Store/update recalculates totals; edit guards when dispatches exist

**Delete**
- Block if any item has dispatches; use delete-check endpoint + SweetAlert detail popup
- Bulk delete endpoint exists

**Order ID generation**
- ORD/{FY}/{4-digit-seq}, FY = April–March

### Sub-module B: Dispatch

**List** (dispatch.index)
- Filter orders that have dispatches
- Columns: Sr No, Order ID (link + Complete chip), Product, Bags/Ton, Dealer, Dispatch Date, Transport, Truck, Driver Contact, Action (View History)
- Server-side DataTables

**History** (dispatch.orderHistory)
- Shows order + dealer header, pending summary cards per item, blocked banner if prior dealer order incomplete
- Table of all dispatch entries; Add modal; Edit modal
- Sequential dispatch enforced on store + UI block

**Add/Edit dispatch modal fields**
- order_item_id (pending only), no_of_bags (<= pending), dispatch_date, transport_id, truck_number (AJAX trucks), driver_contact
- GET /dispatch/transporter-trucks/{transporter} → trucks + phone

## Business Rules — MUST IMPLEMENT

1. Sequential dispatch per dealer by order id ASC — no dispatch on order N until orders 1..N-1 fully dispatched
2. no_of_bags cannot exceed pending qty per order_item
3. Cannot delete order with any dispatch history
4. Cannot reduce qty below dispatched qty; cannot remove dispatched line items; cannot change broker/brand/dealer after dispatch started
5. Broker role: scoped list + locked broker on forms
6. isFullyDispatched(): every item sum(bags) >= qty

## Permissions
add-order, edit-order, delete-order, add-dispatch, edit-dispatch, delete-dispatch, view-dispatch-pending-payments

Seed via: `php artisan db:seed --class=Database\\Seeders\\SalesPermissionSeeder`

## Routes
See Sales_Module_Requirements.md technical section for full route list.
Critical: register dispatch-check and delete-check BEFORE resource routes; dispatch/order/{order} BEFORE dispatch resource.
Dispatch resource uses: `->middleware('role_or_permission:add-dispatch|edit-dispatch|delete-dispatch')`.
Order list/create protected in `OrderManagementController::__construct()`.

## Design Instructions — IMPORTANT

Do NOT generate generic Bootstrap admin templates.

### Step 1 — Read layout
resources/views/layouts/main.blade.php

### Step 2 — Read existing Sales views (PRIMARY design reference)
- resources/views/order_management/index.blade.php
- resources/views/order_management/create.blade.php
- resources/views/order_management/edit.blade.php
- resources/views/dispatch_management/index.blade.php
- resources/views/dispatch_management/history.blade.php

### Step 3 — Match exactly
- Same card headers, filter bars, DataTables dom 'lrtip', Select2, action dropdowns
- Same SweetAlert popups for dispatch-blocked and delete-blocked (dbp-*, od-* classes)
- Same dispatch history UI (dh-*, pd-* sections, progress bars, completion banner)
- Same payment badges on order list
- Sidebar: Sales submenu with Soda/Order and Dispatch links (see layouts/sidebar.blade.php)

### Step 4 — Controllers to mirror
- app/Http/Controllers/OrderManagementController.php
- app/Http/Controllers/DispatchManagementController.php
- app/Models/OrderManagement.php, OrderItem.php, DispatchManagement.php

## Dependencies (already in project)
- users (broker, transporter roles)
- brand_management, dealer_management, products, trucks
- DealerManagementController::getDealersByBrokerBrand for /get-dealers

## Additional Notes
- Order list Grand Total column is intentionally hidden in current UI
- Dispatch delete button in history view is commented out but backend destroy exists
- Order item soft deletes are disabled — hard delete rows when removed on edit
- Use permission middleware on POST/PUT/DELETE routes; use `role_or_permission` on dispatch GET resource routes
- Order index/create: controller middleware `permission:add-order`; edit: `permission:edit-order`
- UI: `canAny` for Dispatch buttons when any dispatch permission exists; `@can('add-dispatch')` for Add Dispatch modal only
- Run `SalesPermissionSeeder` so `add-dispatch` exists in DB (was missing before Jun 2026 update)
- Use `App\Support\SalesScope` for all order/dispatch queries and `authorizeOrderAccess` before single-record actions
- Roles: super admin/admin/staff = all data; broker = broker_id scope; dealer = dealer.user_id scope
- Flash messages: session success/error via existing layout helpers (show_success, show_error)
```
