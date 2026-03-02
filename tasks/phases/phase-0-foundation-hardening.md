# Phase 0 — Foundation Hardening

> **Status:** Must complete before ANY other phase begins.
> **Complexity:** S
> **Risk if skipped:** Every module built on top will inherit critical security flaws.

---

## 1. Objective

Fix the five categories of architectural defects that will cause production failures:
1. Mass-assignable `tenant_id` on every model
2. Missing `SoftDeletes` on financially critical models
3. Cyrillic filename bug (`DeliveryChallаn.php`)
4. Domain middleware not aborting on unknown host
5. `DocumentNumberService` not implemented (blocks Sales)

No new UI is built in this phase. The output is a rock-solid foundation.

---

## 2. Scope

**Routes:** No route changes
**Controllers:** `IdentifyTenantByDomain` middleware only
**Models:** Customer, Invoice, Quote, Payment, CreditNote, Product, Supplier, User, DeliveryChallan
**Services:** `DocumentNumberService` (new)
**Migrations:** Additive only — add `deleted_at`, unique constraints, indexes

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Tenant isolation | `app/Scopes/TenantScope.php` | Existing — verify |
| Sequential doc numbers | `app/Services/System/DocumentNumberService.php` | NEW Service |
| Mass-assignment block | All tenant-owned models | Model `$fillable` audit |
| Soft delete | Customer, Invoice, Quote, Payment, Product, Supplier, User | Model trait + migration |
| Domain abort | `app/Http/Middleware/IdentifyTenantByDomain.php` | Middleware change |
| Tenant isolation tests | `tests/Feature/Tenancy/TenantIsolationTest.php` | NEW Test |
| Scope unit test | `tests/Unit/Scopes/TenantScopeTest.php` | NEW Test |

**No Policies, Jobs, or Notifications in this phase.**

---

## 4. Ordered Task Breakdown

### Task 0.1 — Fix Cyrillic Filename

```
RENAME: app/Models/Sales/DeliveryChallаn.php
     TO: app/Models/Sales/DeliveryChallan.php
```

Search all files for the old name and update:
- Any `use App\Models\Sales\DeliveryChallаn` statements
- Any `class DeliveryChallаn` declaration
- Any `@return` phpdoc references
- Any relationship methods in other models
- Any route model binding references

Verify the rename with:
```bash
php artisan clear-compiled
php artisan optimize:clear
grep -r "DeliveryChall" app/ routes/ --include="*.php"
```

---

### Task 0.2 — Remove `tenant_id` from `$fillable` on ALL Tenant-Owned Models

The `BelongsToTenant` trait's `creating()` hook fills `tenant_id` automatically.
Having it in `$fillable` allows client-side mass-assignment attacks.

**Models to fix (remove `tenant_id` from `$fillable`):**

```
app/Models/CRM/Customer.php
app/Models/CRM/CustomerAddress.php
app/Models/CRM/CustomerContact.php
app/Models/Catalog/Product.php
app/Models/Catalog/ProductCategory.php
app/Models/Catalog/TaxGroup.php
app/Models/Catalog/TaxCategory.php
app/Models/Catalog/Unit.php
app/Models/Sales/Invoice.php
app/Models/Sales/InvoiceItem.php
app/Models/Sales/InvoiceCharge.php
app/Models/Sales/Quote.php
app/Models/Sales/QuoteItem.php
app/Models/Sales/QuoteCharge.php
app/Models/Sales/Payment.php
app/Models/Sales/CreditNote.php
app/Models/Sales/DeliveryChallan.php
app/Models/Purchases/Supplier.php
app/Models/Purchases/PurchaseOrder.php
app/Models/Purchases/VendorBill.php
app/Models/Inventory/Warehouse.php
app/Models/Inventory/ProductStock.php
app/Models/Inventory/StockMovement.php
app/Models/Inventory/StockTransfer.php
app/Models/Finance/BankAccount.php
app/Models/Finance/Currency.php
app/Models/Finance/Expense.php
app/Models/Finance/FinanceCategory.php
app/Models/System/DocumentNumberSequence.php
```

**Rule:** After removing, test that `Model::create([...without tenant_id...])` still
fills `tenant_id` correctly from TenantContext.

---

### Task 0.3 — Verify `BelongsToTenant` Is Applied to All Tenant-Owned Models

Every model in the list above must have:
```php
use App\Traits\BelongsToTenant;

class Customer extends Model
{
    use HasUuids, BelongsToTenant; // ← required
}
```

Also verify the `tenant()` relationship is defined (the trait provides it, but confirm
no model has overridden it incorrectly).

---

### Task 0.4 — Add `SoftDeletes` to Financially Critical Models

**Step 1:** Check if `deleted_at` column exists in each migration:
```bash
grep -r "softDeletes\|deleted_at" database/migrations/ --include="*.php" -l
```

**Step 2:** For models WITHOUT `deleted_at` in their migration, create additive migrations:
```php
// Example: database/migrations/YYYY_MM_DD_000001_add_soft_deletes_to_customers_table.php
public function up(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->softDeletes(); // Adds deleted_at TIMESTAMP NULL
    });
}
public function down(): void
{
    Schema::table('customers', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
```

**Step 3:** Add the trait to each model:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;
}
```

**Priority order for SoftDeletes:**
1. `app/Models/CRM/Customer.php`
2. `app/Models/Sales/Invoice.php`
3. `app/Models/Sales/Quote.php`
4. `app/Models/Sales/Payment.php`
5. `app/Models/Sales/CreditNote.php`
6. `app/Models/Catalog/Product.php`
7. `app/Models/Purchases/Supplier.php`
8. `app/Models/Purchases/PurchaseOrder.php`
9. `app/Models/Purchases/VendorBill.php`
10. `app/Models/User.php`

---

### Task 0.5 — Harden `IdentifyTenantByDomain` Middleware

Current behavior: continues silently when no tenant found (allows Host spoofing).

Fix: abort for `/backoffice/` prefixed paths when no tenant is resolved.

```php
// app/Http/Middleware/IdentifyTenantByDomain.php
public function handle(Request $request, Closure $next)
{
    $host = $request->getHost();
    $hostWithPort = $request->getHttpHost();

    $tenantDomain = TenantDomain::where('domain', $hostWithPort)->first()
        ?? TenantDomain::where('domain', $host)->first();

    if ($tenantDomain) {
        $tenant = $tenantDomain->tenant;
    } else {
        $parts = explode('.', $host);
        $tenant = (count($parts) >= 2)
            ? Tenant::where('slug', $parts[0])->first()
            : null;
    }

    if ($tenant) {
        TenantContext::set($tenant);
        app()->instance('tenant', $tenant);
        $request->attributes->set('tenant', $tenant);
    } elseif ($request->is('backoffice/*')) {
        // Hard stop: no tenant found for this domain on a backoffice route
        abort(404, 'Domaine non reconnu.');
    }

    return $next($request);
}
```

---

### Task 0.6 — Implement `DocumentNumberService`

This service is required by Sales, Purchases, and all document creation flows.

```php
// app/Services/System/DocumentNumberService.php
<?php

namespace App\Services\System;

use App\Models\System\DocumentNumberSequence;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    /**
     * Generate the next document number for the given type.
     * Uses pessimistic locking to prevent race conditions.
     *
     * @param string $documentType  e.g. 'invoice', 'quote', 'purchase_order', 'credit_note'
     * @return string  e.g. 'INV-00001'
     * @throws \RuntimeException if no sequence configured for this tenant+type
     */
    public function next(string $documentType): string
    {
        $tenantId = TenantContext::id()
            ?? throw new \RuntimeException('TenantContext not set.');

        return DB::transaction(function () use ($tenantId, $documentType) {
            $sequence = DocumentNumberSequence::where('tenant_id', $tenantId)
                ->where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                // Auto-create a default sequence for this tenant+type
                $sequence = DocumentNumberSequence::create([
                    'tenant_id'      => $tenantId,
                    'document_type'  => $documentType,
                    'prefix'         => strtoupper(substr($documentType, 0, 3)) . '-',
                    'current_number' => 1,
                    'increment_by'   => 1,
                    'suffix'         => null,
                ]);
            }

            $number = $sequence->current_number;

            // Increment and save using direct SQL to avoid Model events re-triggering
            DocumentNumberSequence::where('id', $sequence->id)
                ->update(['current_number' => $number + $sequence->increment_by]);

            $padded = str_pad((string) $number, 5, '0', STR_PAD_LEFT);

            return ($sequence->prefix ?? '') . $padded . ($sequence->suffix ?? '');
        });
    }
}
```

Register in `AppServiceProvider`:
```php
// app/Providers/AppServiceProvider.php
use App\Services\System\DocumentNumberService;

public function register(): void
{
    $this->app->singleton(DocumentNumberService::class);
}
```

Add unique constraint migration (additive):
```php
// New migration: add_unique_to_document_number_sequences
$table->unique(['tenant_id', 'document_type'], 'doc_seq_tenant_type_unique');
```

---

### Task 0.7 — Sanitize `DemoTenantSeeder.php`

```php
// database/seeders/DemoTenantSeeder.php — replace hardcoded values:

// BEFORE:
'password' => bcrypt('superadmin123'),
'email' => 'rochdi.karouali@glszentrum.com',

// AFTER:
'password' => bcrypt(env('DEMO_SA_PASSWORD', 'secret')),
'email' => 'manager@demo.local',
'password' => bcrypt(env('DEMO_USER_PASSWORD', 'secret')),
```

Add to `.env.example`:
```
DEMO_SA_PASSWORD=
DEMO_ADMIN_PASSWORD=
DEMO_USER_PASSWORD=
```

---

### Task 0.8 — Add Additive Unique Constraint on `invoice_number`

To prevent duplicate invoice numbers per tenant (defense-in-depth alongside `DocumentNumberService`):

```php
// New migration
Schema::table('invoices', function (Blueprint $table) {
    $table->unique(['tenant_id', 'invoice_number'], 'invoices_tenant_number_unique');
});
// Similarly for quotes, purchase_orders if they have number fields
```

---

### Task 0.9 — Write Foundation Tests

#### `tests/Feature/Tenancy/TenantIsolationTest.php`
```php
it('prevents tenant A from reading tenant B customer', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $userA = User::factory()->for($tenantA)->create();
    $customerB = Customer::factory()->for($tenantB)->create();

    TenantContext::set($tenantA);
    $found = Customer::find($customerB->id);

    expect($found)->toBeNull();
});

it('prevents tenant_id mass assignment', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    TenantContext::set($tenantA);

    $customer = Customer::create([
        'name'      => 'Test',
        'tenant_id' => $tenantB->id, // ← attempt injection
        'email'     => 'test@test.com',
        'customer_type' => 'individual',
        'status'    => 'active',
    ]);

    expect($customer->tenant_id)->toBe($tenantA->id); // Must be tenant A
});
```

#### `tests/Unit/Services/DocumentNumberServiceTest.php`
```php
it('generates sequential document numbers', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);

    $service = new DocumentNumberService();
    $first  = $service->next('invoice');
    $second = $service->next('invoice');

    expect($first)->toMatch('/^INV-\d{5}$/');
    expect($second)->not->toBe($first);
});
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `app/Models/Sales/DeliveryChallan.php` | Renamed (Cyrillic removed) |
| All 29+ tenant-owned models | `tenant_id` removed from `$fillable` |
| 10 critical models | `SoftDeletes` trait added |
| Additive migrations for `deleted_at` | Created for models missing it |
| Additive migration: `doc_seq unique` | Created |
| Additive migration: `invoices unique(tenant_id, invoice_number)` | Created |
| `app/Http/Middleware/IdentifyTenantByDomain.php` | Hardened |
| `app/Services/System/DocumentNumberService.php` | New |
| `app/Providers/AppServiceProvider.php` | Service registered |
| `database/seeders/DemoTenantSeeder.php` | Sanitized |
| `.env.example` | Updated |
| `tests/Feature/Tenancy/TenantIsolationTest.php` | New |
| `tests/Unit/Services/DocumentNumberServiceTest.php` | New |

---

## 6. Acceptance Criteria

- [ ] `php artisan migrate:fresh --seed` completes without errors
- [ ] Login as superadmin → arrives at `/admin/dashboard`
- [ ] Login as tenant admin → arrives at `/backoffice/dashboard`
- [ ] GET `/backoffice/dashboard` with spoofed unknown `Host` header → 404
- [ ] `DocumentNumberService::next('invoice')` returns `INV-00001` then `INV-00002`
- [ ] Concurrent calls to `DocumentNumberService::next()` produce no duplicates (run 10 simultaneous)
- [ ] Creating a Customer with `tenant_id=<other_uuid>` in payload → record has correct tenant's ID
- [ ] Deleting a Customer with SoftDeletes → record has `deleted_at` set, not hard-deleted
- [ ] `php artisan test` passes all tests
- [ ] No `tenant_id` in any model's `$fillable` array

---

## 7. Tests Required

| Test File | Type | What It Proves |
|-----------|------|----------------|
| `tests/Feature/Tenancy/TenantIsolationTest.php` | Feature | Cross-tenant query blocked |
| `tests/Feature/Tenancy/MassAssignmentTest.php` | Feature | tenant_id cannot be injected |
| `tests/Unit/Services/DocumentNumberServiceTest.php` | Unit | Sequential, unique numbers |
| `tests/Unit/Scopes/TenantScopeTest.php` | Unit | Scope applies correct filter |

---

## 8. Multi-Tenant Pitfalls to Avoid

- ❌ DO NOT add `tenant_id` back to `$fillable` for any reason
- ❌ DO NOT use `withoutGlobalScopes()` in any controller
- ❌ DO NOT call `Customer::all()` without TenantContext set (returns everything in CLI)
- ✅ DO use `TenantContext::set($tenant)` at the start of any Artisan command that touches tenant data
- ✅ DO verify `TenantContext::id() !== null` before any `Model::create()` in console commands

---

## 9. Notes on Existing Schema

- `DocumentNumberSequence` has: `tenant_id`, `document_type`, `prefix`, `current_number`, `increment_by`, `suffix` — use all fields as-is
- `invoices` has `invoice_number` — add unique constraint `(tenant_id, invoice_number)` additively
- Do NOT rename any column. Do NOT change types.
- `SoftDeletes` only adds `deleted_at TIMESTAMP NULL` — fully additive, backward-compatible

---

## 10. UI Instructions

No UI changes in this phase.
