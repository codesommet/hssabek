# Phase 3 — Catalog (Products, Categories, Units, Taxes)

> **Depends on:** Phase 0, Phase 2 pattern established
> **Complexity:** M
> **Why now:** Products are required by Sales (invoice line items need products).

---

## 1. Objective

Build the product catalog: Products with image upload, Product Categories, Units of measure, Tax Groups with multi-rate support, and Tax Categories. Products form the foundation for all invoice line items.

---

## 2. Scope

**Route file:** `routes/backoffice/catalog.php` (currently empty — fill it)
**Controllers (all currently JSON stubs — rewrite):**
- `app/Http/Controllers/Backoffice/Catalog/ProductController.php`
- `app/Http/Controllers/Backoffice/Catalog/ProductCategoryController.php`
- `app/Http/Controllers/Backoffice/Catalog/UnitController.php`
- `app/Http/Controllers/Backoffice/Catalog/TaxGroupController.php`
- `app/Http/Controllers/Backoffice/Catalog/TaxCategoryController.php`

**Models (existing — respect schema):**
- `App\Models\Catalog\Product` — `tenant_id`, `product_category_id`, `unit_id`, `name`, `sku`, `description`, `purchase_price`, `selling_price`, `tax_group_id`, `product_image`, `track_inventory`
- `App\Models\Catalog\ProductCategory` — `tenant_id`, `name`, `description`, `parent_id`
- `App\Models\Catalog\Unit` — `tenant_id`, `name`, `abbreviation`
- `App\Models\Catalog\TaxGroup` — `tenant_id`, `name`, `description`
- `App\Models\Catalog\TaxCategory` — `tenant_id`, `tax_group_id`, `name`, `rate`, `is_compound`
- `App\Models\Inventory\ProductStock` — `tenant_id`, `product_id`, `warehouse_id`, `quantity_on_hand`, `reorder_level`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Product validation | `app/Http/Requests/Catalog/Store/StoreProductRequest.php` | FormRequest (audit) |
| Category/Unit/Tax validation | Existing FormRequests in `Catalog/` | FormRequest (audit) |
| Product authorization | `app/Policies/ProductPolicy.php` | NEW Policy |
| Image upload | Spatie MediaLibrary on Product model | Existing package |
| Initial stock creation | `ProductController::store()` inline | Controller (simple enough) |

**No Service class needed** — product creation is simple. `ProductStock` creation on
`track_inventory = true` is a single `create()` call in the controller.

---

## 4. Ordered Task Breakdown

### Task 3.1 — Add `BelongsToTenant` + `SoftDeletes` + `HasMedia` to Product Model

```php
// app/Models/Catalog/Product.php
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasUuids, SoftDeletes, BelongsToTenant, InteractsWithMedia;

    // Remove 'tenant_id' from $fillable (Phase 0)
    // Remove 'product_image' from $fillable (use MediaLibrary instead)
    protected $fillable = [
        'product_category_id', 'unit_id', 'name', 'sku', 'description',
        'purchase_price', 'selling_price', 'tax_group_id', 'track_inventory',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_image')
            ->singleFile()  // Only one product image at a time
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
```

### Task 3.2 — Fill `routes/backoffice/catalog.php`

```php
<?php

use App\Http\Controllers\Backoffice\Catalog\ProductController;
use App\Http\Controllers\Backoffice\Catalog\ProductCategoryController;
use App\Http\Controllers\Backoffice\Catalog\UnitController;
use App\Http\Controllers\Backoffice\Catalog\TaxGroupController;
use App\Http\Controllers\Backoffice\Catalog\TaxCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->as('catalog.')->group(function () {
    // Products — full CRUD
    Route::resource('products', ProductController::class);

    // Settings sub-resources (simpler CRUD, manage from a settings page)
    Route::resource('categories',     ProductCategoryController::class)->except(['show']);
    Route::resource('units',          UnitController::class)->except(['show']);
    Route::resource('tax-groups',     TaxGroupController::class)->except(['show']);
    Route::resource('tax-categories', TaxCategoryController::class)->except(['show']);
});
```

### Task 3.3 — Rewrite `ProductController`

```php
// app/Http/Controllers/Backoffice/Catalog/ProductController.php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('inventory.products.view'), 403);

    $products = Product::query()
        ->with(['category', 'unit', 'taxGroup'])
        ->when($request->search, fn($q, $s) =>
            $q->where('name', 'like', "%{$s}%")
              ->orWhere('sku', 'like', "%{$s}%"))
        ->when($request->category_id, fn($q, $c) => $q->where('product_category_id', $c))
        ->latest()
        ->paginate(15)
        ->withQueryString();

    $categories = ProductCategory::orderBy('name')->get();

    return view('backoffice.catalog.products.index', compact('products', 'categories'));
}

public function store(StoreProductRequest $request)
{
    abort_unless(auth()->user()->can('inventory.products.create'), 403);

    $product = Product::create($request->validated());

    // Handle image upload via Spatie MediaLibrary
    if ($request->hasFile('product_image')) {
        $product->addMediaFromRequest('product_image')
            ->toMediaCollection('product_image');
    }

    // Auto-create stock record if tracking enabled
    if ($product->track_inventory) {
        // Find default warehouse for this tenant
        $defaultWarehouse = \App\Models\Inventory\Warehouse::first();
        if ($defaultWarehouse) {
            \App\Models\Inventory\ProductStock::create([
                'product_id'       => $product->id,
                'warehouse_id'     => $defaultWarehouse->id,
                'quantity_on_hand' => 0,
                'reorder_level'    => 0,
            ]);
        }
    }

    return redirect()->route('bo.catalog.products.index')
        ->with('success', 'Produit créé avec succès.');
}
```

### Task 3.4 — Implement Simple List Controllers (Categories, Units, TaxGroups)

Categories, Units, and Tax Groups are managed from a tabbed settings page.
Each controller follows the same minimal pattern:

```php
// Example: ProductCategoryController
public function store(StoreProductCategoryRequest $request)
{
    abort_unless(auth()->user()->can('inventory.products.create'), 403);
    ProductCategory::create($request->validated());
    return redirect()->back()->with('success', 'Catégorie ajoutée.');
}

public function update(UpdateProductCategoryRequest $request, ProductCategory $category)
{
    abort_unless(auth()->user()->can('inventory.products.edit'), 403);
    $category->update($request->validated());
    return redirect()->back()->with('success', 'Catégorie mise à jour.');
}

public function destroy(ProductCategory $category)
{
    abort_unless(auth()->user()->can('inventory.products.delete'), 403);
    // Check if category has products before deleting
    abort_if($category->products()->exists(), 422,
        'Impossible de supprimer une catégorie contenant des produits.');
    $category->delete();
    return redirect()->back()->with('success', 'Catégorie supprimée.');
}
```

### Task 3.5 — Create `ProductPolicy`

```php
// app/Policies/ProductPolicy.php
public function viewAny(User $user): bool { return $user->can('inventory.products.view'); }
public function create(User $user): bool   { return $user->can('inventory.products.create'); }
public function update(User $user, Product $product): bool
{
    return $user->can('inventory.products.edit')
        && $product->tenant_id === $user->tenant_id;
}
public function delete(User $user, Product $product): bool
{
    return $user->can('inventory.products.delete')
        && $product->tenant_id === $user->tenant_id;
}
```

### Task 3.6 — Audit FormRequests

Check `StoreProductRequest` and `UpdateProductRequest`:
- Remove `tenant_id` from rules
- Remove `product_image` from rules (handled by MediaLibrary, not validated as a string)
- Add image validation:
```php
'product_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
```
- All messages in French:
```php
public function messages(): array
{
    return [
        'name.required'            => 'Le nom du produit est obligatoire.',
        'selling_price.required'   => 'Le prix de vente est obligatoire.',
        'selling_price.numeric'    => 'Le prix de vente doit être un nombre.',
        'product_image.image'      => 'Le fichier doit être une image.',
        'product_image.max'        => 'L\'image ne doit pas dépasser 2 Mo.',
        'sku.unique'               => 'Ce code SKU est déjà utilisé.',
    ];
}
```

For `StoreTaxGroupRequest` — include validation for nested tax categories:
```php
'rates'             => ['sometimes', 'array'],
'rates.*.name'      => ['required_with:rates', 'string'],
'rates.*.rate'      => ['required_with:rates', 'numeric', 'min:0', 'max:100'],
'rates.*.is_compound' => ['sometimes', 'boolean'],
```

### Task 3.7 — Create Blade Views

**`resources/views/backoffice/catalog/products/index.blade.php`**
Reference: `resources/views/products.blade.php`

**`resources/views/backoffice/catalog/products/create.blade.php`**
Reference: `resources/views/add-product.blade.php`

**`resources/views/backoffice/catalog/products/edit.blade.php`**
Reference: `resources/views/edit-product.blade.php`

**`resources/views/backoffice/catalog/settings.blade.php`**
A single tabbed page for Categories, Units, Tax Groups:
Reference: `resources/views/company-settings.blade.php` (tabbed pattern)

Product image display in index:
```blade
@if($product->getFirstMedia('product_image'))
    <img src="{{ $product->getFirstMediaUrl('product_image') }}"
         class="rounded" width="40" height="40" alt="{{ $product->name }}">
@else
    <div class="bg-light rounded d-flex align-items-center justify-content-center"
         style="width:40px;height:40px;">
        <i class="ti ti-package text-muted"></i>
    </div>
@endif
```

Track inventory badge:
```blade
<span class="badge {{ $product->track_inventory ? 'bg-info-light text-info' : 'bg-secondary-light text-secondary' }}">
    {{ $product->track_inventory ? 'Suivi stock' : 'Hors stock' }}
</span>
```

### Task 3.8 — Update Sidebar

```blade
<li class="nav-item has-sub {{ request()->routeIs('bo.catalog.*') ? 'open' : '' }}">
    <a href="#" class="nav-link">
        <i class="ti ti-package"></i>
        <span>Catalogue</span>
    </a>
    <ul class="submenu">
        <li><a href="{{ route('bo.catalog.products.index') }}" class="nav-link">Produits</a></li>
        <li><a href="{{ route('bo.catalog.categories.index') }}" class="nav-link">Catégories</a></li>
        <li><a href="{{ route('bo.catalog.tax-groups.index') }}" class="nav-link">Groupes TVA</a></li>
        <li><a href="{{ route('bo.catalog.units.index') }}" class="nav-link">Unités</a></li>
    </ul>
</li>
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/catalog.php` | Filled |
| `app/Http/Controllers/Backoffice/Catalog/ProductController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Catalog/ProductCategoryController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Catalog/UnitController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Catalog/TaxGroupController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Catalog/TaxCategoryController.php` | Rewritten |
| `app/Models/Catalog/Product.php` | Updated (HasMedia, SoftDeletes) |
| `app/Policies/ProductPolicy.php` | New |
| `resources/views/backoffice/catalog/products/{index,create,edit}.blade.php` | New |
| `resources/views/backoffice/catalog/settings.blade.php` | New |

---

## 6. Acceptance Criteria

- [ ] Product list loads with search + category filter
- [ ] Product image uploads and displays in list and edit form
- [ ] Creating product with `track_inventory = true` → `ProductStock` record created
- [ ] Tax group with multiple rates sums correctly
- [ ] Category delete blocked if products reference it
- [ ] SKU unique per tenant (not globally)
- [ ] Permissions `inventory.products.*` enforced on each action
- [ ] Cross-tenant: Tenant A's products invisible to Tenant B

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Catalog/ProductCrudTest.php` | Feature | CRUD + image upload + stock creation |
| `tests/Feature/Catalog/ProductAuthorizationTest.php` | Feature | 403 for missing permissions |
| `tests/Feature/Catalog/TaxGroupTest.php` | Feature | Tax group with multiple rates |

---

## 8. Multi-Tenant Pitfalls

- ❌ NEVER: `ProductCategory::all()` without tenant scope
- ✅ DO: Validate `product_category_id` exists within current tenant:
```php
Rule::exists('product_categories', 'id')->where('tenant_id', TenantContext::id())
```
- ✅ DO: Validate `tax_group_id` same way
- ✅ DO: Validate `unit_id` same way
- ✅ DO: When querying categories for dropdown, always scope to current tenant

---

## 9. Mass-Assignment Safeguards

- `product_image` must NOT be in `$fillable` (MediaLibrary handles it separately)
- `tenant_id` must NOT be in `$fillable`
- `purchase_price` and `selling_price` are trusted server data — OK to accept from form
- Do NOT accept `quantity_on_hand` in `StoreProductRequest` — initial stock is always 0

---

## 10. Schema Notes

**`products` columns:** `tenant_id`, `product_category_id`, `unit_id`, `name`, `sku`, `description`, `purchase_price` (decimal), `selling_price` (decimal), `tax_group_id`, `product_image` (string — legacy, use MediaLibrary instead), `track_inventory` (boolean)

**`tax_categories` columns:** `tenant_id`, `tax_group_id`, `name`, `rate` (decimal), `is_compound` (boolean)

Note: `product_image` column exists in schema as a string URL. With MediaLibrary, leave the column but populate it from MediaLibrary's URL on save (or ignore it and always use `getFirstMediaUrl()`). Do NOT remove the column.

---

## 11. UI Instructions

- **Product list reference:** `resources/views/products.blade.php`
- **Product create reference:** `resources/views/add-product.blade.php`
- **Product edit reference:** `resources/views/edit-product.blade.php`
- **Settings/categories reference:** `resources/views/company-settings.blade.php` (tab pattern)
- Image upload must use `enctype="multipart/form-data"` on the form
- Selling price display: format as `{{ number_format($product->selling_price, 2) }} {{ config('app.currency') }}`
- `track_inventory` toggle: render as a checkbox or toggle switch matching the theme
