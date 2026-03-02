# Phase 2 — CRM (Customers, Addresses, Contacts)

> **Depends on:** Phase 0 complete
> **Complexity:** M
> **Why now:** Customers are required by Sales (invoices need a customer). CRM is the simplest domain and establishes the master pattern for all future modules.

---

## 1. Objective

Deliver full CRUD for Customers, Customer Addresses, and Customer Contacts.
This phase establishes the reusable pattern (Controller → Policy → FormRequest → Blade)
that every subsequent module copies verbatim.

---

## 2. Scope

**Route file:** `routes/backoffice/crm.php` (currently empty — fill it)
**Controllers:**
- `app/Http/Controllers/Backoffice/CRM/CustomerController.php` (rewrite — currently JSON stubs)
- `app/Http/Controllers/Backoffice/CRM/CustomerAddressController.php` (rewrite)
- `app/Http/Controllers/Backoffice/CRM/CustomerContactController.php` (rewrite)

**Models (existing — respect schema exactly):**
- `App\Models\CRM\Customer` — `tenant_id`, `name`, `email`, `phone`, `customer_type`, `tax_id`, `currency_id`, `credit_limit`, `credit_used`, `payment_terms`, `status`, `notes`
- `App\Models\CRM\CustomerAddress` — `customer_id`, `tenant_id`, `address_type`, `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`
- `App\Models\CRM\CustomerContact` — `customer_id`, `tenant_id`, `name`, `email`, `phone`, `position`, `is_primary`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Create customer validation | `app/Http/Requests/CRM/Store/StoreCustomerRequest.php` | FormRequest (already exists — audit messages) |
| Update customer validation | `app/Http/Requests/CRM/Update/UpdateCustomerRequest.php` | FormRequest (already exists — audit) |
| Create address validation | `app/Http/Requests/CRM/Store/StoreCustomerAddressRequest.php` | FormRequest (already exists) |
| Create contact validation | `app/Http/Requests/CRM/Store/StoreCustomerContactRequest.php` | FormRequest (already exists) |
| Customer authorization | `app/Policies/CustomerPolicy.php` | NEW Policy |
| Complex queries (optional) | None needed — controller handles directly | — |

**No Service class needed** — Customer CRUD is straightforward enough for the controller.
Addresses and contacts are nested resources with no business logic beyond CRUD.

---

## 4. Ordered Task Breakdown

### Task 2.1 — Audit Existing FormRequests

Open each existing CRM FormRequest and verify:
1. All `messages()` are in French
2. `authorize()` returns `true` (authorization is in controller via `abort_unless`)
3. `tenant_id` is NOT in the `rules()` array
4. Unique rules are scoped to the current tenant, e.g.:
```php
'email' => [
    'nullable', 'email',
    Rule::unique('customers', 'email')
        ->where('tenant_id', TenantContext::id())
        ->ignore($this->route('customer')?->id),
],
```

### Task 2.2 — Fill `routes/backoffice/crm.php`

```php
<?php

use App\Http\Controllers\Backoffice\CRM\CustomerController;
use App\Http\Controllers\Backoffice\CRM\CustomerAddressController;
use App\Http\Controllers\Backoffice\CRM\CustomerContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->as('crm.')->group(function () {
    Route::resource('customers', CustomerController::class);

    // Addresses and contacts as shallow nested resources
    // Shallow: GET /customers/{customer}/addresses/create
    //          POST /customers/{customer}/addresses
    //          GET|PUT|DELETE /addresses/{address}  (not nested)
    Route::resource('customers.addresses', CustomerAddressController::class)->shallow();
    Route::resource('customers.contacts',  CustomerContactController::class)->shallow();
});
```

### Task 2.3 — Rewrite `CustomerController`

Replace all `response()->json()` methods:

```php
// app/Http/Controllers/Backoffice/CRM/CustomerController.php
<?php

namespace App\Http\Controllers\Backoffice\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\Store\StoreCustomerRequest;
use App\Http\Requests\CRM\Update\UpdateCustomerRequest;
use App\Models\CRM\Customer;
use App\Models\Finance\Currency;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('crm.customers.view'), 403);

        $customers = Customer::query()
            ->with(['currency'])
            ->withCount(['invoices', 'quotes'])
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
            )
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->type, fn($q, $t) => $q->where('customer_type', $t))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('backoffice.crm.customers.index', compact('customers'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('crm.customers.create'), 403);
        $currencies = Currency::orderBy('code')->get();
        return view('backoffice.crm.customers.create', compact('currencies'));
    }

    public function store(StoreCustomerRequest $request)
    {
        abort_unless(auth()->user()->can('crm.customers.create'), 403);
        Customer::create($request->validated());
        return redirect()->route('bo.crm.customers.index')
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Customer $customer)
    {
        abort_unless(auth()->user()->can('crm.customers.view'), 403);
        $customer->load([
            'addresses',
            'contacts',
            'invoices' => fn($q) => $q->latest()->take(10),
            'quotes'   => fn($q) => $q->latest()->take(5),
            'currency',
        ]);
        return view('backoffice.crm.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->can('crm.customers.edit'), 403);
        $currencies = Currency::orderBy('code')->get();
        return view('backoffice.crm.customers.edit', compact('customer', 'currencies'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        abort_unless(auth()->user()->can('crm.customers.edit'), 403);
        $customer->update($request->validated());
        return redirect()->route('bo.crm.customers.index')
            ->with('success', 'Client mis à jour avec succès.');
    }

    public function destroy(Customer $customer)
    {
        abort_unless(auth()->user()->can('crm.customers.delete'), 403);
        $customer->delete(); // SoftDelete (added in Phase 0)
        return redirect()->route('bo.crm.customers.index')
            ->with('success', 'Client supprimé avec succès.');
    }
}
```

### Task 2.4 — Rewrite `CustomerAddressController`

Addresses are managed inline on the customer `show` page via a modal or dedicated sub-form.
Use the shallow resource pattern:

```php
// app/Http/Controllers/Backoffice/CRM/CustomerAddressController.php
public function store(StoreCustomerAddressRequest $request, Customer $customer)
{
    abort_unless(auth()->user()->can('crm.customers.edit'), 403);
    $customer->addresses()->create($request->validated());
    return redirect()->route('bo.crm.customers.show', $customer)
        ->with('success', 'Adresse ajoutée.');
}

public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address)
{
    abort_unless(auth()->user()->can('crm.customers.edit'), 403);
    $address->update($request->validated());
    return redirect()->route('bo.crm.customers.show', $address->customer_id)
        ->with('success', 'Adresse mise à jour.');
}

public function destroy(CustomerAddress $address)
{
    abort_unless(auth()->user()->can('crm.customers.delete'), 403);
    $address->delete();
    return redirect()->route('bo.crm.customers.show', $address->customer_id)
        ->with('success', 'Adresse supprimée.');
}
```

Use same pattern for `CustomerContactController`.

### Task 2.5 — Create `CustomerPolicy`

```php
// app/Policies/CustomerPolicy.php
<?php

namespace App\Policies;

use App\Models\CRM\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool { return $user->can('crm.customers.view'); }
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('crm.customers.view')
            && $customer->tenant_id === $user->tenant_id;
    }
    public function create(User $user): bool { return $user->can('crm.customers.create'); }
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('crm.customers.edit')
            && $customer->tenant_id === $user->tenant_id;
    }
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('crm.customers.delete')
            && $customer->tenant_id === $user->tenant_id;
    }
}
```

Register in `AppServiceProvider`:
```php
Gate::policy(Customer::class, CustomerPolicy::class);
```

### Task 2.6 — Create Blade Views

**`resources/views/backoffice/crm/customers/index.blade.php`**
Reference: `resources/views/customers.blade.php`

Key dynamic sections:
```blade
{{-- Search bar (copy structure from reference) --}}
<input type="text" name="search" class="form-control"
    placeholder="Rechercher un client..." value="{{ request('search') }}">

{{-- Status filter --}}
<select name="status" class="form-select" onchange="this.form.submit()">
    <option value="">Tous les statuts</option>
    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actif</option>
    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
</select>

{{-- Table body --}}
@forelse($customers as $customer)
<tr>
    <td>{{ $customer->name }}</td>
    <td>{{ $customer->email ?? '—' }}</td>
    <td>{{ $customer->phone ?? '—' }}</td>
    <td>
        <span class="badge {{ $customer->status === 'active' ? 'bg-success-light text-success' : 'bg-danger-light text-danger' }}">
            {{ $customer->status === 'active' ? 'Actif' : 'Inactif' }}
        </span>
    </td>
    <td>{{ $customer->invoices_count }}</td>
    <td>
        <div class="dropdown">
            <button class="btn btn-sm dark-transparent dropdown-toggle" data-bs-toggle="dropdown">
                Actions
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('bo.crm.customers.show', $customer) }}">
                    <i class="ti ti-eye me-1"></i> Voir
                </a></li>
                @can('crm.customers.edit')
                <li><a class="dropdown-item" href="{{ route('bo.crm.customers.edit', $customer) }}">
                    <i class="ti ti-edit me-1"></i> Modifier
                </a></li>
                @endcan
                @can('crm.customers.delete')
                <li>
                    <form method="POST" action="{{ route('bo.crm.customers.destroy', $customer) }}">
                        @csrf @method('DELETE')
                        <button class="dropdown-item text-danger" type="submit"
                            onclick="return confirm('Supprimer ce client ?')">
                            <i class="ti ti-trash me-1"></i> Supprimer
                        </button>
                    </form>
                </li>
                @endcan
            </ul>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="text-center py-4">
        <i class="ti ti-users-off fs-2 text-muted"></i>
        <p class="text-muted mt-2">Aucun client trouvé.</p>
    </td>
</tr>
@endforelse

{{-- Pagination --}}
{{ $customers->links() }}
```

**`resources/views/backoffice/crm/customers/create.blade.php`**
Reference: `resources/views/add-customer.blade.php`

**`resources/views/backoffice/crm/customers/edit.blade.php`**
Reference: `resources/views/edit-customer.blade.php`

**`resources/views/backoffice/crm/customers/show.blade.php`**
Reference: `resources/views/customer-details.blade.php`
Include address and contact sub-sections from the detail reference.

### Task 2.7 — Update Sidebar

Activate CRM nav item in `sidebar.blade.php`:
```blade
<li class="nav-item has-sub {{ request()->routeIs('bo.crm.*') ? 'open' : '' }}">
    <a href="#" class="nav-link">
        <i class="ti ti-users"></i>
        <span>CRM</span>
    </a>
    <ul class="submenu">
        <li>
            <a href="{{ route('bo.crm.customers.index') }}"
               class="nav-link {{ request()->routeIs('bo.crm.customers.*') ? 'active' : '' }}">
                Clients
            </a>
        </li>
    </ul>
</li>
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/crm.php` | Filled |
| `app/Http/Controllers/Backoffice/CRM/CustomerController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/CRM/CustomerAddressController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/CRM/CustomerContactController.php` | Rewritten |
| `app/Http/Requests/CRM/Store/StoreCustomerRequest.php` | Audited (French messages) |
| `app/Http/Requests/CRM/Update/UpdateCustomerRequest.php` | Audited |
| `app/Http/Requests/CRM/Store/StoreCustomerAddressRequest.php` | Audited |
| `app/Http/Requests/CRM/Store/StoreCustomerContactRequest.php` | Audited |
| `app/Policies/CustomerPolicy.php` | New |
| `resources/views/backoffice/crm/customers/index.blade.php` | New |
| `resources/views/backoffice/crm/customers/create.blade.php` | New |
| `resources/views/backoffice/crm/customers/edit.blade.php` | New |
| `resources/views/backoffice/crm/customers/show.blade.php` | New |

---

## 6. Acceptance Criteria

- [ ] GET `/backoffice/crm/customers` returns paginated Blade table (not JSON)
- [ ] Search by name filters correctly and `?search=` persists in pagination links
- [ ] Filter by status works correctly
- [ ] Create customer → flash "Client créé avec succès." → redirect to index
- [ ] Edit customer → flash "Client mis à jour avec succès." → redirect to index
- [ ] Delete customer → soft-delete (record has `deleted_at`, still in DB) → redirect to index
- [ ] Customer from Tenant B is invisible to Tenant A (TenantScope applied)
- [ ] Role without `crm.customers.create` → 403 on POST
- [ ] Role without `crm.customers.delete` → 403 on DELETE
- [ ] Addresses and contacts can be added/edited/deleted from customer show page
- [ ] `withCount(['invoices'])` shows correct invoice count per customer

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/CRM/CustomerCrudTest.php` | Feature | Full CRUD happy path + validation |
| `tests/Feature/CRM/CustomerAuthorizationTest.php` | Feature | 403 for missing permissions |
| `tests/Feature/CRM/CustomerTenantIsolationTest.php` | Feature | Cross-tenant isolation |

---

## 8. Multi-Tenant Pitfalls

- ❌ NEVER: `Customer::find($id)` — route model binding with TenantScope is sufficient, but be aware
- ❌ NEVER: Pass `customer_id` from another tenant through form data without validating ownership
- ✅ DO: In `StoreCustomerAddressRequest`, validate `customer_id` belongs to current tenant:
```php
'customer_id' => ['required', Rule::exists('customers', 'id')
    ->where('tenant_id', TenantContext::id())],
```
- ✅ DO: In `CustomerAddressController`, always navigate back to the customer that owns the address

---

## 9. Mass-Assignment Safeguards

- `Customer::$fillable` must NOT contain `tenant_id` (removed in Phase 0)
- `CustomerAddress::$fillable` must NOT contain `tenant_id` — `BelongsToTenant` fills it
- Do NOT include `credit_used` in `StoreCustomerRequest` — this is calculated by the system, not submitted by users
- Do NOT include `credit_limit` unless tenant has that permission (future: feature flag)

---

## 10. Schema Notes

**`customers` table columns (existing — do not change):**
`tenant_id`, `name`, `email`, `phone`, `customer_type` (individual/company), `tax_id`, `currency_id`, `credit_limit`, `credit_used`, `payment_terms`, `status` (active/inactive), `notes`

**`customer_addresses` columns:**
`customer_id`, `tenant_id`, `address_type` (billing/shipping/other), `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country`

**`customer_contacts` columns:**
`customer_id`, `tenant_id`, `name`, `email`, `phone`, `position`, `is_primary` (boolean)

---

## 11. UI Instructions

- Base layout: `resources/views/backoffice/layout/mainlayout.blade.php`
- **Index reference:** `resources/views/customers.blade.php` — copy EXACT HTML structure
- **Create reference:** `resources/views/add-customer.blade.php`
- **Edit reference:** `resources/views/edit-customer.blade.php`
- **Show reference:** `resources/views/customer-details.blade.php`
- All user-facing strings in French (no exceptions)
- Use `@can` Blade directive to show/hide action buttons based on permissions
- Validation: `class="form-control @error('name') is-invalid @enderror"`
- Empty state: use the `@empty` block with a centered icon + text
- Pagination: `{{ $customers->links() }}` (uses Laravel's default pagination)
