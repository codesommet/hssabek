FinancialTransaction
├── type: 'income' | 'expense'
├── transactionable_type: 'Payment', 'SupplierPayment', 'Expense', 'Income', etc.
├── transactionable_id: UUID
├── bank_account_id: UUID
├── amount: decimal
├── transaction_date: date
├── description: auto-generated
└── auto_generated: boolean# Tasks — Facturation SaaS

> Production-grade task plan for a multi-tenant Laravel 12 SaaS.
> Every task in this folder is ordered, opinionated, and ready to execute.
> Generated: 2026-03-01

---

## Folder Structure

```
tasks/
├── README.md                              ← You are here (risk analysis + overview)
├── phases/
│   ├── phase-0-foundation-hardening.md
│   ├── phase-1-users-and-company-settings.md
│   ├── phase-2-crm.md
│   ├── phase-3-catalog.md
│   ├── phase-4-sales-core.md
│   ├── phase-5-purchases.md
│   ├── phase-6-inventory.md
│   ├── phase-7-finance.md
│   ├── phase-8-reports.md
│   ├── phase-9-pdf-and-email.md
│   └── phase-10-dashboard-kpis.md
└── checklists/
    ├── module-checklist.md
    ├── security-checklist.md
    └── testing-checklist.md
```

---

# PART 1 — DEEP RISK ANALYSIS: REAL WORLD THREATS

> Assumes this system has paying customers. Every risk described below is a
> realistic production failure mode, not a theoretical concern.

---

## GROUP 1 — Security & Multi-Tenant Data Leakage

---

### RISK-S1: `tenant_id` is Mass-Assignable on Critical Models

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A malicious tenant user sends a crafted POST request to `POST /backoffice/crm/customers`
with `tenant_id=<other_tenant_uuid>` in the form body. Because `tenant_id` is in `$fillable`
on `Customer` (line 14), `Invoice` (line 14), and likely every other tenant-owned model,
`Customer::create($request->validated())` would create a customer under the wrong tenant.
That customer is now invisible to the attacking tenant (scoped away) but visible to the
victim tenant — a silent data injection attack. If invoices are created this way, the victim
tenant has corrupted financial records they cannot explain.

**Evidence in Code:**
- `app/Models/CRM/Customer.php:14` — `tenant_id` in `$fillable`
- `app/Models/Sales/Invoice.php:14` — `tenant_id` in `$fillable`
- All scaffolded controllers use `Model::create($request->validated())`

**How to Detect:**
- Feature test: POST with `tenant_id=<other_uuid>` → assert record has current tenant's ID
- Log: query log showing inserts with unexpected tenant_id values
- Monitoring: alert on `INSERT` where `tenant_id` differs from session tenant

**Exact Fix Strategy:**

Step 1 — Remove `tenant_id` from every model's `$fillable`:
```php
// app/Models/CRM/Customer.php
protected $fillable = [
    // REMOVE 'tenant_id' — BelongsToTenant trait fills it automatically
    'name', 'email', 'phone', 'customer_type', 'tax_id',
    'currency_id', 'credit_limit', 'credit_used', 'payment_terms', 'status', 'notes',
];
```

Step 2 — Never pass `tenant_id` through `$request->validated()`. The `BelongsToTenant`
trait's `creating()` hook fills it from `TenantContext::id()` automatically.

Step 3 — Add a global test helper `assertTenantIsolated(string $modelClass)` that verifies
no record can be created with a spoofed `tenant_id`.

**Preventive Rules:**
- ✅ DO: Remove `tenant_id` from `$fillable` on ALL tenant-owned models
- ✅ DO: Trust `BelongsToTenant::creating()` to fill `tenant_id`
- ❌ NEVER: Put `tenant_id` in a FormRequest's `rules()` as user-fillable
- ❌ NEVER: Pass `->all()` or `->validated()` directly if `tenant_id` could leak through

**Requires:** Model audit (no Service/Policy/Job needed — pure model change)

---

### RISK-S2: `TenantScope` Falls Back to `auth()->user()->tenant_id` — Dangerous in Queue Workers

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A queued job (e.g., `SendInvoiceEmailJob`) dispatched for Tenant A runs on a worker.
`TenantContext` is empty in the worker process. The `TenantScope::resolveTenantId()`
method falls back to `auth()->user()` — which returns `null` in a queue worker.
The scope returns `null` → **no tenant filter applied** → the job queries ALL invoices
across ALL tenants. If the job then iterates invoices to send emails, every tenant
gets every other tenant's invoice emails. This is a catastrophic data breach.

**Evidence in Code:**
- `app/Scopes/TenantScope.php:46-58` — fallback to `auth()->user()->tenant_id`
- `app/Services/Tenancy/TenantContext.php` — static singleton, cleared between requests
  but NOT set in queue workers

**How to Detect:**
- Unit test: Run `TenantScope::apply()` with no auth user and no TenantContext → assert
  query has `WHERE tenant_id = ?` (it currently does NOT — it applies no filter)
- Integration test: Dispatch a job, inspect the query log for missing tenant_id filter

**Exact Fix Strategy:**

Option A (Safest) — Make TenantScope fail-closed: if no tenant context, apply an
impossible filter instead of no filter:
```php
// app/Scopes/TenantScope.php
protected function resolveTenantId(): ?string
{
    if (TenantContext::check()) {
        return TenantContext::id();
    }
    if (app()->runningInConsole()) {
        return null; // Artisan commands: explicit, intentional bypass
    }
    $user = auth()->user();
    if ($user === null || $user->tenant_id === null) {
        return null; // SuperAdmin or unauthenticated — intentional bypass
    }
    return $user->tenant_id;
}
```

Option B — All jobs MUST serialize and restore TenantContext:
```php
// app/Jobs/Concerns/HasTenantContext.php (new trait)
trait HasTenantContext {
    public string $tenantId;
    public function withTenant(string $tenantId): static {
        $this->tenantId = $tenantId; return $this;
    }
    public function handle(): void {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);
        $this->execute(); // subclass implements execute()
        TenantContext::forget();
    }
}
```

**Preventive Rules:**
- ✅ DO: Every queued job that touches tenant data must set TenantContext at the start of `handle()`
- ✅ DO: Use `HasTenantContext` job trait to enforce this
- ❌ NEVER: Assume TenantContext is set in a queue worker
- ❌ NEVER: Dispatch a job without passing `tenant_id` as a serialized property

**Requires:** `app/Jobs/Concerns/HasTenantContext.php` (new trait), TenantScope hardening

---

### RISK-S3: `IdentifyTenantByDomain` Does Not Abort on Unknown Domain

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
An attacker spoofs the `Host` header: `curl -H "Host: victim.app.com" https://saas.com/backoffice/customers`.
`IdentifyTenantByDomain::handle()` finds no tenant → sets no TenantContext → continues.
The `EnsureTenantIsActive` middleware sees no tenant → also continues (line 15-18).
The `auth` middleware passes (user is logged in from their own subdomain session).
`TenantScope` falls back to `auth()->user()->tenant_id` → data is scoped correctly.
**This appears safe but is NOT** — if the user has `tenant_id = null` (SuperAdmin), the scope
applies no filter, exposing all data via the `/backoffice/` route which has no `isSuperAdmin`
middleware. Result: unauthenticated cross-tenant data access via Host header manipulation.

**How to Detect:**
- Feature test: Request `/backoffice/customers` with an invalid `Host` header → expect 404/403
- Security scanner: Check if `X-Forwarded-Host` or `Host` manipulation bypasses tenant

**Exact Fix Strategy:**
```php
// app/Http/Middleware/IdentifyTenantByDomain.php — add after $tenant resolution:
if (!$tenant) {
    // No tenant found for this domain — hard stop for backoffice routes
    abort(404, 'Domaine non reconnu.');
}
```

Note: This only applies to the `/backoffice/` route group. The `/admin/` and public routes
should remain unaffected. The cleanest fix is to add the abort inside the middleware itself,
guarded by the route prefix check, OR to add a separate `RequireTenant` middleware to the
backoffice route group.

**Requires:** Middleware change only

---

### RISK-S4: TenantContext Static Singleton Leaks Between Requests in Laravel Octane

**Severity:** 🟠 High (Medium if not using Octane; Critical if using Octane)

**Realistic Failure Scenario:**
If the app is deployed with Laravel Octane (Swoole/RoadRunner), the PHP process is
persistent across requests. `TenantContext::$tenant` is a static property. If the
`SetTenantContext` middleware sets it for Request A (Tenant A) but the process reuses
for Request B (Tenant B), and `IdentifyTenantByDomain` fails silently, Request B
reads Tenant A's data. This is a silent data leak that is nearly impossible to detect
without specific Octane testing.

**Exact Fix Strategy:**
- Short-term: Register TenantContext via the IoC container as a request-scoped singleton:
```php
// app/Providers/AppServiceProvider.php
$this->app->scoped(TenantContext::class, fn() => new TenantContext());
```
- Long-term: Convert `TenantContext` from a static singleton to an instance injected via
  constructor in services (proper DI).
- Add `TenantContext::forget()` call in a middleware terminating hook.

**Requires:** `AppServiceProvider` change, TenantContext refactor

---

## GROUP 2 — Authorization & Policy Gaps

---

### RISK-A1: No Policy Files — Any Authenticated Tenant User Can Access Any Resource

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A tenant has two users: Alice (admin role, all permissions) and Bob (receptionist role,
view-only). Bob knows the URL pattern. He navigates to
`/backoffice/crm/customers/{uuid}/edit` — the controller has no `$this->authorize()` or
`abort_unless(can(...))` call. Bob successfully edits a customer record he should never touch.
Since all 40+ business controllers are currently scaffolded as JSON stubs with zero
permission checks, the entire backoffice is effectively open to any authenticated tenant user.

**How to Detect:**
- Feature test: Login as `manager` role (no edit permission) → PUT `/backoffice/crm/customers/{id}` → expect 403
- Currently this test would PASS the wrong way (200 returned)

**Exact Fix Strategy:**
Every controller action must have a guard:
```php
// Option A — inline (use when no Policy file yet)
abort_unless(auth()->user()->can('crm.customers.edit'), 403);

// Option B — Policy (preferred, register in AppServiceProvider)
$this->authorize('update', $customer); // CustomerPolicy::update()
```

Policies go in `app/Policies/{Model}Policy.php`. Register in `AppServiceProvider`:
```php
Gate::policy(Customer::class, CustomerPolicy::class);
```

**Priority order for Policies:**
1. `InvoicePolicy` — financial data, highest sensitivity
2. `CustomerPolicy` — CRM data
3. `ProductPolicy` — pricing data
4. `PaymentPolicy` — money movement
5. All others

**Requires:** Policy files (new), `AppServiceProvider` registration, controller changes

---

### RISK-A2: SuperAdmin Routes Have No Rate Limiting or 2FA Enforcement

**Severity:** 🟠 High

**Realistic Failure Scenario:**
The SuperAdmin panel at `/admin/*` is protected only by `isSuperAdmin` middleware
(checking `tenant_id === null`). There is no rate limiting on login attempts, no 2FA,
and no IP allowlist. A brute-force or credential-stuffing attack that compromises the
SuperAdmin account gives full access to all tenant data across the entire platform.

**Exact Fix Strategy:**
- Add `throttle:10,1` to the login route
- Add `throttle:60,1` to all `/admin/*` routes
- Future: Add `laravel/fortify` with 2FA for SuperAdmin accounts specifically

**Requires:** Route middleware changes, `routes/web.php` update

---

### RISK-A3: Cross-Tenant Role Assignment Possible via API

**Severity:** 🟠 High

**Realistic Failure Scenario:**
`RolesPermissionsController@syncPermissions` accepts a `role_id` from the request.
If the Role model doesn't enforce tenant scoping during resolution, a tenant admin could
pass a `role_id` from another tenant and assign permissions to it. The current Spatie
Permission setup uses custom `Role` model with `tenant_id` — but route model binding
must resolve through the global scope to be safe.

**Exact Fix Strategy:**
- Ensure `Role` model uses `BelongsToTenant` trait (verify it does)
- In `RolesPermissionsController`, always scope role queries:
  `Role::where('tenant_id', TenantContext::id())->findOrFail($id)`
- Add Policy: `RolePolicy::update()` checks `$role->tenant_id === auth()->user()->tenant_id`

**Requires:** Model verification, Policy, controller hardening

---

## GROUP 3 — Data Integrity & Accounting Correctness

---

### RISK-D1: No SoftDeletes — Hard Deletes Corrupt Financial History

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A tenant admin deletes a Customer record. The `Customer::destroy()` issues a hard DELETE.
All associated `Invoice`, `Payment`, `CreditNote` records have FK constraints — either
they cascade-delete (destroying financial history) or the delete fails with a FK violation
(bad UX). If cascade delete is configured, the tenant loses all their invoice history for
that customer permanently. This is an accounting compliance violation.

**How to Detect:**
- Check migrations for `onDelete('cascade')` — if present, hard deletes cascade
- Feature test: Create customer → create invoice → delete customer → assert invoice still exists

**Exact Fix Strategy:**
Add `SoftDeletes` to these models (in priority order):
```php
// 1. app/Models/CRM/Customer.php
// 2. app/Models/Sales/Invoice.php
// 3. app/Models/Sales/Quote.php
// 4. app/Models/Sales/Payment.php
// 5. app/Models/Catalog/Product.php
// 6. app/Models/Purchases/Supplier.php
// 7. app/Models/User.php
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model {
    use HasUuids, SoftDeletes, BelongsToTenant;
    // deleted_at column must exist in migration — verify before adding
}
```

Check if `deleted_at` column exists in each migration before adding the trait.
If missing, create a new migration (additive — does not modify existing columns):
```php
Schema::table('customers', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Requires:** Model changes + additive migrations (no existing column modification)

---

### RISK-D2: `DocumentNumberSequence` Has No Race Condition Protection

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
Two users in the same tenant create invoices simultaneously. Both read
`current_number = 5` from `document_number_sequences`. Both increment to 6.
Both create `Invoice` records with `invoice_number = 'INV-00006'`.
The `invoice_number` field has no unique constraint in the migration (verify).
Result: two invoices with the same number → accounting system corruption →
impossible to reconcile → potential legal liability.

**Schema Check:** The `document_number_sequences` table has:
- `tenant_id`, `document_type`, `prefix`, `current_number`, `increment_by`, `suffix`
- No unique constraint visible on `(tenant_id, document_type)` — verify in migration

**Exact Fix Strategy:**
```php
// app/Services/System/DocumentNumberService.php (NEW FILE)
public function next(string $documentType): string
{
    $tenantId = TenantContext::id();

    return DB::transaction(function () use ($tenantId, $documentType) {
        // Pessimistic lock — only one process gets this row at a time
        $sequence = DocumentNumberSequence::where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->lockForUpdate()  // KEY: SELECT ... FOR UPDATE
            ->firstOrFail();

        $number = $sequence->current_number;
        $sequence->increment('current_number', $sequence->increment_by);

        $padded = str_pad($number, 5, '0', STR_PAD_LEFT);
        return ($sequence->prefix ?? '') . $padded . ($sequence->suffix ?? '');
        // Example: INV-00001
    });
}
```

Also add a unique constraint on `(tenant_id, document_type)` in a new migration:
```php
$table->unique(['tenant_id', 'document_type']); // additive — safe to add
```

And add a unique constraint on `invoice_number` scoped to tenant:
```php
$table->unique(['tenant_id', 'invoice_number']); // prevents duplicate numbers
```

**Requires:** New Service class, new additive migrations

---

### RISK-D3: Invoice Totals Are Stored But Not Verified on Every Write

**Severity:** 🟠 High

**Realistic Failure Scenario:**
A controller calls `Invoice::create(['total_amount' => $request->total_amount, ...])`.
The `total_amount` comes from the client (JavaScript calculation). If the client sends
a manipulated `total_amount` of 0.01 instead of 1000.00, the invoice is created with
the wrong total. There is no server-side recalculation that computes:
`total_amount = subtotal + tax_amount - discount`.

**Exact Fix Strategy:**
All financial totals MUST be calculated server-side in `InvoiceService`:
```php
// app/Services/Sales/TaxCalculationService.php
public function calculate(array $items, array $charges): array
{
    $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
    $taxAmount = collect($items)->sum(fn($i) => ($i['tax_rate'] / 100) * ($i['quantity'] * $i['unit_price']));
    $chargesTotal = collect($charges)->sum('amount');
    $totalAmount = $subtotal + $taxAmount + $chargesTotal;
    return compact('subtotal', 'taxAmount', 'totalAmount');
}
```

The `StoreInvoiceRequest` should NOT include `subtotal`, `tax_amount`, or `total_amount`
as user-submitted fields. These are computed exclusively server-side before the model is saved.

**Requires:** `TaxCalculationService` (new Service), `InvoiceService` orchestration, FormRequest rule removal for total fields

---

### RISK-D4: Payment Over-allocation Not Prevented

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
An invoice has `total_amount = 1000.00`. A payment of 600 is recorded. No server-side
check prevents a second payment allocation of 800 — bringing total paid to 1400,
which exceeds the invoice amount. The `PaymentAllocation` model has no constraint.
Result: accounting books show 400 over-collected — an accounting error that could
trigger customer disputes or tax authority problems.

**Exact Fix Strategy:**
```php
// app/Services/Sales/PaymentService.php
public function allocate(Payment $payment, Invoice $invoice, float $amount): void
{
    $alreadyAllocated = $invoice->payments()
        ->where('id', '!=', $payment->id)
        ->sum('pivot_amount'); // or sum on PaymentAllocation

    $outstanding = $invoice->total_amount - $alreadyAllocated;

    if ($amount > $outstanding) {
        throw new \DomainException(
            "Montant d'allocation ({$amount}) dépasse le solde restant ({$outstanding})."
        );
    }
    // proceed with allocation
}
```

Add DB constraint:
```sql
-- In new migration: CHECK constraint (if MySQL 8.0+ or PostgreSQL)
ALTER TABLE payment_allocations ADD CONSTRAINT chk_amount_positive CHECK (amount > 0);
```

**Requires:** `PaymentService` (new Service), additive migration for constraint

---

## GROUP 4 — Financial Correctness

---

### RISK-F1: CreditNote Double-Application

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A credit note of 500 is issued for Invoice A. A clerk accidentally applies it twice
to Invoice A. The `CreditNoteApplication` model has no unique constraint on
`(credit_note_id, invoice_id)`. The invoice now shows a deduction of 1000 from a
500 credit note. The tenant's books show incorrect receivables.

**Exact Fix Strategy:**
Add unique constraint in new migration:
```php
// New additive migration
$table->unique(['credit_note_id', 'invoice_id']);
```

And validate in `CreditNoteService::apply()`:
```php
if (CreditNoteApplication::where('credit_note_id', $creditNote->id)
    ->where('invoice_id', $invoice->id)->exists()) {
    throw new \DomainException('Cette note de crédit est déjà appliquée à cette facture.');
}
```

**Requires:** New additive migration, `CreditNoteService` (new Service)

---

### RISK-F2: Invoice Status Machine Has No Guard Rails

**Severity:** 🟠 High

**Realistic Failure Scenario:**
Invoice status is stored as a string in the `status` column with no DB-level enum
or application-level state machine. A controller could set a `paid` invoice back to
`draft`, allowing re-editing of financial data that is legally locked. A user who
knows the API could call `PUT /backoffice/sales/invoices/{id}` with `status=draft`
on a fully paid invoice, then change its amounts, effectively falsifying records.

**Exact Fix Strategy:**
Implement status transition validation in `InvoiceService`:
```php
// app/Services/Sales/InvoiceService.php
private const ALLOWED_TRANSITIONS = [
    'draft'   => ['sent', 'cancelled'],
    'sent'    => ['partial', 'paid', 'cancelled'],
    'partial' => ['paid', 'cancelled'],
    'paid'    => [],        // Terminal state — no transitions allowed
    'cancelled' => [],      // Terminal state
];

public function transition(Invoice $invoice, string $newStatus): void
{
    $allowed = self::ALLOWED_TRANSITIONS[$invoice->status] ?? [];
    if (!in_array($newStatus, $allowed)) {
        throw new \DomainException(
            "Transition invalide: {$invoice->status} → {$newStatus}"
        );
    }
    $invoice->update(['status' => $newStatus]);
}
```

**Requires:** `InvoiceService` (new Service), controller change (never allow direct `status` field update)

---

## GROUP 5 — Media / File Isolation Risks

---

### RISK-M1: MediaLibrary Files Are Not Tenant-Partitioned

**Severity:** 🟠 High

**Realistic Failure Scenario:**
`UuidPathGenerator` stores files at `/{uuid}/{filename}`. The UUID is predictable if
the attacker obtains any media UUID (e.g., from an invoice PDF URL they received).
A company logo uploaded by Tenant A is stored at `/storage/media/{uuid}/logo.png`.
Tenant B, knowing this URL pattern, could enumerate UUIDs and access competitor files.

**Exact Fix Strategy:**
Modify `app/Support/MediaLibrary/UuidPathGenerator.php` to prefix with `tenant_id`:
```php
// app/Support/MediaLibrary/UuidPathGenerator.php
public function getPath(Media $media): string
{
    $tenantId = $media->model->tenant_id ?? 'global';
    return $tenantId . '/' . $media->uuid . '/';
}

public function getPathForConversions(Media $media): string
{
    $tenantId = $media->model->tenant_id ?? 'global';
    return $tenantId . '/' . $media->uuid . '/conversions/';
}
```

Also ensure the storage disk uses private visibility with signed URLs for sensitive files.

**Note:** Existing files will not be moved automatically. Run a one-time migration script
for existing media if any exist in production.

**Requires:** `UuidPathGenerator` change (additive path prefix)

---

## GROUP 6 — Performance & Scaling Risks

---

### RISK-P1: N+1 Query Problem on Every List Page

**Severity:** 🟠 High

**Realistic Failure Scenario:**
`CustomerController@index` returns `Customer::paginate(15)`. The Blade view then
loops and calls `$customer->invoices()->count()` for each row. This generates
15 + 1 = 16 queries per page load. At 100 concurrent users: 1,600 queries per
second on a single table. The database collapses under load.

**Exact Fix Strategy:**
Always eager-load known relationships and use `withCount()`:
```php
$customers = Customer::query()
    ->withCount(['invoices', 'quotes'])
    ->with(['currency'])
    ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%"))
    ->latest()
    ->paginate(15)
    ->withQueryString();
```

Use Laravel Debugbar in development to catch N+1 before deployment.

**Requires:** Controller rewrite (per phase), `.env` Debugbar setup

---

### RISK-P2: `SetTenantContext` Loads `TenantSetting` on Every Request

**Severity:** 🟡 Medium

**Realistic Failure Scenario:**
Every single HTTP request triggers `$tenant->settings` (an Eloquent query) in
`SetTenantContext::handle()`. With 100 requests/second, this is 100 additional
`SELECT * FROM tenant_settings WHERE tenant_id = ?` queries per second for
potentially simple requests like asset loading or health checks.

**Exact Fix Strategy:**
```php
// Cache tenant settings per-request using the IoC container, or cache for 5 minutes:
$settings = Cache::remember("tenant_settings:{$tenant->id}", 300, fn() => $tenant->settings);
```

**Requires:** `SetTenantContext` middleware change, Laravel Cache setup

---

### RISK-P3: No Database Indexes on `tenant_id` Foreign Keys

**Severity:** 🟠 High

**Realistic Failure Scenario:**
Every query against tenant-owned tables includes `WHERE tenant_id = ?`. Without an
index on `tenant_id`, MySQL performs a full table scan. At 10,000 invoices across
50 tenants, a query for one tenant's invoices scans all 10,000 rows. Response time:
50ms → 5s. At 100,000 rows: unusable.

**Exact Fix Strategy:**
Verify each migration has `$table->index('tenant_id')` or `$table->foreign('tenant_id')`.
Laravel's `foreignUuid('tenant_id')` creates the index automatically. Add missing indexes
via new additive migrations if any are absent.

**Requires:** Migration audit + additive migrations for missing indexes

---

## GROUP 7 — Logging, Audit & Forensic Risks

---

### RISK-L1: Spatie ActivityLog Is Installed But Wired to Zero Models

**Severity:** 🟠 High

**Realistic Failure Scenario:**
A tenant admin accuses a staff member of deleting an invoice. There is no audit log.
No one can prove what happened, when, or who did it. The tenant escalates to you
(the SaaS owner) demanding a forensic trail. You have nothing to show them.
In regulated industries (France: RGPD, accounting law), absence of audit trail is a
compliance failure.

**Exact Fix Strategy:**
Add `LogsActivity` trait to all financially sensitive models:
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model {
    use HasUuids, SoftDeletes, BelongsToTenant, LogsActivity;

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice');
    }
}
```

Priority models: `Invoice`, `Payment`, `CreditNote`, `Customer`, `User`, `Role`.

**Requires:** Model changes (additive trait addition)

---

### RISK-L2: No Login Security Event Logging

**Severity:** 🟡 Medium

**Realistic Failure Scenario:**
A compromised account logs in from an unusual IP. The `LoginLog` model exists but
the `LoginController` never writes to it. No alert is triggered. The breach goes
undetected until the tenant notices their data has been modified.

**Exact Fix Strategy:**
```php
// app/Http/Controllers/Auth/LoginController.php — after successful authentication:
LoginLog::create([
    'user_id'    => auth()->id(),
    'tenant_id'  => auth()->user()->tenant_id,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'logged_in_at' => now(),
]);
```

Also update `User::last_login_at` and `User::last_login_ip` on successful login.

**Requires:** `LoginController` change

---

## GROUP 8 — Developer Experience & Regression Risks

---

### RISK-E1: Zero Test Coverage — Any Change Can Break Production

**Severity:** 🔴 Critical

**Realistic Failure Scenario:**
A developer changes the `TenantScope::resolveTenantId()` logic. The change looks
correct in isolation. It passes a manual test. It ships to production. Three hours later,
tenant B's users see tenant A's invoices — a full data breach. There were no tests
to catch this. The issue is only discovered when a tenant reports it.

**Exact Fix Strategy:**
Minimum test suite before shipping any business module:
```
tests/Feature/Tenancy/TenantIsolationTest.php   ← Highest priority
tests/Feature/Auth/LoginTest.php
tests/Feature/CRM/CustomerCrudTest.php          ← Per module
tests/Unit/Services/DocumentNumberServiceTest.php
tests/Unit/Services/InvoiceServiceTest.php
tests/Unit/Scopes/TenantScopeTest.php
```

**Requires:** No architectural change — just writing tests (see `testing-checklist.md`)

---

### RISK-E2: No CI/CD — Broken Code Ships to Production

**Severity:** 🟠 High

**Realistic Failure Scenario:**
Developer pushes a commit that breaks the `TenantScope`. There is no CI pipeline to
run `php artisan test`. The bug ships to production within minutes.

**Exact Fix Strategy:**
Add `.github/workflows/ci.yml`:
```yaml
- run: php artisan test --parallel
- run: ./vendor/bin/phpstan analyse --level=5
- run: ./vendor/bin/pint --test
```

**Requires:** GitHub Actions workflow file (new file, no code changes)

---

## PRIORITY FIX ORDER

Execute in this exact order. Each item unblocks the next.

| Priority | Risk ID | Action | Why First |
|----------|---------|--------|-----------|
| 1 | RISK-S1 | Remove `tenant_id` from `$fillable` on ALL models | Prevents active data injection; 5-minute fix |
| 2 | RISK-D2 | Implement `DocumentNumberService` with `lockForUpdate()` | Sales module is blocked without it |
| 3 | RISK-A1 | Add `abort_unless(can(...))` to every controller action | Every new page built without this is vulnerable |
| 4 | RISK-S3 | Make `IdentifyTenantByDomain` abort on unknown domain | Closes Host-spoofing attack vector |
| 5 | RISK-D1 | Add `SoftDeletes` to Customer, Invoice, Quote, Product, User, Supplier | Prevents permanent data loss |
| 6 | RISK-S2 | Add `HasTenantContext` job trait for all future jobs | Required before any queued job is written |
| 7 | RISK-F2 | Implement Invoice status machine in `InvoiceService` | Required before Sales module ships |
| 8 | RISK-D3 | Implement `TaxCalculationService` — server-side totals only | Required before Sales module ships |
| 9 | RISK-D4 | Implement payment over-allocation check in `PaymentService` | Required before payment recording works |
| 10 | RISK-M1 | Add tenant_id prefix to `UuidPathGenerator` | Required before media upload works |
| 11 | RISK-L1 | Wire `LogsActivity` to Invoice, Payment, Customer, User | Required for RGPD compliance |
| 12 | RISK-P1 | Eager-load relationships on all list controllers | Required before performance testing |
| 13 | RISK-E1 | Write tenant isolation feature test | Required before any module ships to production |
| 14 | RISK-P3 | Audit and add missing `tenant_id` indexes | Required before production load |

---

## Phase Overview

| Phase | Name | Complexity | Risk Level |
|-------|------|-----------|------------|
| 0 | Foundation Hardening | S | 🔴 Critical path |
| 1 | Users & Company Settings | M | 🟠 High |
| 2 | CRM | M | 🟡 Medium |
| 3 | Catalog | M | 🟡 Medium |
| 4 | Sales Core | L | 🔴 Critical path |
| 5 | Purchases | L | 🟠 High |
| 6 | Inventory | M | 🟡 Medium |
| 7 | Finance | M | 🟠 High |
| 8 | Reports | M | 🟡 Medium |
| 9 | PDF & Email | L | 🟠 High |
| 10 | Dashboard KPIs | S | 🟡 Medium |
