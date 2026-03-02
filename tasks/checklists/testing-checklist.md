# Testing Checklist

> Minimum test coverage required before any module ships to production.
> Tests are not optional. Every unchecked item is a production incident waiting to happen.

---

## TESTING PHILOSOPHY

This project uses **feature tests** as the primary defense layer.
Feature tests simulate real HTTP requests through the full middleware stack —
this is the only way to catch tenant isolation failures, permission gaps, and
business logic errors before they reach production.

Unit tests cover pure business logic in Services (tax calculation, payment allocation,
document number generation) where the logic is complex enough to warrant isolation.

**Test file locations:**
```
tests/
├── Feature/
│   ├── Tenancy/         ← HIGHEST PRIORITY — test before anything else
│   ├── Auth/
│   ├── CRM/
│   ├── Catalog/
│   ├── Sales/
│   ├── Purchases/
│   ├── Inventory/
│   ├── Finance/
│   ├── Reports/
│   ├── Users/
│   └── Settings/
└── Unit/
    ├── Scopes/
    ├── Services/
    └── Traits/
```

---

## FOUNDATION TESTS (Must pass BEFORE any module ships)

### `tests/Feature/Tenancy/TenantIsolationTest.php`

```php
it('blocks tenant A from reading tenant B customer', function () {
    [$tenantA, $tenantB] = createTwoTenants();
    $userA     = User::factory()->for($tenantA)->create();
    $customerB = Customer::factory()->for($tenantB)->create();

    TenantContext::set($tenantA);
    auth()->login($userA);

    $found = Customer::find($customerB->id);
    expect($found)->toBeNull(); // Scoped away
});

it('blocks tenant A from reading tenant B invoice via HTTP', function () {
    [$tenantA, $tenantB] = createTwoTenants();
    $userA   = loginAsTenantUser($tenantA, 'sales.invoices.view');
    $invoice = Invoice::factory()->for($tenantB)->create();

    $response = $this->actingAs($userA)
        ->get("/backoffice/sales/invoices/{$invoice->id}");

    $response->assertStatus(404); // TenantScope makes it invisible
});

it('prevents tenant_id mass assignment', function () {
    [$tenantA, $tenantB] = createTwoTenants();
    TenantContext::set($tenantA);
    auth()->login(User::factory()->for($tenantA)->create());

    $customer = Customer::create([
        'name'          => 'Injected',
        'tenant_id'     => $tenantB->id, // ← attempted injection
        'customer_type' => 'individual',
        'status'        => 'active',
    ]);

    expect($customer->tenant_id)->toBe($tenantA->id); // Must be A
});
```

- [ ] `TenantIsolationTest` exists and covers Customer, Invoice, and Product
- [ ] Mass assignment test exists for at least 3 models

### `tests/Unit/Scopes/TenantScopeTest.php`

```php
it('applies where clause when tenant context is set', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);

    $query = Customer::query();
    $sql   = $query->toSql();

    expect($sql)->toContain('tenant_id');
});

it('applies no filter for superadmin (tenant_id = null)', function () {
    TenantContext::set(null); // No tenant
    auth()->login(User::factory()->create(['tenant_id' => null]));

    $query = Customer::query();
    $sql   = $query->toSql();

    // SuperAdmin sees all — no tenant filter
    expect($sql)->not->toContain('tenant_id');
});
```

- [ ] TenantScope unit test exists

---

## AUTH TESTS

### `tests/Feature/Auth/LoginTest.php`

- [ ] Correct credentials → redirect to `/dashboard`
- [ ] Wrong password → redirect back with error, no session created
- [ ] Unverified email → redirect to verify-email page
- [ ] Suspended tenant user → blocked by `EnsureTenantIsActive`
- [ ] SuperAdmin login → redirect to `/admin/dashboard`
- [ ] `LoginLog` record created on successful login

---

## PER-MODULE TEST REQUIREMENTS

Every module must have at minimum ONE feature test file covering all of the following:

### Standard Feature Test Template

```php
// tests/Feature/CRM/CustomerCrudTest.php

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user   = User::factory()->for($this->tenant)->create();
    $this->role   = Role::factory()->for($this->tenant)->withPermissions([
        'crm.customers.view',
        'crm.customers.create',
        'crm.customers.edit',
        'crm.customers.delete',
    ])->create();
    $this->user->syncRoles([$this->role]);
    TenantContext::set($this->tenant);
});

// 1. Unauthenticated redirect
it('redirects unauthenticated user to login', function () {
    $this->get('/backoffice/crm/customers')->assertRedirect('/login');
});

// 2. Index — authenticated + scoped
it('shows only current tenant customers', function () {
    $otherTenant   = Tenant::factory()->create();
    $ownCustomer   = Customer::factory()->for($this->tenant)->create(['name' => 'Own']);
    $otherCustomer = Customer::factory()->for($otherTenant)->create(['name' => 'Other']);

    $response = $this->actingAs($this->user)
        ->get('/backoffice/crm/customers');

    $response->assertOk()
        ->assertSeeText('Own')
        ->assertDontSeeText('Other');
});

// 3. Create — happy path
it('creates customer and redirects', function () {
    $response = $this->actingAs($this->user)
        ->post('/backoffice/crm/customers', [
            'name'          => 'Nouveau Client',
            'customer_type' => 'company',
            'status'        => 'active',
        ]);

    $response->assertRedirect('/backoffice/crm/customers');
    $this->assertDatabaseHas('customers', [
        'name'      => 'Nouveau Client',
        'tenant_id' => $this->tenant->id,
    ]);
});

// 4. Create — validation failure
it('returns validation error for missing name', function () {
    $this->actingAs($this->user)
        ->post('/backoffice/crm/customers', ['customer_type' => 'individual'])
        ->assertSessionHasErrors('name');
});

// 5. Edit — happy path
it('updates customer and redirects', function () {
    $customer = Customer::factory()->for($this->tenant)->create(['name' => 'Old Name']);

    $this->actingAs($this->user)
        ->put("/backoffice/crm/customers/{$customer->id}", [
            'name'          => 'New Name',
            'customer_type' => 'individual',
            'status'        => 'active',
        ])
        ->assertRedirect('/backoffice/crm/customers');

    expect($customer->fresh()->name)->toBe('New Name');
});

// 6. Delete — soft delete
it('soft-deletes customer', function () {
    $customer = Customer::factory()->for($this->tenant)->create();

    $this->actingAs($this->user)
        ->delete("/backoffice/crm/customers/{$customer->id}")
        ->assertRedirect('/backoffice/crm/customers');

    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

// 7. Authorization — missing permission
it('returns 403 when user lacks create permission', function () {
    $limitedRole = Role::factory()->for($this->tenant)->withPermissions([
        'crm.customers.view', // only view, no create
    ])->create();
    $limitedUser = User::factory()->for($this->tenant)->create();
    $limitedUser->syncRoles([$limitedRole]);

    $this->actingAs($limitedUser)
        ->post('/backoffice/crm/customers', ['name' => 'Test'])
        ->assertStatus(403);
});

// 8. Cross-tenant — cannot edit other tenant's customer
it('returns 404 when editing another tenant customer', function () {
    $otherTenant   = Tenant::factory()->create();
    $otherCustomer = Customer::factory()->for($otherTenant)->create();

    $this->actingAs($this->user)
        ->put("/backoffice/crm/customers/{$otherCustomer->id}", ['name' => 'Hack'])
        ->assertStatus(404); // TenantScope makes it invisible → 404 from model binding
});
```

**Per-module checklist:**

- [ ] Unauthenticated redirect test
- [ ] Index shows only current tenant's records
- [ ] Create happy path + verifies `tenant_id` in DB
- [ ] Create validation failure test
- [ ] Update happy path
- [ ] Delete → soft-delete (not hard delete)
- [ ] 403 for role missing required permission
- [ ] 404 for cross-tenant access via URL manipulation

---

## SERVICE UNIT TESTS

### `tests/Unit/Services/DocumentNumberServiceTest.php`

- [ ] Returns formatted string: matches regex `/^[A-Z]+-\d{5}$/`
- [ ] Sequential: two calls return consecutive numbers
- [ ] Tenant-isolated: two tenants get their own sequences
- [ ] Concurrent safety: (optional) run 10 parallel calls → no duplicates

### `tests/Unit/Services/TaxCalculationServiceTest.php`

- [ ] Zero tax rate → tax_amount = 0
- [ ] 20% tax on 100.00 → tax_amount = 20.00, total = 120.00
- [ ] Multiple items → subtotal is sum of all lines
- [ ] Rounding: 100/3 * 20% = 6.666... → rounds to 6.67

### `tests/Unit/Services/InvoiceServiceTest.php`

- [ ] `create()` generates invoice with correct totals
- [ ] Client-submitted `total_amount` is ignored (server calculates it)
- [ ] Status transition: `draft` → `sent` allowed
- [ ] Status transition: `paid` → `draft` throws `DomainException`
- [ ] `recalculate()` updates totals from items

### `tests/Unit/Services/PaymentServiceTest.php`

- [ ] Allocation to invoice: `paid_amount` on invoice updated correctly
- [ ] Over-allocation: throws `DomainException` when `amount > outstanding`
- [ ] Exact match: invoice moves to `paid` status
- [ ] Partial payment: invoice moves to `partial` status

### `tests/Unit/Services/StockServiceTest.php`

- [ ] `adjust('in')` increases `quantity_on_hand`
- [ ] `adjust('out')` decreases `quantity_on_hand`
- [ ] `adjust('out')` throws `DomainException` when quantity > stock (tracked product)
- [ ] `adjust()` creates `StockMovement` record
- [ ] `transfer()` deducts from source and adds to destination atomically

---

## RUNNING TESTS

```bash
# Run full suite
php artisan test --parallel

# Run specific module
php artisan test --filter=Customer

# Run with coverage (requires Xdebug)
php artisan test --coverage --min=70

# Run only feature tests
php artisan test tests/Feature/

# Run only unit tests
php artisan test tests/Unit/
```

---

## TEST FACTORIES REQUIRED

Each domain needs a Model Factory. Priority:

```
database/factories/
├── UserFactory.php          (usually exists — extend for tenant_id)
├── TenantFactory.php        ← NEW
├── CRM/
│   └── CustomerFactory.php  ← NEW
├── Sales/
│   ├── InvoiceFactory.php   ← NEW
│   └── QuoteFactory.php     ← NEW
├── Catalog/
│   └── ProductFactory.php   ← NEW
├── Inventory/
│   └── WarehouseFactory.php ← NEW
├── Finance/
│   └── BankAccountFactory.php ← NEW
└── Purchases/
    └── SupplierFactory.php  ← NEW
```

---

## CI/CD INTEGRATION

Add to `.github/workflows/ci.yml`:

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_sqlite, mbstring

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy env
        run: cp .env.example .env && php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --database=sqlite

      - name: Run tests
        run: php artisan test --parallel

      - name: Static analysis
        run: ./vendor/bin/phpstan analyse --level=5

      - name: Code style
        run: ./vendor/bin/pint --test
```

- [ ] `ci.yml` created and passing on GitHub
- [ ] All tests green on every push
- [ ] Failed PR cannot be merged (branch protection rule)
