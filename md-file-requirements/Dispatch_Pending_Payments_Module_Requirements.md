# Dispatch Pending Payments Module — Requirements & AI Prompt
> Laravel 12 | Bootstrap 5 | Prepared for AI-assisted code generation

---

## 📁 Module Overview

**Module Name:** Dispatch Pending Payments (Sales — Dispatch Pending Payments)
**Report title (UI):** Sales — Dispatch Pending Payments
**Framework:** Laravel 12
**UI:** Bootstrap 5 (mobile-first responsive grid), jQuery, Tabler Icons (`ti ti-*`), project custom CSS (`public/assets/css/style.css`)
**Layout:** `resources/views/layouts/main.blade.php`

**Purpose:** Read-only **exception / aging report** for **dispatched goods whose dispatch-level payment is still unpaid**. Used by sales/admin teams for **collection follow-up** after delivery — not for scheduling new dispatches.

**Relationship to Sales module:**
- Depends on existing **Soda / Order** and **Dispatch** sub-modules (`Sales_Module_Requirements.md`).
- Payment tracked **per dispatch row** on `dispatch_management.status` (`0` = Unpaid, `1` = Paid).
- Order-level `order_management.payment_status` (`unpaid` / `paid` / `partial`) is **separate**; this report uses **dispatch-level** unpaid rows only.

**Sidebar:** Nested under **Sales** → **Dispatch Pending Payments** (new link, alongside Soda / Order and Dispatch).

**Type identifier (routes/comments):**
- `delivery-pending-payments` → Report routes (`delivery-pending-payments.*` or `deliveryPendingPayments.*`)

**Module type:** Report only — **no CRUD**, no new master tables. Mark dispatch as Paid/Unpaid on existing Dispatch screens (`dispatch.orderHistory`, dashboard dispatch modal).

**Implemented (reference):**

| Layer | Path |
|---|---|
| Controller | `app/Http/Controllers/DeliveryPendingPaymentsController.php` |
| Service | `app/Services/DeliveryPendingPaymentsReportService.php` |
| Excel | `app/Exports/DeliveryPendingPaymentsExport.php` |
| Views | `resources/views/delivery_pending_payments/` (+ `partials/`) |
| Seeder | `database/seeders/DeliveryPendingPaymentsPermissionSeeder.php` |
| Routes | `delivery-pending-payments.index`, `delivery-pending-payments.export` |
| Dashboard | `HomeController@index`, `resources/views/dashboard.blade.php`, `resources/views/dashboard/partials/delivery_pending_payments_widget.blade.php` |

**Display vs internal naming:**
- **UI labels (user-facing):** **Dispatch Pending Payments** (sidebar, report page, Excel title, dashboard widget).
- **Internal identifiers (unchanged):** routes `delivery-pending-payments.*`, permission `view-dispatch-pending-payments`, view folder `delivery_pending_payments/`, CSS root `.delivery-pending-payments-module`, PHP classes `DeliveryPendingPayments*`.

---

## 🔐 Permissions (Spatie)

| Permission | Used for |
|---|---|
| `view-dispatch-pending-payments` | View report page, route `delivery-pending-payments.index` |

**Suggested role behaviour:**
- **Admin / accounts / sales manager:** Full report, all brands.
- **Broker role (optional future):** Filter to orders where `order_management.broker_id = auth()->id()` (mirror order list scoping — confirm with stakeholder before implementing).

**Seed / assign:** Run `database/seeders/DeliveryPendingPaymentsPermissionSeeder.php` (creates permission; assigns to `admin` and `super admin`). Attach to other roles via Permissions module as needed.

---

## 🗄️ Data Sources (Read-Only — No New Tables)

This module does **not** create database tables. It aggregates:

| Model | Table | Report usage |
|---|---|---|
| `DispatchManagement` | `dispatch_management` | **Primary driver** — unpaid rows (`status = 0`), `dispatch_date` for aging |
| `OrderManagement` | `order_management` | Order ID, brand, dealer, broker |
| `DealerManagement` | `dealer_management` | Dealer display name, `city_id`, `brand_id` |
| `BrandManagement` | `brand_management` | Brand column grouping (Mayank, Ajay, Mahakal, etc.) |
| `CityManagement` | `city_management` | City name (`city_name`) |
| `User` | `users` | Dealer linked user name (`dealer.user.name`) |

### `dispatch_management.status` (dispatch payment)

| Value | Constant | Meaning |
|---|---|---|
| `0` | `DispatchManagement::STATUS_UNPAID` | Unpaid — **included** in this report |
| `1` | `DispatchManagement::STATUS_PAID` | Paid — **excluded** from day-count list |

Migration: `database/migrations/2026_06_01_000002_add_status_to_dispatch_management_table.php`

**UI for editing status:** `resources/views/dispatch_management/partials/status-field.blade.php` (radio Unpaid / Paid on add/edit dispatch modals).

### Related columns (reference)

**`dispatch_management` (relevant fields):**

| Column | Notes |
|---|---|
| `order_id` | FK → order |
| `dispatch_date` | Aging anchor date |
| `status` | `0` unpaid, `1` paid |
| `deleted_at` | Soft delete — exclude trashed dispatches |

**`order_management` (relevant fields):**

| Column | Notes |
|---|---|
| `unique_order_id` | Display as Order ID (e.g. `ORD/2025-26/0001`) |
| `brand_id` | Brand for grouping (prefer **order** brand; fallback dealer brand if needed) |
| `dealer_id` | Dealer for city + name |
| `broker_id` | Optional broker scoping |
| `deleted_at` | Exclude trashed orders |

**`dealer_management`:**

| Column | Notes |
|---|---|
| `city_id` | FK → `city_management` |
| `brand_id` | Dealer’s brand (secondary if order brand differs) |
| `user_id` | Linked user for display name |
| `firm_shop_name` | Fallback display name |

**Dealer display name (match order list):**
`dealer.user.name ?? dealer.firm_shop_name ?? '—'`

---

## ⚙️ Business Rules

### 1. Inclusion — which orders appear

An **order** is included in the report if and only if:

```text
EXISTS at least one dispatch_management row WHERE:
  order_id = order.id
  AND status = 0 (Unpaid)
  AND deleted_at IS NULL
```

> Spreadsheet note: *“only show orders who has at least one dispatch payment is pending.”*

Orders with **no dispatches** or **all dispatches paid** are **omitted**.

### 2. Pending Payment Days (per order)

For each included order, collect **every unpaid dispatch** on that order.

**Per unpaid dispatch:**

```text
pending_days = whole days from dispatch_date to today (application timezone / Asia-Kolkata if configured)
```

Use **calendar date** comparison (start of day to start of day), consistent with ERP date fields.

**Display formats (column label: `Pending Payment Days` — not “Dispatch Pending Payment Days”):**

| Context | Format | Example |
|---|---|---|
| **Screen (browser)** | Day numbers only, separated by ` - `; **hover** each number for tooltip *Dispatch date: {d M Y}* | `15 - 13 - 9` (hover → dates) |
| **Print / PDF** | Same wrapped string as Excel; **word-wrap** inside cell (`pending_days_label`) | `10 (22 May 2026) - 7 (25 May 2026) - 0 (01 Jun 2026)` |
| **Excel** | `pending_days_label` in column D with **wrap text** | Same as print row above |

- Sort unpaid dispatch entries **descending** by days (highest first).
- Built by `DeliveryPendingPaymentsReportService::formatPendingDaysLabel($pending_days_items)`.
- Each item in `pending_days_items[]`: `{ days: int, dispatch_date: string }` where `dispatch_date` format is `d M Y` (e.g. `22 May 2026`).
- Partial: `resources/views/delivery_pending_payments/partials/pending-days-cell.blade.php` (`.dpp-days-screen` vs `.dpp-days-print`).

**Footnote (show on report page):**

> **Pending Payment Days:** Days from dispatch date to today. On screen, hover a day count for dispatch date; on print/PDF/Excel shown as *days (dispatch date)*.

### 3. Hierarchy & sort order

Report structure:

```text
Brand → City → Dealer → Order → [Pending Payment Days]
```

| Level | Source | Sort |
|---|---|---|
| Brand | `order.brand` (or `brand_management` via `order.brand_id`) | Brand name ASC (or fixed business order: Mayank, Ajay, Mahakal — configurable) |
| City | `dealer.city.city_name` | City name ASC |
| Dealer | Dealer display name | Name ASC |
| Order | `unique_order_id` | Order id ASC (creation order) |

**City column:** Repeat city name on each row under that city (tabular layout like spreadsheet), not merged cells only.

### 4. Brand section layout (HTML / print)

- **One brand per row** — full width (`col-12`), stacked vertically on **all** screen sizes (matches print/PDF; no side-by-side brands on `xl`).
- Each section: card with grey header **`{Brand} Brand`** via `formatBrandSectionTitle()` (avoids duplicate “Mayank Brand Brand” when name already ends with “Brand”).
- Table columns: **City** | **Dealer** | **Order** | **Pending Payment Days**
- Brands with no pending data: **hidden** (only brands with rows appear).
- Order column links to `dispatch.orderHistory` when user has `add-dispatch` / `edit-dispatch` / `delete-dispatch`.

### 5. Order-level payment_status — not used for filtering

Do **not** filter by `order_management.payment_status` alone. An order marked `paid` at order level could still have unpaid dispatch rows if data is inconsistent — report reflects **dispatch.status** only.

*(Optional admin note in UI: if order payment_status conflicts with dispatch unpaid rows, still show based on dispatch status.)*

### 6. Soft deletes & inactive records

- Exclude soft-deleted `dispatch_management`, `order_management`, `dealer_management`.
- Only include dispatches linked to valid orders/dealers.
- City/brand: use active records; if city missing → show `—` or `Unknown`.

### 7. Real-time / refresh

- Report is **computed on each page load** (no caching required in v1).
- Day counts change daily without data migration.

### 8. Full report vs dashboard (10+ days filter)

| Surface | Scope | Aging filter |
|---|---|---|
| **Full report** (`delivery-pending-payments.index`) | All orders with ≥ 1 unpaid dispatch | **None** — every unpaid dispatch on the order is listed |
| **Dashboard** (stat card + widget) | Same unpaid-dispatch rules, but only dispatches aged **≥ 10 calendar days** | `DeliveryPendingPaymentsReportService::DASHBOARD_MIN_PENDING_DAYS = 10` |

**Dashboard inclusion (per dispatch row on `dispatch_management`):**

```text
status = 0 (Unpaid)
AND deleted_at IS NULL
AND dispatch_date IS NOT NULL
AND pending_days >= 10   (calendar days from dispatch_date to today, start-of-day)
AND linked order not soft-deleted
```

- An order appears on the dashboard widget only if it has **≥ 1** dispatch meeting the above.
- Within each order row, **only** dispatch entries with `days >= 10` are shown (via `applyMinDaysFilter()` after `build()`).
- Service entry point: `buildForDashboard()` → `build('all', DASHBOARD_MIN_PENDING_DAYS)`.
- Summary helper: `summarize($brandSections)` returns `order_count`, `dispatch_count`, `brand_count`.

**Count semantics on dashboard:**

| UI element | Metric | Field |
|---|---|---|
| Top **summary stat card** | Number of **unpaid dispatch rows** at 10+ days | `dispatch_count` |
| Widget summary pill “Orders” | Orders with ≥ 1 qualifying dispatch | `order_count` |
| Widget summary pill “Unpaid Dispatches” | Same as stat card | `dispatch_count` |
| Widget summary pill “Brands” | Brands with ≥ 1 qualifying row | `brand_count` |

---

## 🏠 Dashboard Integration (Implemented)

The module surfaces on the main **Dashboard** (`HomeController@index`) for users with `view-dispatch-pending-payments`. It does **not** add new routes — it reuses the report service and brand-section partials.

### Layout order (top → bottom)

1. Top KPI row (Total Dealers, Broker, Soda/Order, Dispatch request, **Unpaid Dispatches (10+ days)**).
2. **Dispatch Pending Payments (10+ Days)** — full-width widget (`col-12`).
3. Recent Dealers / Recent Soda/Orders / Recent Dispatch Request (three-column row).

### 1. Summary stat card (top KPI row)

| Item | Value |
|---|---|
| File | `resources/views/dashboard.blade.php` |
| Permission | `@can('view-dispatch-pending-payments')` |
| Label | **Unpaid Dispatches (10+ days)** |
| Count | `$dpp_dashboard_summary['dispatch_count']` |
| Icon | `ti ti-report-money` on dark avatar (same card style as other KPI tiles) |

Shows a **single number** — total unpaid dispatch rows aged 10+ days (not order count).

### 2. Dashboard widget (detail preview)

| Item | Value |
|---|---|
| Partial | `resources/views/dashboard/partials/delivery_pending_payments_widget.blade.php` |
| Title | **Dispatch Pending Payments (10+ Days)** |
| Subtitle | Unpaid dispatches with 10 or more pending payment days |
| Actions | **View Full Report** → `route('delivery-pending-payments.index')` |
| Data | `$dpp_dashboard_sections`, `$dpp_dashboard_summary`, `$dpp_dashboard_min_days`, `$dpp_dashboard_can_link_order` |

**Widget body:**
- Three summary pills: **Orders** | **Unpaid Dispatches** | **Brands**.
- Scrollable brand stack (max-height ~520px) reusing `delivery_pending_payments.partials.brand-section`.
- Empty state: green check + “No unpaid dispatch payments at 10+ days.”
- Footer note: days from dispatch date to today; shown 10+ days per dispatch.

**Scoped CSS:** `dashboard.blade.php` includes `delivery_pending_payments.partials.module-responsive` plus inline styles for `.dashboard-dpp-widget` (stat pills, scroll stack, hides report-only header/footnotes).

**Order links:** `$dpp_dashboard_can_link_order` is `true` when user has any of `add-dispatch`, `edit-dispatch`, `delete-dispatch` (links to `dispatch.orderHistory` via brand-section partial).

### 3. Controller wiring (`HomeController`)

```php
$data['dpp_dashboard_sections'] = collect();
$data['dpp_dashboard_summary']  = ['order_count' => 0, 'dispatch_count' => 0, 'brand_count' => 0];
$data['dpp_dashboard_can_link_order'] = false;
$data['dpp_dashboard_min_days'] = DeliveryPendingPaymentsReportService::DASHBOARD_MIN_PENDING_DAYS;

if ($loginUser->can('view-dispatch-pending-payments')) {
    $data['dpp_dashboard_sections'] = $this->pendingPaymentsReportService->buildForDashboard();
    $data['dpp_dashboard_summary']  = $this->pendingPaymentsReportService->summarize($data['dpp_dashboard_sections']);
    $data['dpp_dashboard_can_link_order'] = $loginUser->can('add-dispatch')
        || $loginUser->can('edit-dispatch')
        || $loginUser->can('delete-dispatch');
}
```

Inject `DeliveryPendingPaymentsReportService` via constructor on `HomeController`.

### 4. Service constants & methods

| Symbol | Purpose |
|---|---|
| `DASHBOARD_MIN_PENDING_DAYS` | `10` — minimum calendar days for dashboard filter |
| `buildForDashboard()` | `build('all', DASHBOARD_MIN_PENDING_DAYS)` |
| `summarize(Collection $brandSections)` | Aggregates order / dispatch / brand counts for dashboard |
| `applyMinDaysFilter($row, $minDays)` | Keeps only `pending_days_items` where `days >= $minDays`; drops order if none remain |

---

## 📊 Optional — City Summary View (Phase 2)

A separate spreadsheet layout lists cities per brand with placeholder buckets **`1 - 2 - 3 - 4`** (e.g. `Patan - 1 - 2 - 3 - 4`).

**Status:** Business definition of buckets **not confirmed** in sample data (all cities show identical `1-2-3-4`). Implement **Phase 2** after stakeholder confirms:

| Bucket | TBD definition (examples) |
|---|---|
| `1` | Unpaid dispatches aged 1–7 days |
| `2` | Unpaid dispatches aged 8–15 days |
| `3` | Unpaid dispatches aged 16–30 days |
| `4` | Unpaid dispatches aged 31+ days |

Alternative: counts of dealers / orders per bucket per city.

**Phase 1 deliverable:** Detail report only (brand → city → dealer → order → day list).

---

## 🖥️ Module — Report Page

### Route & Access

| Item | Value |
|---|---|
| URL | `/delivery-pending-payments` (suggested) |
| Route name | `delivery-pending-payments.index` |
| Method | `GET` |
| Middleware | `auth`, `verified`, `permission:view-dispatch-pending-payments` |
| Controller | `DeliveryPendingPaymentsController@index` (new) |

### Page Title

**Sales — Dispatch Pending Payments** (or shorter: **Dispatch Pending Payments**)

Icon suggestion: `ti ti-report-money` or `ti ti-clock-exclamation`

### Screen Layout

#### 1. Page header (card header — match Sales list pattern)

- Wrap page in root class: `delivery-pending-payments-module` (for scoped responsive CSS).
- Title + short subtitle: *Unpaid dispatch payments after delivery*
- Header row: `row align-items-center g-3` (same as dispatch list).
  - Title block: `col-12 col-sm-auto me-auto`
  - Filters / actions: `col-12 col-sm-6 col-lg-4` (full width on mobile, inline on `sm+`)
- Optional filters (v1):
  - **Brand** — All / specific brand (Select2, `w-100` on mobile)
  - **City** — dependent on brand (optional v1.1)
  - **Broker** — All / specific (hidden for broker role if scoped)
- **Export Excel** — downloads `.xlsx` for current brand filter (same data as on-screen report); see [Export Excel](#export-excel) below.
- **Print** — browser print (`window.print()`); buttons stack full-width on `xs` (see Responsive section).

#### 2. Report body

```html
<div class="delivery-pending-payments-module">
  <div class="row g-3">
    @foreach ($brandSections as $section)
      @include('delivery_pending_payments.partials.brand-section', ...)
    @endforeach
  </div>
</div>
```

- Partial: `resources/views/delivery_pending_payments/partials/brand-section.blade.php`
- Each brand: `col-12` → `.dpp-brand-card` → table (`md+`) or mobile cards (`< md`).

**Empty state:** “No pending dispatch payments found.” when no unpaid dispatches exist.

#### 3. Footnotes (below tables)

Card `.dpp-footnotes-card`:

| Note | Text |
|---|---|
| Aging | Pending Payment Days: days from dispatch date to today; *days (dispatch date)* on print/PDF/Excel; hover on screen. |
| Scope | Only orders with at least one unpaid dispatch payment are listed. |

#### 4. Row actions (optional v1.1)

- **Order** cell → link to `route('dispatch.orderHistory', $order)` for users with dispatch permissions.
- Read-only users see plain text Order ID.

#### 5. Export Excel

| Item | Value |
|---|---|
| URL | `GET /delivery-pending-payments/export` |
| Route name | `delivery-pending-payments.export` |
| Middleware | `auth`, `verified`, `permission:view-dispatch-pending-payments` |
| Controller | `DeliveryPendingPaymentsController@export` |
| Export class | `App\Exports\DeliveryPendingPaymentsExport` (`FromArray`, `WithEvents`) |
| Library | Maatwebsite Excel (`maatwebsite/excel`) |

**Behaviour:**

- **Not** generated from HTML — `FromArray` + `AfterSheet` styling (avoids phantom blank rows).
- Same **data** and **`pending_days_label`** format as print/PDF.
- Respects **brand filter**: `?brand_id=all` or `?brand_id={id}`.
- Empty data → redirect to index with error flash.
- Filename: `delivery-pending-payments-{YYYY-MM-DD}.xlsx`

**Excel row structure (top to bottom):**

| Row block | Type | Notes |
|---|---|---|
| Row 1 | `title` | *Sales — Dispatch Pending Payments* (merged A:D, bold 14pt) |
| Row 2 | `subtitle` | Export timestamp (merged A:D, grey 9pt) |
| **Spacer** | `spacer-lg` | Empty row, height **16** — gap before first brand |
| Per brand | `brand` | Merged title via `formatBrandSectionTitle()` — bg `#E2E8F0` |
| | `header` | City \| Dealer \| Order \| Pending Payment Days — bg `#F1F5F9` |
| | `data` | Data rows; column D **wrap text**, top-aligned |
| **Spacer** | `spacer` | Empty row, height **10** — between brands only |
| **Spacer** | `spacer-lg` | Empty row, height **16** — gap before footnotes |
| Footnotes | `footnote` | Aging + scope (merged, italic grey) |

**Column widths:** A=14, B=22, C=22, D=52. Thin borders on brand/header/data rows.

**Pending Payment Days (column D):** `10 (22 May 2026) - 7 (25 May 2026) - 0 (01 Jun 2026)` — wraps in cell.

**UI:**

```blade
<a href="{{ route('delivery-pending-payments.export', ['brand_id' => $brandFilter]) }}"
   class="btn btn-primary">
    <i class="ti ti-file-export me-1"></i> Export Excel
</a>
```

#### 6. Print / PDF (browser print)

- `window.print()` — no separate DomPDF route in v1.
- `@media print` in `module-responsive.blade.php`: hide filters/actions; full-width stacked brands.
- **Pending Payment Days:** `pending_days_label` with **word-wrap** (same string as Excel); fixed table layout; column D ~47%; 8–9px font.
- Dates inline in print (no hover tooltips).

> **Note:** HTML/print use Blade + CSS. Excel uses `DeliveryPendingPaymentsExport` only. Register `/export` route **before** index.

---

## 🔍 Query Logic (Reference Implementation)

### Step 1 — Unpaid dispatches with relations

```php
$unpaidDispatches = DispatchManagement::query()
    ->where('status', DispatchManagement::STATUS_UNPAID)
    ->whereHas('order', fn ($q) => $q->whereNull('deleted_at'))
    ->with([
        'order:id,unique_order_id,brand_id,dealer_id,broker_id',
        'order.brand:id,name',
        'order.dealer:id,city_id,user_id,firm_shop_name',
        'order.dealer.city:id,city_name',
        'order.dealer.user:id,name',
    ])
    ->get();
```

### Step 2 — Group by order, compute day list

```php
$pendingDaysItems = $dispatches
    ->filter(fn ($d) => $d->dispatch_date !== null)
    ->map(fn ($d) => [
        'days' => max(0, (int) $d->dispatch_date->startOfDay()->diffInDays($today)),
        'dispatch_date' => $d->dispatch_date->format('d M Y'),
    ])
    ->sortByDesc('days')
    ->values();

return [
    // ...
    'pending_days_items'   => $pendingDaysItems->all(),
    'pending_days_display' => $pendingDaysItems->pluck('days')->implode(' - '),
    'pending_days_label'   => DeliveryPendingPaymentsReportService::formatPendingDaysLabel($pendingDaysItems->all()),
    'max_pending_days'     => (int) $pendingDaysItems->first()['days'],
    'days_emphasis_class'  => $this->daysEmphasisClass($maxDays), // text-success | text-warning | text-danger
];
```

**Service helpers:**

| Method | Purpose |
|---|---|
| `build(?string $brandFilter)` | Returns `Collection` of brand sections `{ brand_id, brand_name, rows[] }` |
| `formatPendingDaysLabel(array $items)` | `days (date) - days (date)` for Excel/print |
| `formatBrandSectionTitle(string $name)` | Section header without duplicate “Brand” |

### Step 3 — Nest for view

Group `$byOrder` by `brand_id`, then `city_name`, then sort dealer/order as per Business Rules §3.

---

## 🎨 Design Requirements

### Design philosophy

- **Do NOT** introduce a new UI framework.
- Match existing Sales / Dashboard reports: Bootstrap 5 cards, `custom-table`, `cls-cardhed-part` header pattern.
- Read `resources/views/dispatch_management/index.blade.php` and `resources/views/order_management/index.blade.php` for header/filter patterns.

### Status / emphasis (implemented on screen)

| Max days in row | CSS class on pending days cell |
|---|---|
| 0–7 | `text-success` |
| 8–15 | `text-warning` |
| 16+ | `text-danger` |

Applied via `days_emphasis_class` on `.dpp-days-value` (screen + print inherit color).

### Sidebar menu

Under **Sales** submenu (`resources/views/layouts/sidebar.blade.php`):

```blade
@can('view-dispatch-pending-payments')
<li>
    <a href="{{ route('delivery-pending-payments.index') }}"
       class="@if (request()->routeIs('delivery-pending-payments.*')) active @endif">
        <span>Dispatch Pending Payments</span>
    </a>
</li>
@endcan
```

Extend `@canany` on Sales parent menu to include `view-dispatch-pending-payments`.

---

## 📱 Responsive Requirements

Mobile-first layout aligned with the rest of the CRM (Bootstrap 5 breakpoints, same patterns as Dispatch / Raw Material modules). **No horizontal page overflow** on phones; tables must remain readable without zooming.

### Breakpoints (Bootstrap 5 — project standard)

| Breakpoint | Min width | Report behaviour |
|---|---|---|
| `xs` | &lt; 576px | Single column; mobile card list; full-width filters/buttons |
| `sm` | ≥ 576px | Header filters may sit inline; still single-column brand blocks |
| `md` | ≥ 768px | **Table view** inside `table-responsive` (horizontal scroll if needed) |
| `lg` | ≥ 992px | Same as `md`; header filter bar matches dispatch index |
| `xl` | ≥ 1200px | **One brand per row** (`col-12`) — same as other breakpoints |

### Page wrapper & partial

| Item | Path |
|---|---|
| Root CSS scope | `.delivery-pending-payments-module` on main `@section('content')` wrapper |
| Responsive styles partial | `resources/views/delivery_pending_payments/partials/module-responsive.blade.php` |

Include partial at bottom of `index.blade.php` (same pattern as Raw Material):

```blade
@include('delivery_pending_payments.partials.module-responsive')
```

### 1. Page header — responsive

- Use `row align-items-center g-3` in `card-header` (mirror `dispatch_management/index.blade.php`).
- On **`max-width: 991.98px`**: filter column(s) go **full width** (`col-12`); Select2 dropdowns `width: 100%`.
- On **`max-width: 575.98px`**: optional Print/Export buttons `width: 100%`; stack with `gap: 0.5rem`.
- Page title: allow wrap; reduce subtitle font on small screens if needed (`small` class).

### 2. Brand sections — responsive

- Each brand in **`col-12` only** (full width at all breakpoints — no `col-xl-6`).
- Card `.dpp-brand-card` with `.dpp-brand-card-header`; **no `h-100`** (prevents empty stretched area below short tables).
- Partial: `partials/brand-section.blade.php`, `partials/pending-days-cell.blade.php`.

### 3. Table view — `md` and up (`d-none d-md-block`)

Wrap every brand table in:

```html
<div class="table-responsive custom-table">
  <table class="table custom-table mb-0 dpp-report-table">...</table>
</div>
```

| Rule | Detail |
|---|---|
| Min table width | `min-width: 640px` on `.dpp-report-table` inside scroll container |
| Scroll | Horizontal scroll **inside** `table-responsive` only — not the whole page |
| Last column | `.dpp-col-days` ~44% width; `word-break: break-word`; print uses wrapped `pending_days_label` |
| Sticky header (optional) | `thead` sticky within scroll area on tall tables — nice-to-have, not required v1 |
| Column widths | City ~15%, Dealer ~25%, Order ~25%, Days ~35% (approximate; fluid) |

### 4. Mobile card list — below `md` (`d-md-none`)

Replace table with **stacked cards** per order row (one card = one report row):

```html
<div class="dpp-mobile-item border-bottom p-3">
  <div class="dpp-mobile-row">
    <span class="dpp-mobile-label">City</span>
    <span class="dpp-mobile-value">{{ $row['city_name'] }}</span>
  </div>
  <div class="dpp-mobile-row">
    <span class="dpp-mobile-label">Dealer</span>
    <span class="dpp-mobile-value">{{ $row['dealer_name'] }}</span>
  </div>
  <div class="dpp-mobile-row">
    <span class="dpp-mobile-label">Order</span>
    <span class="dpp-mobile-value">...</span>
  </div>
  <div class="dpp-mobile-row">
    <span class="dpp-mobile-label">Pending Payment Days</span>
    <span class="dpp-mobile-value dpp-days-value">15 - 13 - 9 - 5 - 4</span>
  </div>
</div>
```

| Rule | Detail |
|---|---|
| Labels | Muted small text (`text-muted`, `font-size: 0.75rem`) |
| Values | Normal weight; Order ID may be link-styled |
| Pending days | Slightly larger / monospace optional for number alignment |
| Touch | Minimum tap target 44px height for linked Order row |
| Spacing | `p-3` per card; last item no double border |

### 5. Footnotes — responsive

- Footnotes below all brand cards in full-width `col-12`.
- Font size `small`; padding `px-3 pb-3` on mobile.
- Do not sit inside `table-responsive` scroll area.

### 6. Scoped CSS (`module-responsive.blade.php`)

Implemented in repo — includes:

- `.dpp-brand-card`, `.dpp-brand-card-header`, column width classes (`.dpp-col-city` … `.dpp-col-days`)
- `.dpp-days-screen` (visible on screen) / `.dpp-days-print` (hidden on screen, visible in `@media print`)
- `.dpp-day-pill` hover cursor; `.dpp-footnotes-card`
- Print: `table-layout: fixed`, wrap on pending days, hide mobile list, `page-break-inside: avoid` on brand cards

### 7. Sidebar & main layout

- Uses existing `layouts.main` — **no change** to sidebar behaviour.
- On mobile, sidebar is already off-canvas / collapsible via project layout; report content is full width within `content` area.
- Avoid wide fixed-width elements inside report (no `min-width` on outer card).

### 8. Print & export actions

- **Export Excel** and **Print** sit in `.dpp-header-actions`; both `width: 100%` on `max-width: 575.98px`.
- `@media print`: hide `.dpp-header-actions` (no export/print buttons in printout); hide filters; force single column; show tables not mobile cards.
- Page break: `page-break-inside: avoid` on brand card where possible.

### 9. What NOT to do

- Do **not** use DataTables `responsive: true` child-row mode for this report (static/grouped data; use card fallback instead).
- Do **not** shrink font below 12px for body text.
- Do **not** hide the “Pending Days” column on mobile — it is the primary metric.
- Do **not** require landscape orientation on phones.

### 10. Responsive QA checklist

| Device / width | Expected |
|---|---|
| iPhone SE (375px) | Stacked brand cards; mobile list; no page horizontal scroll |
| iPad portrait (768px) | Table with in-card horizontal scroll if needed |
| Desktop 1366px+ | Full-width stacked brand cards (`col-12`) |
| Print / PDF | Pending days wrap like Excel; no truncated overflow |
| Excel export | Spacers before first brand, between brands, before footnotes |

---

## 🛠️ Laravel 12 Technical Requirements

### Architecture

| Item | Path / name |
|---|---|
| Controller | `app/Http/Controllers/DeliveryPendingPaymentsController.php` |
| Service | `app/Services/DeliveryPendingPaymentsReportService.php` |
| Excel export | `app/Exports/DeliveryPendingPaymentsExport.php` |
| View (index) | `resources/views/delivery_pending_payments/index.blade.php` |
| Partial — brand table | `resources/views/delivery_pending_payments/partials/brand-section.blade.php` |
| Partial — pending days | `resources/views/delivery_pending_payments/partials/pending-days-cell.blade.php` |
| Partial — CSS | `resources/views/delivery_pending_payments/partials/module-responsive.blade.php` |
| Permission seeder | `database/seeders/DeliveryPendingPaymentsPermissionSeeder.php` |
| Routes | `routes/web.php` (`export` route registered **before** `index`) |
| Sidebar | `resources/views/layouts/sidebar.blade.php` (Sales submenu) |
| Dashboard stat + widget | `resources/views/dashboard.blade.php`, `resources/views/dashboard/partials/delivery_pending_payments_widget.blade.php` |
| Dashboard controller | `app/Http/Controllers/HomeController.php` (`DeliveryPendingPaymentsReportService` injected) |

### Routes (`routes/web.php`)

```php
Route::get('delivery-pending-payments/export', [DeliveryPendingPaymentsController::class, 'export'])
    ->name('delivery-pending-payments.export')
    ->middleware('permission:view-dispatch-pending-payments');
Route::get('delivery-pending-payments', [DeliveryPendingPaymentsController::class, 'index'])
    ->name('delivery-pending-payments.index')
    ->middleware('permission:view-dispatch-pending-payments');
```

### Controller responsibility

- `index()`: build grouped report array; return Blade with `$brandSections` (or equivalent).
- `export()`: apply same brand filter → `build()` → `Excel::download(FromArray)`; empty sections redirect with error flash.
- No `store` / `update` / `destroy`.
- No DataTables required for v1 (static HTML table is acceptable; DataTables optional if row count is large).

### Models — no changes required

Use existing `DispatchManagement`, `OrderManagement`, `DealerManagement`, `BrandManagement`, `CityManagement`.

---

## 📋 Sample Data (from requirements spreadsheet)

**Screen:** day counts only (e.g. `15 - 13 - 9`). **Excel / print:** `15 (5 Jan 2026) - 13 (7 Jan 2026) - …`

| City | Dealer | Order | Pending Payment Days (export/print example) |
|---|---|---|---|
| Patan | Abhay | ORD/2025-26/0001 | 15 (1 Jun 2026) - 13 (3 Jun 2026) - 9 (7 Jun 2026) |
| Deesa | Abhay | ORD/2025-26/0009 | 14 (2 Jun 2026) - 12 (4 Jun 2026) - 5 (11 Jun 2026) |

Brands appear in **separate stacked sections** (Ajay Brand, Mahakal Brand, Mayank Brand, …).

---

## ✅ Acceptance Criteria

1. Page loads under Sales → **Dispatch Pending Payments** with `view-dispatch-pending-payments`.
2. Only orders with **≥ 1** unpaid dispatch (`status = 0`) appear.
3. Columns: **City** | **Dealer** | **Order** | **Pending Payment Days** (label without “Dispatch”).
4. Day count = calendar days from `dispatch_date` to today; sorted descending per order.
5. Grouped by **Brand** (`col-12` stacked), sorted City → Dealer → Order.
6. **Screen:** day numbers with tooltip on hover (*Dispatch date: d M Y*).
7. **Print/PDF:** `pending_days_label` wraps in cell — same format as Excel.
8. **Excel:** `FromArray` export with spacers (before first brand, between brands, before footnotes); column D wrap; no phantom blank data rows.
9. Brand headers use `formatBrandSectionTitle()` (no “Brand Brand”).
10. Order links to dispatch history when user has dispatch permissions.
11. Brand filter (Select2) applies to index and export.
12. Mobile (`< md`): card list; desktop: table in `table-responsive`.
13. No DB writes from this module; soft-deleted records excluded.
14. Footnotes explain aging, screen hover, and scope.
15. **Dashboard:** users with `view-dispatch-pending-payments` see stat card **Unpaid Dispatches (10+ days)** (`dispatch_count`) and widget **Dispatch Pending Payments (10+ Days)** below KPI row, above Recent Dealers/Orders/Dispatch.
16. Dashboard widget lists only unpaid dispatches with **≥ 10** calendar days; full report lists **all** unpaid dispatches (any age).
17. Dashboard widget reuses `brand-section` partial and links to full report; order links respect dispatch permissions.

---

## 🔗 Dependencies

| Dependency | Document / path |
|---|---|
| Sales module (orders, dispatch) | `md-file-requirements/Sales_Module_Requirements.md` |
| Dispatch payment status field | `resources/views/dispatch_management/partials/status-field.blade.php` |
| Dispatch model constants | `app/Models/DispatchManagement.php` |
| Mark payment on dispatch | `DispatchManagementController@store` / `@update` |
| Dashboard home | `app/Http/Controllers/HomeController.php`, `resources/views/dashboard.blade.php` |

---

## 🤖 AI Generation Prompt

> Copy everything below this line and share it with an AI agent.

---

```
You are a Laravel 12 developer. Maintain the implemented "Dispatch Pending Payments" report module.

## Context
Read-only Sales report: orders with at least one UNPAID dispatch (status = 0). Per order: City, Dealer, Order ID, Pending Payment Days. Group by Brand (stacked col-12 sections).

## Implemented files (do not duplicate)
- app/Http/Controllers/DeliveryPendingPaymentsController.php
- app/Services/DeliveryPendingPaymentsReportService.php (build, buildForDashboard, summarize, formatPendingDaysLabel, formatBrandSectionTitle, DASHBOARD_MIN_PENDING_DAYS)
- app/Exports/DeliveryPendingPaymentsExport.php (FromArray, WithEvents, spacers)
- resources/views/delivery_pending_payments/index.blade.php
- partials: brand-section, pending-days-cell, pending-days-chips, mobile-order-card, footnotes-legend, module-responsive
- resources/views/dashboard/partials/delivery_pending_payments_widget.blade.php
- database/seeders/DeliveryPendingPaymentsPermissionSeeder.php
- HomeController: dashboard stat card + widget data (dpp_dashboard_*)

## UI naming
- Display: "Dispatch Pending Payments" (sidebar, page title, Excel, dashboard widget)
- Internal: routes/permission still `delivery-pending-payments` / `view-dispatch-pending-payments`

## Dashboard (10+ days)
- Stat card label: "Unpaid Dispatches (10+ days)" — count = dispatch_count
- Widget: "Dispatch Pending Payments (10+ Days)" — buildForDashboard(), filter days >= 10
- Full report index: all unpaid dispatches (no min-days filter)
- Layout: KPI row → DPP widget → Recent Dealers/Orders/Dispatch

## Pending Payment Days — three contexts
1. Screen: "15 - 13 - 9" with Bootstrap tooltip per number (dispatch date d M Y)
2. Print/PDF: pending_days_label wrapped in cell — "15 (5 Jan 2026) - 13 (7 Jan 2026)"
3. Excel: same pending_days_label, column D wrap text, FromArray (NOT FromView HTML)

## Excel spacers
- spacer-lg (16px) after subtitle, before first brand
- spacer (10px) between brands
- spacer-lg before footnotes

## HTML layout
- col-12 only (no side-by-side brands)
- No h-100 on brand cards
- Export + Print buttons in header; brand Select2 filter

## Routes
GET delivery-pending-payments/export (before index)
GET delivery-pending-payments
Permission: view-dispatch-pending-payments

## Full spec
md-file-requirements/Dispatch_Pending_Payments_Module_Requirements.md
```
