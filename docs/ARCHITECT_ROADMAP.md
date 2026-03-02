# ARCHITECT ROADMAP — Facturation SaaS

> **Single source of truth for architecture decisions, delivery phases, and acceptance criteria.**
> Scanned & generated: 2026-03-01 | Model: Claude Sonnet 4.6 (Senior Architect Mode)
> Update this file at the end of every completed phase.

---

## A) PROJECT SNAPSHOT

### What This Project Is

A **multi-tenant B2B SaaS invoicing & business management platform** built on Laravel 12.
Each paying company (tenant) gets a fully isolated backoffice to manage their customers,
products, quotes, invoices, purchases, inventory, and finances. The SaaS owner operates a
separate SuperAdmin portal to manage tenants, subscriptions, plans, and global permissions.

The UI theme is a fully pre-built admin theme ("Dreamsrent") with 300+ static Blade
templates in `resources/views/*.blade.php`. All dynamic views **must exactly mirror** these
reference templates — no new CSS classes, no new layout structures.

---

### Current Implemented Modules

| Module | Area | State | Route |
|--------|------|-------|-------|
| Multi-tenant core (domain identification, TenantContext, TenantScope) | Core | ✅ Working | — |
| Auth — login, register, forgot/reset password, email verify | Core | ✅ Working | `routes/auth.php` |
| Backoffice layout (sidebar, header, mainlayout) | UI Shell | ✅ Working | — |
| Backoffice dashboard (view only, no real data) | Backoffice | ✅ Routed | `bo.dashboard` |
| Backoffice — Account Settings (profile, password, avatar) | Backoffice | ✅ Working | `routes/backoffice/settings.php` |
| Backoffice — Roles & Permissions (tenant-scoped CRUD) | Backoffice | ✅ Working | `routes/backoffice/access.php` |
| SuperAdmin dashboard | SuperAdmin | ✅ Working | `sa.dashboard` |
| SuperAdmin — Tenant management (CRUD + suspend/activate) | SuperAdmin | ✅ Working | `routes/superadmin/tenants.php` |
| SuperAdmin — Plan management (full CRUD) | SuperAdmin | ✅ Working | `routes/superadmin/plans.php` |
| SuperAdmin — Subscription management (show/update/delete) | SuperAdmin | ✅ Working | `routes/superadmin/subscriptions.php` |
| SuperAdmin — Roles & Permissions (global CRUD) | SuperAdmin | ✅ Working | `routes/superadmin/access.php` |
| All 79 domain Models + 81 Migrations | Data | ✅ Done | — |
| Spatie Permission, MediaLibrary, ActivityLog — installed | Packages | ✅ Installed | — |
| Seeders: Permission, Role, Plan, DemoTenant | Dev data | ✅ Done | — |

---

### ⚠️ CRITICAL FINDING

> **Every single business-domain controller (`CustomerController`, `InvoiceController`,
> `ProductController`, etc.) currently returns `response()->json()` — they were scaffolded
> as API controllers.** Zero Blade-based business UI exists. Furthermore, all business
> route files are empty (just `<?php`). The models and migrations are complete, but
> nothing is reachable from the browser.

---

### Current Missing / Broken Modules

| Module | Route File | Controller State | Views |
|--------|-----------|------------------|-------|
| CRM — Customers, Addresses, Contacts | `routes/backoffice/crm.php` — **EMPTY** | JSON stub only | None |
| Catalog — Products, Categories, Units, Taxes | `routes/backoffice/catalog.php` — **EMPTY** | JSON stub only | None |
| Sales — Quotes, Invoices, Payments, Credit Notes | `routes/backoffice/sales.php` — **EMPTY** | JSON stub only | None |
| Purchases — Suppliers, POs, Vendor Bills | `routes/backoffice/purchases.php` — **EMPTY** | JSON stub only | None |
| Inventory — Warehouses, Stock, Movements, Transfers | `routes/backoffice/inventory.php` — **EMPTY** | JSON stub only | None |
| Finance — Bank Accounts, Expenses, Income, Currency | `routes/backoffice/finance.php` — **EMPTY** | JSON stub only | None |
| Reports — Sales, Purchases, Inventory, Finance | `routes/backoffice/reports.php` — **EMPTY** | Does not exist | None |
| User Management — Invite, List, Deactivate | Not created | Does not exist | None |
| Company / Tenant Settings | Not routed | Does not exist | None |
| SuperAdmin Settings | `routes/superadmin/settings.php` — **EMPTY** | Does not exist | None |
| SuperAdmin Templates | `routes/superadmin/templates.php` — **EMPTY** | Does not exist | None |
| DocumentNumberSequence logic | Model exists | Not implemented | N/A |
| Spatie ActivityLog wiring to models | Installed | Not wired | N/A |
| Email / Notification system | Models exist | Not implemented | N/A |
| Pro features (Recurring Invoices, Reminders, Branches) | Not routed | Stubs only | None |

---

### Key Stakeholders / Roles

| Role | Scope | Area |
|------|-------|------|
| **SuperAdmin** | Global (`tenant_id = NULL`) | `/admin/*` — manages tenants, plans, global permission catalog |
| **Company Admin** (`admin` role, tenant-scoped) | Single tenant | `/backoffice/*` — full access to company data |
| **Manager** (`manager` role, tenant-scoped) | Single tenant | Sales, CRM, Inventory, Reports |
| **Receptionist** (`receptionist` role, tenant-scoped) | Single tenant | Quotes, Invoices, Customers (limited) |
| *Custom roles* | Single tenant | Defined by company admin via Roles & Permissions module |

---

### Non-Functional Requirements (Inferred)

| Requirement | Current State | Target |
|-------------|--------------|--------|
| **Data isolation** | `TenantScope` global scope on all models | ✅ Architecture done; needs testing |
| **Authorization** | Spatie Permission installed, gates not wired in controllers | ⚠️ Must add `can()` checks per-action |
| **Audit trail** | Spatie ActivityLog installed | ⚠️ Not wired to any model |
| **Document numbering** | `DocumentNumberSequence` model exists | ⚠️ Logic not implemented |
| **Soft deletes** | Inconsistent across models | ⚠️ Add to all key models |
| **Queues** | `QUEUE_CONNECTION=database` in .env, no Jobs created | ⚠️ Needed for email/export |
| **Multi-currency** | Currency + ExchangeRate models | ⚠️ Not wired to invoices |
| **File storage** | Spatie MediaLibrary on `local` disk | ⚠️ Not tenant-partitioned |
| **Backups** | Not configured | ⚠️ Add before production |
| **Tests** | Zero test files found | ⚠️ Start immediately |

---

## B) ARCHITECTURE OVERVIEW (CURRENT STATE)

### High-Level System Map

```
┌─────────────────────────────────────────────────────────────────────┐
│                         PUBLIC WEBSITE                              │
│  GET /  /pricing  /features  /contact  (resources/views/web/)       │
└────────────────────────────┬────────────────────────────────────────┘
                             │  POST /login → /dashboard
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      AUTH LAYER                                     │
│  routes/auth.php | Guard: web (session) | App\Models\User           │
└──────────────────┬──────────────────────────────┬───────────────────┘
                   │                              │
    user.tenant_id = UUID                user.tenant_id = NULL
                   │                              │
                   ▼                              ▼
┌──────────────────────────┐     ┌────────────────────────────────────┐
│   BACKOFFICE /bo.*       │     │   SUPERADMIN /sa.*                  │
│   /backoffice/*          │     │   /admin/*                          │
│                          │     │                                     │
│ Middleware:              │     │ Middleware:                         │
│  identifyTenant          │     │  auth + isSuperAdmin                │
│  tenantActive            │     │                                     │
│  setTenantContext        │     │ ✅ Dashboard                        │
│  auth                   │     │ ✅ Tenants (CRUD + suspend)          │
│                          │     │ ✅ Plans (CRUD)                     │
│ ✅ Dashboard (stub)      │     │ ✅ Subscriptions                    │
│ ✅ Account Settings      │     │ ✅ Roles & Permissions               │
│ ✅ Roles & Permissions   │     │ ⬜ Settings (empty route)            │
│ ⬜ CRM (empty route)     │     │ ⬜ Templates (empty route)           │
│ ⬜ Catalog (empty route) │     └────────────────────────────────────┘
│ ⬜ Sales (empty route)   │
│ ⬜ Purchases             │
│ ⬜ Inventory             │
│ ⬜ Finance               │
│ ⬜ Reports               │
│ ⬜ Users                 │
│ ⬜ Company Settings      │
└──────────────────────────┘

DATA LAYER
┌──────────────────────────────────────────────────────────────────────┐
│  SQLite (dev) → MySQL/PostgreSQL (prod)                             │
│  79 Models | 81 Migrations | 12 Domains                            │
│  Isolation: TenantScope (global scope, auto-applied via model boot) │
│  Auto-fill: BelongsToTenant trait (tenant_id on creating event)     │
│  Context: TenantContext singleton (per-request, set by middleware)  │
└──────────────────────────────────────────────────────────────────────┘
```

---

### Auth / Roles Model

```
Permission (global, tenant_id = NULL — created by PermissionSeeder)
  └── naming: "{group}.{module}.{action}"
      Groups: sales | crm | inventory | purchases | finance | reports | settings | access
      Actions: view | create | edit | delete
      Examples:
        sales.invoices.view       crm.customers.create
        access.roles.delete       reports.sales.view

Role
  ├── tenant_id = NULL → global role (platform-level, e.g. super_admin)
  └── tenant_id = UUID → tenant-scoped role (admin, manager, receptionist...)

User
  ├── tenant_id = NULL → SuperAdmin → /admin/*
  └── tenant_id = UUID → Tenant user → /backoffice/*
        └── assigned Role(s) → grants Permission(s)

Permission check (TARGET — not yet applied to business controllers):
  abort_unless(auth()->user()->can('sales.invoices.create'), 403);
```

---

### Data Boundaries & Isolation Rules

| Rule | Enforcement |
|------|-------------|
| All tenant-owned models filter by `tenant_id` | `TenantScope` global scope (automatic via `BelongsToTenant` boot) |
| `tenant_id` auto-set on create | `BelongsToTenant::creating()` reads from `TenantContext` singleton |
| SuperAdmin bypasses tenant scope | `tenant_id = null` → scope never applied |
| Route model binding must respect scope | ✅ Automatic (global scope applies to `findOrFail`) |
| Permission catalog is global | `PermissionSeeder` seeds with `tenant_id = null` |
| Roles are tenant-scoped | Custom `Role` model has `tenant_id` column |
| Media files | ⚠️ Not yet tenant-partitioned — risk (see section C) |

---

### Where Business Logic Lives (Target)

| Layer | Responsibility | Example |
|-------|---------------|---------|
| `Controller` | HTTP only: validate, delegate, redirect | `InvoiceController::store()` |
| `FormRequest` | Input validation + authorization | `StoreInvoiceRequest` |
| `Service` | Complex business logic | `InvoiceService::create()`, `PaymentService::allocate()` |
| `Model` | Relationships, scopes, accessors | `Invoice::overdue()` scope |
| `Scope` | Reusable query filters | `TenantScope`, `StatusScope` |
| `Trait` | Reusable model behaviors | `BelongsToTenant`, `LogsActivity` |
| `Job` | Async operations | `SendInvoiceEmailJob` |
| `Notification` | Email/push notifications | `InvoiceSentNotification` |

Simple CRUD (Customer, Product, Warehouse) → controller handles directly, no Service.
Complex flows (Invoice creation, Payment allocation, Stock transfer) → dedicated Service.

---

### UI Layer Strategy

- **Layout**: `resources/views/backoffice/layout/mainlayout.blade.php`
- **Static reference templates**: `resources/views/*.blade.php` (300+ files — read-only, never modify)
- **Dynamic views**: `resources/views/backoffice/{domain}/{page}.blade.php`
- **Pattern**: Copy reference template → replace static content with Blade variables
- **Forbidden**: New CSS classes, new layout wrappers, new component types
- **Required**: French-only user strings, `@error` + `is-invalid`, `@forelse` + `@empty` tables

---

## C) ARCHITECTURE ISSUES & RISKS

### Priority 1 — Critical

| # | Issue | File(s) | Fix |
|---|-------|---------|-----|
| C1 | **ALL business controllers return `response()->json()`** — app is Blade/web, not API | All `Backoffice/{Domain}/*Controller.php` | Rewrite every controller (Phases 1–7) |
| C2 | **ALL business route files are empty** — controllers exist but are unreachable | `routes/backoffice/crm.php` + 5 others | Fill route files per phase |
| C3 | **No authorization checks in any controller action** — any authenticated user can do anything | All controllers | Add `abort_unless(can(...))` per action |
| C4 | **DeliveryChallan model filename contains Cyrillic character** (`Challан.php`) | `app/Models/Sales/DeliveryChallаn.php` | Rename to `DeliveryChallan.php` immediately |
| C5 | **Real developer email in `DemoTenantSeeder.php`** committed to git | `database/seeders/DemoTenantSeeder.php:128` | Replace with generic placeholder |

---

### Priority 2 — Security

| # | Issue | Risk |
|---|-------|------|
| S1 | Media files not tenant-scoped — `UuidPathGenerator` uses UUID only, not `{tenant_id}/{uuid}` | Tenant A could guess Tenant B's file URLs |
| S2 | `tenant_id` may be in `$fillable` on some models — client could inject it via POST | Cross-tenant data injection |
| S3 | SuperAdmin identified by `tenant_id === null` — if seeder fails, any null-tenant user becomes SA | Privilege escalation edge case |
| S4 | Hardcoded dev passwords in `DemoTenantSeeder.php` committed to git | Low risk now; HIGH risk if repo ever becomes public |
| S5 | `EnsureTenantIsActive` — if `IdentifyTenantByDomain` fails silently, active check is bypassed | Suspended tenant user could access backoffice |

---

### Priority 3 — Data Integrity

| # | Issue | Fix |
|---|-------|-----|
| D1 | No `SoftDeletes` on critical models (Customer, Invoice, Product, User, Supplier) | Add `SoftDeletes` trait to all customer-facing models |
| D2 | `DocumentNumberSequence` model exists but logic not implemented | Build `DocumentNumberService` before Sales module |
| D3 | Invoice status values not DB-enforced (no enum/check constraint) | Add to migration or model cast |
| D4 | `PaymentAllocation` and `CreditNoteApplication` — no unique constraint to prevent double-allocation | Add unique constraint |
| D5 | Currency/ExchangeRate models exist but not wired to Invoice/Quote totals | Wire in Phase 3 (Sales) |

---

### Priority 4 — Architecture Inconsistencies

| # | Issue | Fix |
|---|-------|-----|
| A1 | No Policy files — authorization is entirely absent | Add Policies per domain from Phase 1 |
| A2 | No Service classes for complex logic — will create fat controllers | Create Services for Invoice, Payment, Stock (Phases 3, 5) |
| A3 | Spatie ActivityLog installed but not wired to any model | Add `LogsActivity` trait to key models (Phase 8) |
| A4 | `AppServiceProvider` is empty — no service bindings | Register services and policies there |
| A5 | Spatie Permission cache disabled — every `can()` call hits DB | Enable after permissions stabilize |
| A6 | Zero tests exist | Start feature tests with tenant isolation (Phase 0) |
| A7 | `DashboardController@index` returns a view but with no real data | Wire KPIs in Phase 10 |

---

## D) TARGET ARCHITECTURE (Next 4–8 Weeks)

### North Star Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                               ✅ Done
│   │   ├── Backoffice/
│   │   │   ├── Access/                         ✅ Done
│   │   │   ├── CRM/                            ⬜ Phase 1 — rewrite
│   │   │   ├── Catalog/                        ⬜ Phase 2 — rewrite
│   │   │   ├── Sales/                          ⬜ Phase 3 — rewrite
│   │   │   ├── Purchases/                      ⬜ Phase 4 — rewrite
│   │   │   ├── Inventory/                      ⬜ Phase 5 — rewrite
│   │   │   ├── Finance/                        ⬜ Phase 6 — rewrite
│   │   │   ├── Users/                          ⬜ Phase 7 — create
│   │   │   ├── Settings/                       ⬜ Phase 8 — create
│   │   │   └── Reports/                        ⬜ Phase 9 — create
│   │   └── SuperAdmin/
│   │       ├── Access/                         ✅ Done
│   │       ├── Settings/                       ⬜ Phase 10
│   │       └── Templates/                      ⬜ Phase 10
│   ├── Requests/                               ✅ Structure good
│   ├── Middleware/                             ✅ Done
│   └── Policies/                               ⬜ Add per domain
│       ├── CustomerPolicy.php
│       ├── InvoicePolicy.php
│       └── ...
├── Models/                                     ✅ Done (79 models)
├── Services/
│   ├── Tenancy/TenantContext.php               ✅ Done
│   ├── System/DocumentNumberService.php        ⬜ Phase 0
│   ├── Sales/InvoiceService.php                ⬜ Phase 3
│   ├── Sales/PaymentService.php                ⬜ Phase 3
│   ├── Inventory/StockService.php              ⬜ Phase 5
│   └── Finance/CurrencyService.php             ⬜ Phase 6
├── Traits/
│   ├── BelongsToTenant.php                     ✅ Done
│   └── (LogsActivity via Spatie)               ⬜ Wire in Phase 11
└── Providers/AppServiceProvider.php            ⬜ Register services + policies

resources/views/backoffice/
├── layout/                                     ✅ Done
├── components/                                 ✅ Done
├── crm/customers/                              ⬜ Phase 1
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── catalog/products/                           ⬜ Phase 2
├── sales/{invoices,quotes,payments,...}/       ⬜ Phase 3
├── purchases/{suppliers,purchase-orders,...}/  ⬜ Phase 4
├── inventory/                                  ⬜ Phase 5
├── finance/                                    ⬜ Phase 6
├── users/                                      ⬜ Phase 7
├── settings/                                   ⬜ Phase 8
└── reports/                                    ⬜ Phase 9
```

---

### Standard CRUD Pattern (Copy This for Every Module)

**Route file** (`routes/backoffice/crm.php`):
```php
use App\Http\Controllers\Backoffice\CRM\CustomerController;

Route::prefix('crm')->as('crm.')->group(function () {
    Route::resource('customers', CustomerController::class);
});
```

**Controller** (`app/Http/Controllers/Backoffice/CRM/CustomerController.php`):
```php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('crm.customers.view'), 403);
    $customers = Customer::query()
        ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
        ->latest()->paginate(15)->withQueryString();
    return view('backoffice.crm.customers.index', compact('customers'));
}

public function store(StoreCustomerRequest $request)
{
    Customer::create($request->validated());
    return redirect()->route('bo.crm.customers.index')
        ->with('success', 'Client créé avec succès.');
}

public function destroy(Customer $customer)
{
    abort_unless(auth()->user()->can('crm.customers.delete'), 403);
    $customer->delete();
    return redirect()->route('bo.crm.customers.index')
        ->with('success', 'Client supprimé.');
}
```

**FormRequest** (`app/Http/Requests/CRM/Store/StoreCustomerRequest.php`):
```php
public function rules(): array { /* ... */ }
public function messages(): array { /* ALL FRENCH */ }
```

---

### Standard Response Pattern

| Outcome | Code |
|---------|------|
| Create success | `redirect()->route('bo.x.index')->with('success', 'Message en français.')` |
| Update success | `redirect()->route('bo.x.index')->with('success', '...')` |
| Delete success | `redirect()->route('bo.x.index')->with('success', '...')` |
| Validation fail | Automatic via FormRequest (`@error` in Blade) |
| Not authorized | `abort(403)` |
| Not found | Automatic via route model binding (`abort(404)`) |

Flash messages render via `resources/views/backoffice/components/alerts.blade.php` (exists).

---

### Minimum Testing Per Module

```
tests/Feature/
├── Auth/LoginTest.php                          ← login, redirect, logout
├── Tenancy/TenantIsolationTest.php             ← Tenant A cannot read Tenant B's data
├── CRM/CustomerCrudTest.php                    ← Phase 1
├── Catalog/ProductCrudTest.php                 ← Phase 2
├── Sales/InvoiceTest.php                       ← Phase 3
├── Sales/PaymentAllocationTest.php             ← Phase 3
└── ...

tests/Unit/
├── Services/DocumentNumberServiceTest.php      ← Phase 0
└── Services/InvoiceServiceTest.php             ← Phase 3
```

Per-module minimum:
1. Unauthenticated → redirected to login
2. Wrong-tenant user → 404 (scope blocks cross-tenant)
3. Happy path CRUD (create → read → update → delete)
4. Invalid input → validation error shown
5. Unauthorized role → 403

---

## E) PHASE-BY-PHASE DELIVERY PLAN

---

### PHASE 0 — Foundation Hardening
**Objective:** Fix critical bugs and infrastructure gaps before building any business module.

**Scope:** Core infrastructure only — no new user-facing pages.

**Technical Tasks:**
- [ ] Rename `app/Models/Sales/DeliveryChallаn.php` (Cyrillic) → `DeliveryChallan.php` (ASCII)
- [ ] Update all references to renamed model (namespace, use statements, relationships)
- [ ] Sanitize `DemoTenantSeeder.php` — replace hardcoded passwords with `.env` vars, replace real email with placeholder
- [ ] Audit `$fillable` on 5 critical models (Customer, Invoice, Product, User, Supplier) — ensure `tenant_id` is NOT fillable
- [ ] Add `SoftDeletes` to: Customer, Invoice, Quote, Product, Supplier, User
- [ ] Create `app/Services/System/DocumentNumberService.php` with DB-locked sequential generation
- [ ] Verify `IdentifyTenantByDomain` resolves `localhost` + `127.0.0.1` correctly for local dev
- [ ] Verify `EnsureTenantIsActive` aborts correctly for suspended tenants
- [ ] Write `tests/Feature/Tenancy/TenantIsolationTest.php` — Tenant A cannot read Tenant B's Customer/Invoice

**Deliverables:**
- `app/Models/Sales/DeliveryChallan.php` (fixed)
- `app/Services/System/DocumentNumberService.php`
- Updated `database/seeders/DemoTenantSeeder.php`
- `tests/Feature/Tenancy/TenantIsolationTest.php`

**Acceptance Criteria:**
- `php artisan migrate:fresh --seed` completes with no errors
- Login as superadmin → `/admin/` dashboard
- Login as tenant admin → `/backoffice/` dashboard
- Suspend tenant via SuperAdmin → tenant user gets blocked page
- `php artisan test` passes (isolation test)

**Risks & Mitigation:**
- Cyrillic filename may silently fail to load on Linux CI — fix immediately
- `DemoTenantSeeder` real email is PII in git — sanitize before any CI setup

**Complexity:** S

---

### PHASE 1 — CRM Module (Customers, Contacts, Addresses)
**Objective:** The first fully working Blade CRUD module. Establishes the pattern all subsequent modules will follow.

**Why first:** Customers are required by Sales (invoices need a customer). This module is also the simplest domain.

**Scope:** `routes/backoffice/crm.php`, 3 controllers, 4 Blade views.

**Technical Tasks:**
- [ ] Fill `routes/backoffice/crm.php` — resource routes for customers, addresses, contacts
- [ ] Rewrite `CustomerController` — replace all `response()->json()` with Blade views + redirects
- [ ] Add `abort_unless(can(...))` for each action using `crm.customers.*` permissions
- [ ] Add search filter: `when($request->search, fn($q,$s) => $q->where('name','like',"%$s%"))`
- [ ] Rewrite `CustomerAddressController` — nested under customer (inline modal or shallow resource)
- [ ] Rewrite `CustomerContactController` — nested under customer
- [ ] Audit `StoreCustomerRequest` and `UpdateCustomerRequest` — ensure all messages are French
- [ ] Create `resources/views/backoffice/crm/customers/index.blade.php` (ref: `customers.blade.php`)
- [ ] Create `resources/views/backoffice/crm/customers/create.blade.php` (ref: `add-customer.blade.php`)
- [ ] Create `resources/views/backoffice/crm/customers/edit.blade.php` (ref: `edit-customer.blade.php`)
- [ ] Create `resources/views/backoffice/crm/customers/show.blade.php` (ref: `customer-details.blade.php`)
- [ ] Activate CRM section in `resources/views/backoffice/layout/partials/sidebar.blade.php`
- [ ] Write `tests/Feature/CRM/CustomerCrudTest.php`

**Deliverables:**
```
routes/backoffice/crm.php
app/Http/Controllers/Backoffice/CRM/CustomerController.php
app/Http/Controllers/Backoffice/CRM/CustomerAddressController.php
app/Http/Controllers/Backoffice/CRM/CustomerContactController.php
resources/views/backoffice/crm/customers/index.blade.php
resources/views/backoffice/crm/customers/create.blade.php
resources/views/backoffice/crm/customers/edit.blade.php
resources/views/backoffice/crm/customers/show.blade.php
tests/Feature/CRM/CustomerCrudTest.php
```

**Acceptance Criteria:**
- GET `/backoffice/crm/customers` → paginated Blade table
- Search by name filters results and preserves query string
- Create → flash "Client créé avec succès." → redirected to list
- Edit → saves changes → redirected to list
- Delete → dropdown action → soft-delete → redirected to list
- Role without `crm.customers.create` → 403 on POST `/backoffice/crm/customers`
- Tenant B's customers invisible to Tenant A

**Risks & Mitigation:**
- Address/Contact modals need JS handling — use existing `modal-popup.blade.php` component pattern
- `withQueryString()` must be added to `paginate()` for search to persist across pages

**Complexity:** M

---

### PHASE 2 — Catalog Module (Products, Categories, Units, Taxes)
**Objective:** Build the product catalog — required foundation for invoicing and inventory.

**Scope:** `routes/backoffice/catalog.php`, 5 controllers, product views + settings views.

**Technical Tasks:**
- [ ] Fill `routes/backoffice/catalog.php` — resource routes for products, categories, units, tax groups, tax categories
- [ ] Rewrite `ProductController` — Blade CRUD with image upload via Spatie MediaLibrary
- [ ] Rewrite `ProductCategoryController` — simple list + inline modal CRUD
- [ ] Rewrite `UnitController` — simple list + inline modal CRUD
- [ ] Rewrite `TaxGroupController` — with associated TaxGroupRate items
- [ ] Rewrite `TaxCategoryController` — simple list + inline modal CRUD
- [ ] Create `resources/views/backoffice/catalog/products/index.blade.php` (ref: `products.blade.php`)
- [ ] Create `resources/views/backoffice/catalog/products/create.blade.php` (ref: `add-product.blade.php`)
- [ ] Create `resources/views/backoffice/catalog/products/edit.blade.php` (ref: `edit-product.blade.php`)
- [ ] Create `resources/views/backoffice/catalog/settings.blade.php` (categories, units, taxes — tabbed)
- [ ] When `track_inventory = true` on product create → auto-create `ProductStock` for default warehouse
- [ ] Activate Catalog section in sidebar
- [ ] Write `tests/Feature/Catalog/ProductCrudTest.php`

**Deliverables:**
```
routes/backoffice/catalog.php
app/Http/Controllers/Backoffice/Catalog/*.php (5 controllers)
resources/views/backoffice/catalog/products/{index,create,edit}.blade.php
resources/views/backoffice/catalog/settings.blade.php
tests/Feature/Catalog/ProductCrudTest.php
```

**Acceptance Criteria:**
- Products CRUD works with image upload
- Filter by category on product list
- Tax group can have multiple rates (e.g. TVA 20% + TVA 7%)
- Inventory tracking toggle creates initial stock record
- Permissions `inventory.products.*` enforced

**Complexity:** M

---

### PHASE 3 — Sales Core (Quotes → Invoices → Payments → Credit Notes)
**Objective:** The core revenue feature. Build the complete quote-to-cash flow.

**Scope:** `routes/backoffice/sales.php`, 4 controllers, 2+ Service classes.

**Technical Tasks:**
- [ ] Implement `DocumentNumberService` fully (if not done in Phase 0)
- [ ] Create `app/Services/Sales/InvoiceService.php` — number generation, tax calc, status machine
- [ ] Create `app/Services/Sales/PaymentService.php` — allocate payment amounts to invoices
- [ ] Fill `routes/backoffice/sales.php` — resource routes + custom actions (convert, duplicate, send, download)
- [ ] Rewrite `QuoteController` — Blade CRUD, use `InvoiceService`, convert-to-invoice action
- [ ] Rewrite `InvoiceController` — Blade CRUD, status machine, PDF download stub
- [ ] Rewrite `PaymentController` — record payment, allocate to invoice
- [ ] Rewrite `CreditNoteController` — create from invoice, apply to invoice
- [ ] Invoice status machine: `draft → sent → partial → paid → cancelled`
- [ ] Create views: `backoffice/sales/quotes/{index,create,edit,show}.blade.php`
  (ref: `quotations.blade.php`, `add-quotation.blade.php`)
- [ ] Create views: `backoffice/sales/invoices/{index,create,edit,show}.blade.php`
  (ref: `invoices.blade.php`, `add-invoice.blade.php`, `invoice-details.blade.php`)
- [ ] Create views: `backoffice/sales/payments/index.blade.php`
- [ ] Activate Sales section in sidebar
- [ ] Write `tests/Feature/Sales/InvoiceTest.php` and `PaymentAllocationTest.php`
- [ ] Write `tests/Unit/Services/InvoiceServiceTest.php`

**Deliverables:**
```
app/Services/Sales/InvoiceService.php
app/Services/Sales/PaymentService.php
routes/backoffice/sales.php
app/Http/Controllers/Backoffice/Sales/*.php (4 rewritten)
resources/views/backoffice/sales/{quotes,invoices,payments,credit-notes}/*.blade.php
tests/Feature/Sales/InvoiceTest.php
tests/Unit/Services/InvoiceServiceTest.php
```

**Acceptance Criteria:**
- Quote CRUD with line items and dynamic tax calculation
- Quote → Invoice conversion copies all items and charges
- Invoice auto-number: `INV-00001`, `INV-00002`... per tenant (never duplicated)
- Invoice total = sum(line_items) + sum(charges) - discount, tax applied per tax_group
- Payment recorded → `invoice.paid_amount` updated → status changes (`partial` / `paid`)
- Double-payment allocation is prevented at DB level
- Permissions `sales.invoices.*` enforced

**Complexity:** L

---

### PHASE 4 — Purchases (Suppliers, POs, Vendor Bills)
**Objective:** Build the purchase flow — mirror of Sales for the procurement side.

**Scope:** `routes/backoffice/purchases.php`, 3 controllers.

**Technical Tasks:**
- [ ] Fill `routes/backoffice/purchases.php`
- [ ] Rewrite `SupplierController` — Blade CRUD (mirrors CustomerController)
- [ ] Rewrite `PurchaseOrderController` — line items, status, "receive goods" action
- [ ] Rewrite `VendorBillController` — linked to PO/GoodsReceipt, payment allocation
- [ ] Create `app/Services/Purchases/PurchaseOrderService.php`
- [ ] Create views: `backoffice/purchases/suppliers/`, `purchase-orders/`, `vendor-bills/`
  (ref: `purchases.blade.php`, `purchase-orders.blade.php`)
- [ ] PO → GoodsReceipt → VendorBill workflow
- [ ] Activate Purchases section in sidebar
- [ ] Write feature tests

**Deliverables:**
```
routes/backoffice/purchases.php
app/Http/Controllers/Backoffice/Purchases/*.php (3 rewritten)
app/Services/Purchases/PurchaseOrderService.php
resources/views/backoffice/purchases/**/*.blade.php
tests/Feature/Purchases/PurchaseOrderTest.php
```

**Acceptance Criteria:**
- PO created → marked as received → GoodsReceipt auto-created
- VendorBill created from received PO
- Supplier payment allocated to vendor bill
- Permissions `purchases.*` enforced

**Complexity:** L

---

### PHASE 5 — Inventory (Warehouses, Stock, Movements, Transfers)
**Objective:** Stock level tracking per warehouse, movement audit log, inter-warehouse transfers.

**Scope:** `routes/backoffice/inventory.php`, 4 controllers, 1 Service.

**Technical Tasks:**
- [ ] Fill `routes/backoffice/inventory.php`
- [ ] Rewrite `WarehouseController` — Blade CRUD
- [ ] Rewrite `ProductStockController` — read-only stock levels per product/warehouse
- [ ] Rewrite `StockMovementController` — manual adjustment (creates movement record)
- [ ] Rewrite `StockTransferController` — transfer with items, status, approval flow
- [ ] Create `app/Services/Inventory/StockService.php` — atomic stock adjustment with DB transaction
- [ ] Wire: Invoice creation → stock decrement; GoodsReceipt creation → stock increment
- [ ] Create views (ref: `inventory.blade.php`, `warehouse.blade.php`)
- [ ] Activate Inventory section in sidebar
- [ ] Write feature tests

**Deliverables:**
```
routes/backoffice/inventory.php
app/Services/Inventory/StockService.php
app/Http/Controllers/Backoffice/Inventory/*.php (4 rewritten)
resources/views/backoffice/inventory/*.blade.php
tests/Feature/Inventory/StockTest.php
```

**Acceptance Criteria:**
- Warehouse CRUD
- Product stock level visible per warehouse
- Manual stock adjustment creates `StockMovement` record with reason
- Transfer deducts from source and adds to destination atomically
- Over-selling blocked when `track_inventory = true`

**Complexity:** M

---

### PHASE 6 — Finance (Bank Accounts, Expenses, Income, Currency)
**Objective:** Track business finances — expenses, income, bank reconciliation, multi-currency.

**Scope:** `routes/backoffice/finance.php`, 4 controllers.

**Technical Tasks:**
- [ ] Fill `routes/backoffice/finance.php`
- [ ] Rewrite `BankAccountController`, `ExpenseController`, `FinanceCategoryController`, `CurrencyController`
- [ ] Create `app/Services/Finance/CurrencyService.php` — exchange rate calculations
- [ ] Wire invoice payments to bank account balance
- [ ] Create views (ref: `bank-accounts.blade.php`, `expenses.blade.php`, `transactions.blade.php`)
- [ ] Activate Finance section in sidebar
- [ ] Write feature tests

**Deliverables:**
```
routes/backoffice/finance.php
app/Http/Controllers/Backoffice/Finance/*.php (4 rewritten)
app/Services/Finance/CurrencyService.php
resources/views/backoffice/finance/*.blade.php
```

**Acceptance Criteria:**
- Expenses CRUD with categories and bank account linkage
- Bank account balance reflects payments received minus expenses
- Multi-currency exchange rates configurable per tenant

**Complexity:** M

---

### PHASE 7 — User Management (Invite, Activate, Role Assignment)
**Objective:** Let tenant admins manage their team — invite new users, assign roles, deactivate users.

**Scope:** New `UserController` + invitation flow.

**Technical Tasks:**
- [ ] Create `app/Http/Controllers/Backoffice/Users/UserController.php`
- [ ] Create `app/Http/Controllers/Backoffice/Users/UserInvitationController.php`
- [ ] Create `routes/backoffice/users.php` + include in `web.php`
- [ ] Implement invite flow: create `UserInvitation` → queue email → user clicks link → sets password → assigned role
- [ ] Create views: `backoffice/users/index.blade.php` (ref: `users.blade.php`)
- [ ] Create invite form view
- [ ] Activate Users section in sidebar
- [ ] Write feature tests for invite flow

**Deliverables:**
```
routes/backoffice/users.php
app/Http/Controllers/Backoffice/Users/UserController.php
app/Http/Controllers/Backoffice/Users/UserInvitationController.php
resources/views/backoffice/users/{index,invite}.blade.php
tests/Feature/Users/UserInviteTest.php
```

**Acceptance Criteria:**
- Tenant admin sees all users in their tenant only
- Invite by email → email sent → user registers → has assigned role
- User can be activated/deactivated
- User cannot belong to a different tenant
- Permissions `access.users.*` enforced

**Complexity:** M

---

### PHASE 8 — Company Settings & Tenant Settings
**Objective:** Let tenant admins configure their company profile, invoice settings, localization.

**Scope:** New Settings controllers, extends `routes/backoffice/settings.php`.

**Technical Tasks:**
- [ ] Create `app/Http/Controllers/Backoffice/Settings/CompanySettingsController.php`
- [ ] Create `app/Http/Controllers/Backoffice/Settings/InvoiceSettingsController.php`
- [ ] Create `app/Http/Controllers/Backoffice/Settings/LocalizationSettingsController.php`
- [ ] Extend `routes/backoffice/settings.php` with company/invoice/localization routes
- [ ] Wire `TenantSetting` model to form persistence (JSON settings columns)
- [ ] Create views (ref: `company-settings.blade.php`, `invoice-settings.blade.php`, `email-settings.blade.php`)
- [ ] Ensure `SetTenantContext` middleware picks up new settings on next request

**Deliverables:**
```
app/Http/Controllers/Backoffice/Settings/*.php (3 controllers)
resources/views/backoffice/settings/*.blade.php
```

**Acceptance Criteria:**
- Company name, logo, address, default currency editable
- Invoice prefix + starting number configurable → `DocumentNumberService` respects it
- Timezone/language change → applied on next page load

**Complexity:** M

---

### PHASE 9 — Reports
**Objective:** Read-only analytics pages for each domain.

**Scope:** `routes/backoffice/reports.php` + new report controllers.

**Technical Tasks:**
- [ ] Create `app/Http/Controllers/Backoffice/Reports/` controllers (Sales, Customer, Finance, Inventory)
- [ ] Fill `routes/backoffice/reports.php`
- [ ] Create `app/Services/Reports/ReportService.php` — aggregate queries
- [ ] Create views (ref: `sales-report.blade.php`, `customers-report.blade.php`)
- [ ] Date range filter on all reports
- [ ] CSV export via queued job (`app/Jobs/ExportReportJob.php`)

**Deliverables:**
```
routes/backoffice/reports.php
app/Http/Controllers/Backoffice/Reports/*.php
app/Services/Reports/ReportService.php
resources/views/backoffice/reports/*.blade.php
```

**Acceptance Criteria:**
- Sales report shows revenue by period, top customers, outstanding invoices
- Finance report shows income vs expense by category
- All data strictly scoped to current tenant
- CSV export downloads correctly

**Complexity:** L

---

### PHASE 10 — SuperAdmin: Settings & Templates
**Objective:** Complete the SuperAdmin panel.

**Scope:** `routes/superadmin/settings.php`, `routes/superadmin/templates.php`.

**Technical Tasks:**
- [ ] Create `SuperAdmin/Settings/SettingsController.php`
- [ ] Create `SuperAdmin/Templates/TemplateCatalogController.php`
- [ ] Fill route files
- [ ] Create views

**Complexity:** S

---

### PHASE 11 — Dashboard KPIs (Backoffice)
**Objective:** Replace the stub dashboard with real aggregated data.

**Technical Tasks:**
- [ ] Implement `DashboardController@index` with tenant-scoped aggregate queries
- [ ] Build `backoffice/dashboard/index.blade.php` (ref: `admin-dashboard.blade.php`)
- [ ] KPI cards: total revenue (MTD), unpaid invoices count + amount, total customers, low stock alerts
- [ ] Revenue chart by month (last 12 months)
- [ ] Cache dashboard queries for 5 minutes (`Cache::remember()` with tenant-keyed key)

**Complexity:** S

---

### PHASE 12 — ActivityLog, Notifications & Pro Features
**Objective:** Production-readiness — audit trail, email, recurring invoices.

**Technical Tasks:**
- [ ] Wire `LogsActivity` (Spatie) to: Customer, Invoice, Payment, User models
- [ ] Email notifications: invoice sent, payment received, subscription renewal
- [ ] PDF generation: `barryvdh/laravel-dompdf` for invoice/quote PDF (`InvoiceController@download`)
- [ ] `RecurringInvoice` cron job → auto-generates Invoice on schedule
- [ ] `InvoiceReminder` → scheduled email via queue
- [ ] `spatie/laravel-backup` + scheduled daily backup

**Complexity:** L

---

### PHASE 13 — Testing Hardening & Security Audit
**Objective:** Reach minimum test coverage; complete security review.

**Technical Tasks:**
- [ ] Feature tests for every module (happy path + 403 + 404 + validation)
- [ ] `TenantIsolationTest` — cross-tenant data leak is impossible (comprehensive)
- [ ] Unit tests: `InvoiceService`, `PaymentService`, `StockService`, `DocumentNumberService`
- [ ] Security audit: mass assignment review, permission gate completeness
- [ ] Performance: add DB indexes for `tenant_id` on all major tables (if not already present)
- [ ] Configure Larastan at level 5 (`phpstan.neon`)
- [ ] Fix all Larastan errors

**Complexity:** L

---

## F) NEXT STEP — Execute Immediately

### Single Goal
**Start Phase 0 (Foundation fixes) immediately, then flow into Phase 1 (CRM).**
These two phases unblock everything else and establish the master pattern.

---

### Concrete Tasks — Exact Order

**Task 1 — Fix Cyrillic filename** *(~5 min)*
```
Rename: app/Models/Sales/DeliveryChallаn.php → DeliveryChallan.php
Update: any `use` or `class` references
```
This is a ticking time bomb on Linux and CI systems.

**Task 2 — Sanitize DemoTenantSeeder** *(~10 min)*
- Line 79: `bcrypt('superadmin123')` → `bcrypt(env('DEMO_SA_PASSWORD', 'secret'))`
- Line 103: `bcrypt('admin123')` → `bcrypt(env('DEMO_ADMIN_PASSWORD', 'secret'))`
- Line 128: `rochdi.karouali@glszentrum.com` → `manager@demo.local`
- Line 129: `bcrypt('password')` → `bcrypt(env('DEMO_USER_PASSWORD', 'secret'))`

**Task 3 — Fill `routes/backoffice/crm.php`** *(~15 min)*
```php
<?php

use App\Http\Controllers\Backoffice\CRM\CustomerController;
use App\Http\Controllers\Backoffice\CRM\CustomerAddressController;
use App\Http\Controllers\Backoffice\CRM\CustomerContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->as('crm.')->group(function () {
    Route::resource('customers', CustomerController::class);
    Route::resource('customers.addresses', CustomerAddressController::class)->shallow();
    Route::resource('customers.contacts',  CustomerContactController::class)->shallow();
});
```

**Task 4 — Rewrite `CustomerController`** *(~30 min)*
Convert all 5 methods from `response()->json()` to Blade returns + redirects.
Add `abort_unless(can(...))` to every action. Add `when($request->search)` to `index()`.

**Task 5 — Build 4 Customer Blade views** *(~1–2 hours)*
Use these exact reference files:
- `index.blade.php` → copy from `resources/views/customers.blade.php`
- `create.blade.php` → copy from `resources/views/add-customer.blade.php`
- `edit.blade.php` → copy from `resources/views/edit-customer.blade.php`
- `show.blade.php` → copy from `resources/views/customer-details.blade.php`

**Task 6 — Update sidebar** *(~15 min)*
Activate the CRM navigation group in `sidebar.blade.php` pointing to `route('bo.crm.customers.index')`.

---

### One Question Before I Start

> **Do you want `CustomerAddressController` and `CustomerContactController` to use
> inline modals** (same-page, JS-driven, like the `customer-details.blade.php` reference if it
> has modal patterns) **or full separate Blade pages** (create/edit in their own views)?

Say **"modals"** or **"pages"** and I will proceed immediately with no further questions.
I will infer all other decisions from the existing reference templates and project conventions.

---

## Appendix: New Module Checklist

Copy and use for every phase:

- [ ] Route file filled (`routes/backoffice/{module}.php`)
- [ ] Controller(s) in `app/Http/Controllers/Backoffice/{Domain}/`
- [ ] FormRequests updated — all `messages()` in French
- [ ] `abort_unless(can(...))` on every controller action
- [ ] Blade views in `resources/views/backoffice/{domain}/` from correct reference template
- [ ] Sidebar nav activated in `layout/partials/sidebar.blade.php`
- [ ] Route names follow `bo.{module}.{action}` convention
- [ ] Flash messages use French strings
- [ ] `@empty` / no-records state included in list view
- [ ] `->withQueryString()` on all `paginate()` calls
- [ ] Feature tests written and passing
- [ ] `SoftDeletes` + `$fillable` audit done for new models

---

## Appendix: Reference Template Lookup

| Page Type | Reference File |
|-----------|---------------|
| List / index (table + search + actions) | `resources/views/customers.blade.php` |
| Create form | `resources/views/add-customer.blade.php` |
| Edit form | `resources/views/edit-customer.blade.php` |
| Detail / show page | `resources/views/customer-details.blade.php` |
| Invoice list | `resources/views/invoices.blade.php` |
| Invoice create | `resources/views/add-invoice.blade.php` |
| Invoice detail | `resources/views/invoice-details.blade.php` |
| Quotation list | `resources/views/quotations.blade.php` |
| Quotation create | `resources/views/add-quotation.blade.php` |
| Product list | `resources/views/products.blade.php` |
| Product create | `resources/views/add-product.blade.php` |
| Purchases list | `resources/views/purchases.blade.php` |
| Purchase orders | `resources/views/purchase-orders.blade.php` |
| Inventory | `resources/views/inventory.blade.php` |
| Users list | `resources/views/users.blade.php` |
| Company settings | `resources/views/company-settings.blade.php` |
| Invoice settings | `resources/views/invoice-settings.blade.php` |
| Dashboard | `resources/views/admin-dashboard.blade.php` |
| Roles & Permissions | `resources/views/roles-permissions.blade.php` |

---

*Last updated: 2026-03-01 | Review after each completed phase.*
