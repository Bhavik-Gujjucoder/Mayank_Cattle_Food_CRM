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

## 🔐 Permissions (Spatie)

| Permission | Used for |
|---|---|
| `add-order` | Create order, route `order.store` |
| `edit-order` | Edit order, route `order.update` |
| `delete-order` | Delete order, bulk delete, route `order.destroy` / `order.bulkDelete` |
| `add-dispatch` | Add dispatch entry (modal on history page), route `dispatch.store` |
| `edit-dispatch` | Edit dispatch entry (modal on history page), route `dispatch.update` |
| `delete-dispatch` | Delete dispatch (route exists; UI delete button is currently commented out in history view) |

**Role behaviour:**
- **Broker role:** On order list, only sees own orders (`broker_id = auth id`). Broker filter hidden. On create/edit, broker dropdown is pre-selected and disabled.
- **Other roles:** See all orders; can filter by broker.

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
- **Page-level action:** **Add Soda/Order** → `order.create` (permission `add-order`)
- **Row actions (⋮ dropdown):**
  - **Dispatch** — always shown in action builder; runs sequential dispatch check then navigates to `dispatch.orderHistory`
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
- **Row action:** View History → `dispatch.orderHistory`
- *(Edit from list is commented out)*

### 2. Dispatch History Page (`/dispatch/order/{order}`)

- **Page title:** Dispatch History
- **Header:** Order ID + dealer name; **Add New Dispatch** button (modal) if not blocked and `add-dispatch`
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

## 🛠️ Laravel 12 Technical Requirements

### Architecture
- Laravel 12 MVC
- Eloquent ORM + `SoftDeletes` on `OrderManagement`, `DispatchManagement` (not on `OrderItem`)
- Controllers: `OrderManagementController`, `DispatchManagementController`
- Yajra DataTables for list pages (server-side)
- Permission middleware on mutating routes
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

```php
// Order / Soda-Order
Route::get('order-last-price', ...)->name('order.lastItemPrice');
Route::get('order/{order}/dispatch-check', ...)->name('order.dispatchCheck');
Route::get('order/{order}/delete-check', ...)->name('order.deleteCheck');
Route::resource('order', OrderManagementController::class)->except(['store','update','destroy']);
Route::post('order', ...)->middleware('permission:add-order');
Route::match(['put','patch'], 'order/{order}', ...)->middleware('permission:edit-order');
Route::delete('order/{order}', ...)->middleware('permission:delete-order');
Route::post('order-bulk-delete', ...)->middleware('permission:delete-order');

// Dispatch — orderHistory BEFORE resource
Route::get('dispatch/order/{order}', ...)->name('dispatch.orderHistory');
Route::get('dispatch/transporter-trucks/{transporter}', ...)->name('dispatch.transporterTrucks');
Route::resource('dispatch', DispatchManagementController::class)->except(['store','update','destroy']);
Route::post('dispatch', ...)->middleware('permission:add-dispatch');
Route::match(['put','patch'], 'dispatch/{dispatch}', ...)->middleware('permission:edit-dispatch');
Route::delete('dispatch/{dispatch}', ...)->middleware('permission:delete-dispatch');

// Shared (dealer loading on order form)
Route::get('/get-dealers', ...)->name('get.dealers');
```

### Key implementation files

| File | Responsibility |
|---|---|
| `OrderManagementController` | index (DataTables), create, store, edit, update, destroy, bulkDelete, lastItemPrice, deleteCheck, checkDispatchEligibility, validateOrder() |
| `DispatchManagementController` | index (DataTables), orderHistory, store, update, destroy, getTrucksByTransporter |

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
add-order, edit-order, delete-order, add-dispatch, edit-dispatch, delete-dispatch

## Routes
See Sales_Module_Requirements.md technical section for full route list.
Critical: register dispatch-check and delete-check BEFORE resource routes; dispatch/order/{order} BEFORE dispatch resource.

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
- Use permission middleware on POST/PUT/DELETE routes
- Flash messages: session success/error via existing layout helpers (show_success, show_error)
```
