# Dashboard Module — Requirements & AI Prompt
> Laravel 12 | Bootstrap 5 | Prepared for AI-assisted code generation

---

## 📁 Module Overview

**Module Name:** Dashboard (Home)
**Framework:** Laravel 12
**UI:** Bootstrap 5, Tabler Icons (`ti ti-*`), project custom CSS (`public/assets/css/style.css`)
**Layout:** `resources/views/layouts/main.blade.php`
**View:** `resources/views/dashboard.blade.php`
**Controller:** `App\Http\Controllers\HomeController@dashboard`

The Dashboard is the **default landing page** after successful login. It is not a CRUD module — it aggregates read-only KPI cards and “recent activity” lists from existing Sales and User/Dealer data.

**Post-login redirects (all use `route('dashboard')`):**
- Email login → OTP verify → dashboard
- Mobile login (Dealer role only) → dashboard
- Registration, email verification, password confirmation → dashboard

**Sidebar:** Top-level **Dashboard** link (`ti ti-layout-2`) — always visible (not wrapped in `@can`).

---

## 🔐 Permissions (Spatie)

Dashboard widgets are shown/hidden with `@can('permission-name')` in the Blade view. Permissions are stored in the `permissions` table; dashboard-specific rows use `is_dashboard = 1` (loaded in `RoleController` for role create/edit — **UI block is currently commented out** in `roles/edit.blade.php` and `roles/create.blade.php`).

| Permission | Widget / Section |
|---|---|
| `total-dealers` | KPI card — Total Dealers |
| `total-brokers` | KPI card — Total Broker |
| `total-soda-order` | KPI card — Total Soda/Order |
| `total-dispatch-request` | KPI card — Total Dispatch request |
| `recent-dealers` | Recent Dealers list (max 5) |
| `recent-orders` | Recent Soda/Orders list (max 5) |
| `recent-dispatch-request` | Recent Dispatch Request list (max 5) |

**Commented / disabled in current UI (preserve in code for future enablement):**

| Permission | Intended widget |
|---|---|
| `total-orders` | Total Orders chart card |
| `revenue` | Revenue chart card |
| `recent-broker` | Recent Broker list |
| `recent-transporter` | Recent Transporter list |

**Note:** Assign the active permissions above to roles via the Permissions module or database seeders. Without them, the dashboard shows only the welcome banner.

---

## 🗄️ Data Sources (Read-Only)

The Dashboard does **not** own database tables. It reads from:

| Model | Table | Dashboard usage |
|---|---|---|
| `User` | `users` | Logged-in user, broker/transporter lists (transporter list loaded but UI commented) |
| `DealerManagement` | `dealer_management` | Recent dealers, total dealers count |
| `OrderManagement` | `order_management` | Recent orders, total soda/order count |
| `DispatchManagement` | `dispatch_management` | Recent dispatch requests, total dispatch count |
| `CityManagement` | `city_management` | Dealer city label (`city_name`) |

**Dealer filter for list data:** Only dealers whose linked `users.status = 1` (active user).

```php
DealerManagement::whereHas('user', fn ($q) => $q->where('status', 1))
    ->orderBy('id', 'desc')->get();
```

**Brokers / transporters:** Users with Spatie role `broker` or `transporter`, ordered by `id` desc (used only if recent-broker/transporter sections are enabled).

---

## 🖥️ Module: Dashboard Page

### Route & Access

| Item | Value |
|---|---|
| URL | `/` (inside authenticated route group) |
| Route name | `dashboard` |
| Method | `GET` |
| Middleware | `auth`, `verified` |
| Controller | `HomeController@dashboard` |

Unauthenticated visitors hitting `/` see the login page (separate route outside the auth group).

### Page Title

Dynamic: `{Role} Dashboard` — e.g. `Admin Dashboard`, `Broker Dashboard`, `Dealer Dashboard`.

Derived from: `ucfirst(auth()->user()->roles->first()->name)`.

---

### Screen Layout (top to bottom)

#### 1. Welcome Banner

- **Background:** `welcome-wrap` (full-width colored strip)
- **Heading:** `Welcome Back, {user_name}` (white text)
- **Subtitle:** empty `<p>` placeholder

#### 2. KPI Summary Row (`row detials-gc-user`)

Four optional stat cards in a responsive grid (`col-xl-3 col-sm-6`). Each card is wrapped in `@can(...)`:

| Card | Permission | Value variable | Label | Icon |
|---|---|---|---|---|
| Total Dealers | `total-dealers` | `$total_dealers` | Total Dealers | `ti ti-medal` |
| Total Broker | `total-brokers` | `$total_broker` | Total Broker | `ti ti-user-up` |
| Total Soda/Order | `total-soda-order` | `$total_soda_order` | Total Soda/Order | `ti ti-user-star` |
| Total Dispatch request | `total-dispatch-request` | `$total_dispatch_order` | Total Dispatch request | `ti ti-businessplan` |

**Card CSS classes (for theming):** `total-dealers`, `total-broker`, `total-soda-order`, `total-dispatch-request`.

#### 3. Charts Row (disabled)

Second row exists but chart cards for **Total Orders** and **Revenue** are **commented out** (`@can('total-orders')`, `@can('revenue')`). Placeholder revenue was `₹0`.

#### 4. Recent Activity Row

Three columns (`col-xxl-4`) when permissions allow:

##### A. Recent Dealers — `@can('recent-dealers')`

- **Header:** “Recent Dealers” + **View All** → `route('dealer.index')`
- **Items:** Up to 5 from `$dealers` (already limited in loop via `take(5)`)
- **Per item:**
  - Avatar → profile picture or `images/default-user.png`
  - Name link → `route('dealer.edit', $dealer->id)` (displays `$dealer->user->name`)
  - Subtext → `$dealer->city->city_name`

##### B. Recent Soda/Orders — `@can('recent-orders')`

- **Header:** “Recent Soda/Orders” + **View All** → `route('order.index')`
- **Empty state:** “No recent soda orders found.”
- **Items:** Up to 5 from `$soda_order`, sorted by `created_at` desc in the view
- **Per item:**
  - Dealer name link → `route('order.edit', $order->id)`
  - Order ID (`unique_order_id`) + order date (`d M Y`)
  - Amount column exists but is **commented out** in template

##### C. Recent Dispatch Request — `@can('recent-dispatch-request')`

- **Header:** “Recent Dispatch Request” + **View All** → `route('dispatch.index')`
- **Empty state:** “No recent dispatch requests found.”
- **Items:** Up to 5 from `$dispatch_order`, sorted by `created_at` desc in the view
- **Per item:**
  - Product name + bags/ton: `{product.name} (bag/ton {no_of_bags})`
  - Link → `route('dispatch.orderHistory', $dispatch_order->order_id)`
  - Subline: order `unique_order_id` + dispatch date (`d M Y`)

##### D. Recent Broker / Transporter (disabled)

Blade blocks for `@can('recent-broker')` and `@can('recent-transporter')` are **fully commented**. Data (`$brokers`, `$transporters`) is still loaded in the controller for easy re-enable.

---

## ⚙️ Business Rules

### Role-Based Data Scoping

The logged-in user’s **first Spatie role name** drives filtering for **orders** and **dispatches** (KPI totals and recent lists). **Dealers, brokers, and transporter aggregates are not role-scoped** in the current implementation — all active dealers / all brokers are counted and listed if the user has permission.

| Role | Recent Soda/Orders (`$soda_order`) | Total Soda/Order (`$total_soda_order`) |
|---|---|---|
| `broker` | `order_management.broker_id = auth()->id()`, latest 5 | Full count for that broker |
| `dealer` | Orders where `dealer_management.user_id = auth()->id()` (via `whereHas('dealer')`), latest 5 | Full count for that dealer |
| **Other** (admin, super admin, staff, etc.) | Latest 5 orders globally | Global count |

| Role | Recent Dispatch (`$dispatch_order`) | Total Dispatch (`$total_dispatch_order`) |
|---|---|---|
| `broker` | Dispatches whose order has `broker_id = auth()->id()`, latest 5 | **Current code:** count of the 5-item collection (see notes) |
| `dealer` | Dispatches whose order belongs to dealer’s `user_id`, latest 5 | **Current code:** count of the 5-item collection |
| **Other** | Latest 5 dispatches globally | **Current code:** count of the 5-item collection |

**Recommended fix for dispatch total (admin/broker/dealer):** Use a dedicated `count()` query (same filters as list, without `take(5)`), matching how `$total_soda_order` is implemented.

### Dealer ID vs User ID

`order_management.dealer_id` references `dealer_management.id`, **not** `users.id`. Dealer scoping must use:

```php
OrderManagement::whereHas('dealer', fn ($q) => $q->where('user_id', $loginUser->id))
```

### Relationships Used in View

- Order → `dealer.user.name`, `unique_order_id`, `order_date`
- Dispatch → `product.name`, `no_of_bags`, `order.unique_order_id`, `dispatch_date`, `order_id`

---

## 🛠️ Laravel Technical Requirements

### Controller: `HomeController@dashboard`

**File:** `app/Http/Controllers/HomeController.php`

**Variables passed to view:**

| Variable | Description |
|---|---|
| `login_user` | `auth()->user()` |
| `role` | First role name string or `''` |
| `user_name` | User display name |
| `page_title` | `{Role} Dashboard` |
| `dealers` | Active-user dealers collection |
| `brokers` | Users with role `broker` |
| `transporters` | Users with role `transporter` |
| `soda_order` | Recent orders (role-scoped, max 5) |
| `total_dealers` | `$dealers->count()` |
| `total_broker` | `$brokers->count()` |
| `total_soda_order` | Role-scoped full count |
| `dispatch_order` | Recent dispatches (role-scoped, max 5) |
| `total_dispatch_order` | See business rules — should be full count |

### Routes

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');
    // ... other authenticated routes
});
```

**Guest route (login):**

```php
Route::get('/', function () {
    return view('auth.login');
});
```

Registered **before** the auth group; authenticated users use the inner `/` → dashboard definition.

### Models & Relationships (referenced)

```
OrderManagement
  - belongsTo(User, broker_id) → broker
  - belongsTo(DealerManagement, dealer_id) → dealer
  - hasMany(DispatchManagement) → dispatches

DispatchManagement
  - belongsTo(OrderManagement, order_id) → order
  - belongsTo(Product, product_id) → product

DealerManagement
  - belongsTo(User, user_id) → user
  - hasOne(CityManagement, id, city_id) → city

User
  - Spatie HasRoles
```

### Blade Structure

```blade
@extends('layouts.main')
@section('content')
@section('title')
    <h3>{{ $page_title }}</h3>
@endsection
{{-- welcome-wrap, KPI row, recent lists --}}
@endsection
@section('script')
@endsection
```

No page-specific JavaScript in `@section('script')` (charts would go here if enabled).

---

## 🎨 Design Requirements

### Design Philosophy

- **Bootstrap 5** — match existing CRM theme (same as Sales, Dealer, Supplier modules in this project)
- **Do NOT** introduce Tailwind or new UI libraries for Dashboard
- Reuse card patterns: `card flex-fill`, `card-header`, `card-body`, `btn btn-light btn-md`
- KPI row wrapper: `detials-gc-user` (project-specific class)
- Recent list items: `dashboard-card` wrapper inside `recent-cards` panel

### How to Capture Existing Design (Instructions for AI)

Before changing Dashboard views:

1. Read `resources/views/layouts/main.blade.php` (navbar, sidebar, content area)
2. Read `resources/views/dashboard.blade.php` (primary reference)
3. Read `public/assets/css/style.css` — search `.detials-gc-user`, `.welcome-wrap`, `.total-dealers`, etc.
4. Match Tabler icon classes and avatar/image patterns from Dealer or User modules

### Welcome Banner

- Class `welcome-wrap mb-4`
- White heading text on themed background (defined in CSS)

---

## 📋 Role Management Integration

`RoleController@create` and `@edit` load:

```php
Permission::where('deleted_at', null)->where('is_dashboard', 1)->get();
```

Passed as `$dashboard_permissions` to role views. The checkbox section **“Dashboard Permissions”** is commented out in Blade — permissions must be assigned via Permissions CRUD or direct DB until UI is re-enabled.

---

## 🤖 AI Generation Prompt

> Copy everything below this line and share it with an AI agent.

---

```
You are a Laravel 12 developer. Document and maintain the "Dashboard" (home) module for my cattle feed manufacturing CRM.

## Context
After login, users land on a read-only dashboard showing KPI cards and recent dealers/orders/dispatches. Visibility is controlled by Spatie permissions. Order and dispatch data is filtered by role (broker, dealer, or global).

## Tech Stack
- Laravel 12
- Bootstrap 5 (match existing project — NOT Tailwind)
- Blade: resources/views/dashboard.blade.php
- Layout: resources/views/layouts/main.blade.php
- Spatie Laravel Permission (@can in Blade)
- Tabler Icons (ti ti-*)

## What Exists — Do Not Break

### Route
GET / → HomeController@dashboard, name dashboard, middleware auth + verified

### Controller (HomeController@dashboard)
- Load login user, role, page_title
- dealers: DealerManagement with active user (status=1), desc id
- brokers: User with role broker; transporters: role transporter
- soda_order: latest 5, scoped by broker_id OR dealer.user_id OR global
- total_soda_order: full count with same scope as soda_order
- dispatch_order: latest 5, scoped via order.broker_id OR order.dealer.user_id OR global
- total_dispatch_order: should use full count query (fix if still using collection count)

### Blade widgets (@can gated)
KPI: total-dealers, total-brokers, total-soda-order, total-dispatch-request
Lists: recent-dealers → dealer.index, recent-orders → order.index, recent-dispatch-request → dispatch.index
Welcome: Welcome Back, {user_name}

### Disabled (commented) — do not delete without product approval
Chart cards: total-orders, revenue
Lists: recent-broker, recent-transporter

## Permissions to seed / assign
total-dealers, total-brokers, total-soda-order, total-dispatch-request,
recent-dealers, recent-orders, recent-dispatch-request
(Set is_dashboard = 1 on permissions table if using RoleController dashboard_permissions)

## Role scoping rules — MUST IMPLEMENT
- broker: orders where broker_id = auth id; dispatches on those orders
- dealer: orders where dealer.user_id = auth id; dispatches on those orders
- others: global latest 5 + global counts
- dealer_id on orders is dealer_management.id, NOT users.id

## Links from dashboard
- dealer.edit for dealer name
- order.edit for order lines
- dispatch.orderHistory for dispatch lines
- View All buttons to dealer.index, order.index, dispatch.index

## Design Instructions — IMPORTANT
1. Read resources/views/layouts/main.blade.php
2. Read resources/views/dashboard.blade.php as PRIMARY reference
3. Match Bootstrap cards, welcome-wrap, detials-gc-user KPI row, recent-cards panels
4. Sidebar: Dashboard menu item with ti ti-layout-2 (see layouts/sidebar.blade.php)
5. Do NOT add DataTables or modals to dashboard — static lists only

## Files to mirror
- app/Http/Controllers/HomeController.php
- resources/views/dashboard.blade.php
- routes/web.php (dashboard route inside auth group)

## Known gaps / improvements (optional tasks)
1. total_dispatch_order for non-dealer/broker roles should use DispatchManagement::count() not take(5) collection count
2. Re-enable Dashboard Permissions checkboxes in roles/create.blade.php and roles/edit.blade.php
3. Fix HTML typo in recent orders: <spa> → <span> for order id
4. Role-scope dealer/broker KPI counts if business requires brokers to see only their dealers
5. Re-enable revenue/orders charts when backend metrics exist
```

---

## Additional Notes

- **Email login flow:** Password validated → OTP sent → after `OtpController@verify`, redirect to dashboard.
- **Mobile login:** Only users with role `dealer`; uses `phone_no` + `Hash::check` (not `Auth::validate`, because dealer email may be null).
- **Tests:** Feature auth tests assert redirect to `route('dashboard')`.
- **Legacy files:** `resources/views/1dashboard.blade.php`, `public/dashboard.html` — not used by the Laravel route; safe to ignore for module behaviour.
- Dashboard does not include Raw Material, Supplier, or Machine metrics — extend with new `@can` widgets and controller queries if product requires them later.
