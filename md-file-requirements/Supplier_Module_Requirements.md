# Supplier Module — Requirements & AI Prompt
> Laravel 12 | Tailwind CSS | Prepared for AI-assisted code generation

---

## 📁 Module Overview

**Module Name:** Supplier Management
**Framework:** Laravel 12
**CSS Framework:** Tailwind CSS

---

## 🗄️ Database Schema

### Table: `suppliers`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| name | VARCHAR(255) | — | Supplier name |
| mobile | VARCHAR(20) | — | Contact number |
| email | VARCHAR(255) | — | Email address |
| address | TEXT | — | Full address |
| opening_balance | DECIMAL(12,2) | 0 | Opening account balance |
| status | TINYINT | 1 | 1 = active, 0 = disabled |
| state_id | BIGINT | — | FK → `state_management.id` |
| city_id | BIGINT | — | FK → `city_management.id` |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

### Table: `state_management`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| name | VARCHAR(255) | — | State name |
| status | TINYINT | 1 | 1 = active, 0 = inactive |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

### Table: `city_management`

| Column | Type | Default | Notes |
|---|---|---|---|
| id | BIGINT | AUTO INCREMENT | Primary Key |
| name | VARCHAR(255) | — | City name |
| state_id | BIGINT | — | FK → `state_management.id` |
| status | TINYINT | 1 | 1 = active, 0 = inactive |
| created_at | TIMESTAMP | NULL | |
| updated_at | TIMESTAMP | NULL | |
| deleted_at | TIMESTAMP | NULL | Soft delete |

---

## 🖥️ Module: Supplier Management

### Pages / Screens

#### 1. List Page

- **Page Title:** Supplier Management
- **Table columns (exact order):**
  `Sr No` | `Name` | `Mobile` | `Email` | `Address` | `City` | `Opening Balance` | `Status` | `Action`
- **Search:** by name, mobile, email
- **Filters:**
  - Status → All / Active / Inactive
  - State → dropdown (all active states)
  - City → dropdown (filtered dynamically by selected state)
- **Actions per row (⋮ dropdown):** Edit | Delete
- **Page-level Actions:** Add Supplier button (top right)
- **Pagination:** Show N entries dropdown (10, 25, 50, 100), showing X to Y of Z entries
- **Bulk selection:** Checkbox per row + header checkbox (select all on current page)

#### 2. Add Supplier — Modal

- Triggered by clicking **Add Supplier** button on list page
- **Modal Title:** Add Supplier
- **Fields:**

| Field | Type | Required | Notes |
|---|---|---|---|
| Name | Text input | Yes | Supplier name |
| Mobile | Text input | Yes | Contact number |
| Email | Email input | Yes | Email address |
| Opening Balance (₹) | Number input | No | Decimal, default 0.00 |
| Address | Textarea | Yes | Full address |
| State | Dropdown | Yes | Load active states; on change, filter cities |
| City | Dropdown | Yes | Filtered by selected state (AJAX) |
| Status | Radio buttons | Yes | Active (default) / Inactive |

- **Buttons:** Cancel (red) | Save (blue)
- On **Save:** validate → store → close modal → refresh table → show success flash

#### 3. Edit Supplier — Modal

- Triggered by clicking **Edit** in the row action (⋮) menu
- **Modal Title:** Edit Supplier
- Same fields as Add form, pre-populated with existing data
- State/City dropdowns pre-select the saved values (city list filtered to match saved state)
- On **Save:** validate → update → close modal → refresh table → show success flash

#### 4. Delete Supplier

- Triggered by clicking **Delete** in the row action (⋮) menu
- Show **confirmation popup/modal** before executing
- **Delete Rule:** Soft delete only. Block delete if any `raw_material_orders` exist for this supplier — show an error message explaining why deletion is not allowed.
- On successful delete: remove row from table → show success flash

---

## ⚙️ Business Rules

### State & City Dependency
- City dropdown is **disabled** until a state is selected
- When state changes, reload city dropdown via AJAX (`/cities?state_id=X`) showing only active cities for that state
- City dropdown resets to blank when state changes

### Status
- `1` = Active (green badge)
- `0` = Inactive / Disabled (red badge)

### Opening Balance
- Stored as `DECIMAL(12,2)`
- Displayed with ₹ prefix in the list table (e.g., `₹ 111.00`)
- Default value is `0.00`

### Soft Deletes
- Use `SoftDeletes` trait on `Supplier`, `StateManagement`, `CityManagement` models
- Block supplier delete if related `raw_material_orders` records exist

### Validation Rules
- `name` → required, string, max 255
- `mobile` → required, string, max 20
- `email` → required, email, max 255, unique (excluding current record on update)
- `address` → required, string
- `opening_balance` → nullable, numeric, min 0, default 0
- `state_id` → required, exists in `state_management`
- `city_id` → required, exists in `city_management`
- `status` → required, in: 0, 1

---

## 🎨 Design Requirements

### Design Philosophy
- **Framework:** Tailwind CSS — do NOT use Bootstrap
- Study the existing project's UI and match it exactly
- Match: color scheme, sidebar style, navbar, card style, button styles, badge styles, table styles, form inputs, spacing, font

### How to Capture Existing Design (Instructions for AI)
Before generating any Blade views, the AI must:
1. Read the existing Blade layout file (`resources/views/layouts/app.blade.php` or equivalent)
2. Read 1–2 existing module views (e.g., existing `index.blade.php` and `create.blade.php`) to understand exact patterns
3. Note from existing views:
   - Tailwind CSS class conventions used (custom colors, spacing scale)
   - How page titles and breadcrumbs are structured
   - How cards/panels wrap content
   - How tables are styled (striped, hover, border, responsive wrapper)
   - How action buttons are placed (inline, dropdown ⋮ menu, icon buttons)
   - How forms are laid out inside modals (label position, input size, grid columns)
   - How flash messages / alerts are shown (success, error)
   - How modals are implemented (Alpine.js or vanilla JS)
   - How pagination is rendered
   - How status badges/pills are colored
   - How filters/search bar is positioned (above table)
4. Use ALL of the above patterns consistently across every view generated for this module

### Specific Design Rules
- **List page:** Match existing table layout — same wrapper divs, same classes, same action button style
- **Modals (Add/Edit):** Match existing modal patterns in the project
- **Delete confirmation:** Use the same confirmation method as the rest of the project
- **Status badges:** `Active` = green, `Inactive` = red — match existing badge style
- **Flash messages:** Reuse existing session flash alert pattern (`session('success')`, `session('error')`)
- **Sidebar menu:** Add Supplier Management link in the sidebar following the same format as existing menu items

### Status Badge Colors
- Active → success / green
- Inactive → danger / red

---

## 📱 Responsive Requirements

- Use the project’s existing responsive patterns (Bootstrap grid / existing layout behavior).
- List page filter bar must wrap/stack cleanly on small screens (no horizontal page overflow).
- Tables must remain usable on mobile via the existing `table-responsive` wrapper (horizontal scroll only inside the table container).
- Modal form fields should stack to a single column on small screens, and use two columns only where the project already does so.
- Ensure dropdowns (State/City) are full-width on mobile and remain accessible.

---

## 🛠️ Laravel 12 Technical Requirements

### Architecture
- **Laravel 12** with standard MVC pattern
- **Eloquent ORM** with Model relationships
- **Laravel Migrations** for all tables
- **Soft Deletes** (`SoftDeletes` trait) on Supplier, StateManagement, CityManagement models
- **Form Requests** for validation
- **Resource Controller** for Supplier CRUD
- Modals handled via **Alpine.js** (or match existing project pattern)

### Models & Relationships

```
Supplier
  - belongsTo(StateManagement, 'state_id')
  - belongsTo(CityManagement, 'city_id')
  - hasMany(RawMaterialOrder)        // for delete block check

StateManagement
  - hasMany(CityManagement)
  - hasMany(Supplier)

CityManagement
  - belongsTo(StateManagement)
  - hasMany(Supplier)
```

### Controllers

**SupplierController** (Resource)
- `index` — list with search + filters + pagination
- `store` — validate & create (returns JSON for modal)
- `update` — validate & update (returns JSON for modal)
- `destroy` — soft delete with block check (returns JSON)

**CityController** (or inline in SupplierController)
- `getCitiesByState($state_id)` — returns JSON list of active cities for a given state
  - Route: `GET /cities?state_id={id}`

### Routes

```php
// Suppliers
Route::resource('suppliers', SupplierController::class)->except(['show', 'create', 'edit']);
// Add/Edit are modal-based, no separate pages needed for create/edit

// AJAX — cities by state
Route::get('/cities', [CityController::class, 'byState'])->name('cities.byState');
```

### Key Migrations

**`create_state_management_table`**
```
id, name (string), status (tinyint default 1), created_at, updated_at, deleted_at
```

**`create_city_management_table`**
```
id, name (string), state_id (foreignId → state_management), status (tinyint default 1), created_at, updated_at, deleted_at
```

**`create_suppliers_table`**
```
id, name (string), mobile (string), email (string), address (text),
opening_balance (decimal 12,2 default 0), status (tinyint default 1),
state_id (foreignId → state_management), city_id (foreignId → city_management),
created_at, updated_at, deleted_at
```
- Add indexes on: `state_id`, `city_id`, `status`

### Frontend (Blade + Tailwind + Alpine.js)

- **List page:** Blade table with Tailwind classes, search input, status filter, state/city dropdowns above table
- **Add/Edit:** Modal dialog using Alpine.js `x-data` / `x-show` — no separate page
- **State → City AJAX:** On state dropdown `@change`, fire `fetch('/cities?state_id=X')` and repopulate city dropdown
- **Delete:** Confirmation modal (or `confirm()`) before sending DELETE request
- **Pagination:** Laravel default pagination styled with Tailwind
- **Flash messages:** Inline alert (success/error) using `session('success')` / `session('error')`

---

## 🤖 AI Generation Prompt

> Copy everything below this line and share it with an AI agent.

---

```
You are a Laravel 12 developer. I need you to generate a complete "Supplier Management" module for my Laravel 12 application.

## Context
This is a cattle feed manufacturing company's ERP system. The Supplier module manages supplier master records used across purchase orders for raw materials.

## Tech Stack
- Laravel 12
- Tailwind CSS (already installed — do NOT use Bootstrap)
- Alpine.js for dynamic UI (modals, AJAX dropdowns)
- MySQL database
- Blade templates

## Database Schema

### suppliers
| Column | Type | Notes |
|---|---|---|
| id | bigint auto increment | PK |
| name | string | |
| mobile | string | |
| email | string | |
| address | text | |
| opening_balance | decimal(12,2) default 0 | |
| status | tinyint default 1 | 1=active, 0=disabled |
| state_id | bigint | FK → state_management.id |
| city_id | bigint | FK → city_management.id |
| created_at | timestamp null | |
| updated_at | timestamp null | |
| deleted_at | timestamp null | soft delete |

### state_management
| Column | Type | Notes |
|---|---|---|
| id | bigint auto increment | PK |
| name | string | |
| status | tinyint default 1 | 1=active, 0=inactive |
| created_at | timestamp null | |
| updated_at | timestamp null | |
| deleted_at | timestamp null | soft delete |

### city_management
| Column | Type | Notes |
|---|---|---|
| id | bigint auto increment | PK |
| name | string | |
| state_id | bigint | FK → state_management.id |
| status | tinyint default 1 | 1=active, 0=inactive |
| created_at | timestamp null | |
| updated_at | timestamp null | |
| deleted_at | timestamp null | soft delete |

## What to Generate

### 1. Migrations
- create_state_management_table
- create_city_management_table
- create_suppliers_table (with foreign keys to state_management and city_management)

### 2. Models
- StateManagement (SoftDeletes, hasMany CityManagement, hasMany Supplier)
- CityManagement (SoftDeletes, belongsTo StateManagement, hasMany Supplier)
- Supplier (SoftDeletes, belongsTo StateManagement, belongsTo CityManagement)

### 3. Form Requests (Validation)
- StoreSupplierRequest
- UpdateSupplierRequest
  - name: required, string, max:255
  - mobile: required, string, max:20
  - email: required, email, max:255, unique:suppliers (ignore current id on update)
  - address: required, string
  - opening_balance: nullable, numeric, min:0
  - state_id: required, exists:state_management,id
  - city_id: required, exists:city_management,id
  - status: required, in:0,1

### 4. Controllers
- SupplierController:
  - index: load suppliers with state/city, apply search (name/mobile/email) + status filter + state/city filter + pagination
  - store: validate + create + return JSON {success: true, message: '...'}
  - update: validate + update + return JSON {success: true, message: '...'}
  - destroy: soft delete, block if raw_material_orders exist for this supplier + return JSON {success: true/false, message: '...'}
- CityController (or method in SupplierController):
  - byState: return JSON list of active cities for given state_id

### 5. Routes (in web.php)
Route::resource('suppliers', SupplierController::class)->except(['show', 'create', 'edit']);
Route::get('/cities', [CityController::class, 'byState'])->name('cities.byState');

### 6. Blade Views

#### resources/views/suppliers/index.blade.php
- Page title: "Supplier Management"
- Top right: "Add Supplier" button (opens Add modal)
- Above table: Search input (name/mobile/email), Status filter dropdown, State filter dropdown, City filter dropdown (filtered by selected state)
- Table columns: Sr No | Name | Mobile | Email | Address | City | Opening Balance | Status | Action
  - City displays the city name (from city_management via city_id)
  - Opening Balance displayed as ₹ X.XX
  - Status badge: green for Active, red for Inactive
  - Action: ⋮ dropdown with Edit and Delete
- Below table: "Showing X to Y of Z entries" + pagination
- Flash success/error messages at top

#### Add Supplier Modal (inside index.blade.php or a partial)
- Modal triggered by Add Supplier button
- Fields: Name*, Mobile*, Email*, Opening Balance (₹), Address* (textarea), State*, City* (disabled until state selected), Status (radio: Active default / Inactive)
- Buttons: Cancel (red), Save (blue)
- On save: POST /suppliers → refresh table or reload page → show flash

#### Edit Supplier Modal (inside index.blade.php or a partial)
- Modal triggered by Edit in ⋮ dropdown
- Same fields as Add, pre-populated via AJAX fetch of supplier data
- On save: PUT /suppliers/{id} → refresh or reload page → show flash

#### Delete Confirmation
- Show confirmation modal or confirm() dialog
- On confirm: DELETE /suppliers/{id}
- Block and show error if supplier has linked raw_material_orders

## Business Logic

### State → City Dependency
- On state dropdown change: AJAX GET /cities?state_id={id}
- Response: [{id, name}, ...]
- Repopulate city dropdown with new options; disable city if state is empty
- City dropdown resets when state changes

### Delete Block
- In SupplierController@destroy: check if Supplier hasMany RawMaterialOrder count > 0
- If yes: return JSON {success: false, message: 'Cannot delete supplier — linked to existing purchase orders.'}
- If no: soft delete and return JSON {success: true, message: 'Supplier deleted successfully.'}

### Opening Balance
- Default 0.00 on create
- Display with ₹ prefix in list table

## Design Instructions — IMPORTANT

Do NOT generate generic or default Tailwind views. Follow these steps before writing any Blade view:

### Step 1 — Read the existing layout file
Read: resources/views/layouts/app.blade.php (or the main layout used in this project)
Understand base HTML structure, sidebar, navbar, and @yield sections.

### Step 2 — Read 2 existing module views
Read any existing index.blade.php and a modal or form view from another module already in the project.
Identify:
- Tailwind CSS class patterns used (colors, spacing, rounded, shadow)
- Page header / title structure
- Filter bar and search box layout
- Table structure and wrapper
- Action button (⋮ dropdown) pattern
- Modal implementation (Alpine.js x-show, x-data, etc.)
- Delete confirmation method
- Flash message structure
- Pagination rendering
- Status badge color patterns

### Step 3 — Match everything exactly
Every view in this module must look like it was built by the same developer as the rest of the project.
Use the exact same CSS classes, wrapper divs, and Alpine.js patterns.
Do NOT introduce any new library not already present in the project.
Add Supplier Management to the sidebar navigation in the same format as other existing menu items.

### Step 4 — Status badge colors
- Active → green badge (match existing badge style)
- Inactive → red badge (match existing badge style)

## Additional Notes
- All list pages must support filter state persistence via GET query params (filters remain after page reload)
- AJAX responses must return proper JSON with success boolean and message string
- Modal open/close state managed with Alpine.js
- Mobile number field: string type (not integer) to support leading zeros and formatting
- Soft delete on all 3 tables
- Add proper indexes on state_id, city_id, status columns in suppliers migration
```
