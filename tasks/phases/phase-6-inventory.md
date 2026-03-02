# Phase 6 — Inventory (Warehouses, Stock, Movements, Transfers)

> **Depends on:** Phase 0, Phase 3 (Catalog), Phase 4 (Sales — stock decrement), Phase 5 (Purchases — stock increment)
> **Complexity:** M

---

## 1. Objective

Manage stock levels per warehouse. Track every stock movement with a full audit log.
Enforce over-selling prevention when `track_inventory = true`.

---

## 2. Scope

**Route file:** `routes/backoffice/inventory.php` (currently empty)
**Controllers (rewrite):**
- `app/Http/Controllers/Backoffice/Inventory/WarehouseController.php`
- `app/Http/Controllers/Backoffice/Inventory/ProductStockController.php`
- `app/Http/Controllers/Backoffice/Inventory/StockMovementController.php`
- `app/Http/Controllers/Backoffice/Inventory/StockTransferController.php`

**Models (existing — do not modify):**
- `Warehouse` — `tenant_id`, `name`, `code`, `address`, `is_default`, `status`
- `ProductStock` — `tenant_id`, `product_id`, `warehouse_id`, `quantity_on_hand`, `reorder_level`
- `StockMovement` — `tenant_id`, `product_id`, `warehouse_id`, `type` (in/out/adjustment), `quantity`, `reference_type`, `reference_id`, `notes`, `movement_date`
- `StockTransfer` — `tenant_id`, `from_warehouse_id`, `to_warehouse_id`, `transfer_date`, `status`, `notes`
- `StockTransferItem` — `stock_transfer_id`, `product_id`, `quantity_requested`, `quantity_transferred`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| All stock mutations | `app/Services/Inventory/StockService.php` | NEW Service (CRITICAL) |
| Authorization | `app/Policies/WarehousePolicy.php`, `StockPolicy.php` | NEW Policies |

**`StockService` is the single point of truth for all stock changes.**
Never call `ProductStock::update()` directly from a controller.

---

## 4. Ordered Task Breakdown

### Task 6.1 — Implement `StockService` (Critical)

```php
// app/Services/Inventory/StockService.php
<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Catalog\Product;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Adjust stock for a product in a warehouse.
     * type: 'in' | 'out' | 'adjustment'
     * Creates a StockMovement record for every change.
     */
    public function adjust(
        string $productId,
        float  $quantity,
        string $type,
        string $notes = '',
        ?string $warehouseId = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): ProductStock {
        return DB::transaction(function () use ($productId, $quantity, $type, $notes, $warehouseId, $referenceType, $referenceId) {

            $warehouse = $warehouseId
                ? \App\Models\Inventory\Warehouse::findOrFail($warehouseId)
                : \App\Models\Inventory\Warehouse::where('is_default', true)->firstOrFail();

            $stock = ProductStock::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouse->id],
                ['quantity_on_hand' => 0, 'reorder_level' => 0]
            );

            $product = Product::findOrFail($productId);

            if ($type === 'out' && $product->track_inventory) {
                if ($stock->quantity_on_hand < $quantity) {
                    throw new \DomainException(
                        "Stock insuffisant pour \"{$product->name}\". "
                        . "Disponible: {$stock->quantity_on_hand}, Demandé: {$quantity}"
                    );
                }
            }

            $delta = match($type) {
                'in'         => $quantity,
                'out'        => -$quantity,
                'adjustment' => $quantity,  // can be positive or negative
                default      => throw new \InvalidArgumentException("Type de mouvement invalide: {$type}"),
            };

            $stock->increment('quantity_on_hand', $delta);

            StockMovement::create([
                'product_id'     => $productId,
                'warehouse_id'   => $warehouse->id,
                'type'           => $type,
                'quantity'       => abs($quantity),
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'movement_date'  => now(),
            ]);

            return $stock->fresh();
        });
    }

    /**
     * Execute a stock transfer between two warehouses.
     * Deducts from source, adds to destination, atomically.
     */
    public function transfer(StockTransfer $transfer, array $items): void
    {
        DB::transaction(function () use ($transfer, $items) {
            foreach ($items as $item) {
                // Deduct from source
                $this->adjust(
                    $item['product_id'],
                    $item['quantity_transferred'],
                    'out',
                    "Transfert #{$transfer->id}",
                    $transfer->from_warehouse_id,
                    StockTransfer::class,
                    $transfer->id,
                );

                // Add to destination
                $this->adjust(
                    $item['product_id'],
                    $item['quantity_transferred'],
                    'in',
                    "Transfert #{$transfer->id}",
                    $transfer->to_warehouse_id,
                    StockTransfer::class,
                    $transfer->id,
                );

                StockTransferItem::where('stock_transfer_id', $transfer->id)
                    ->where('product_id', $item['product_id'])
                    ->update(['quantity_transferred' => $item['quantity_transferred']]);
            }

            $transfer->update(['status' => 'completed']);
        });
    }
}
```

### Task 6.2 — Fill `routes/backoffice/inventory.php`

```php
<?php

use App\Http\Controllers\Backoffice\Inventory\{WarehouseController, ProductStockController, StockMovementController, StockTransferController};
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->as('inventory.')->group(function () {
    Route::resource('warehouses',       WarehouseController::class);
    Route::get('stock',                 [ProductStockController::class, 'index'])->name('stock.index');
    Route::resource('movements',        StockMovementController::class)->only(['index', 'create', 'store']);
    Route::resource('transfers',        StockTransferController::class);
    Route::post('transfers/{transfer}/execute',
        [StockTransferController::class, 'execute'])->name('transfers.execute');
});
```

### Task 6.3 — Implement Controllers

**`WarehouseController`** — standard CRUD, no business logic:
```php
public function store(StoreWarehouseRequest $request)
{
    abort_unless(auth()->user()->can('inventory.warehouses.create'), 403);
    Warehouse::create($request->validated());
    return redirect()->route('bo.inventory.warehouses.index')
        ->with('success', 'Entrepôt créé.');
}
```

**`ProductStockController@index`** — read-only stock level report:
```php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('inventory.stock_movements.view'), 403);

    $stocks = ProductStock::query()
        ->with(['product', 'warehouse'])
        ->when($request->warehouse_id, fn($q, $w) => $q->where('warehouse_id', $w))
        ->when($request->low_stock, fn($q) =>
            $q->whereRaw('quantity_on_hand <= reorder_level'))
        ->join('products', 'product_stocks.product_id', 'products.id')
        ->orderBy('products.name')
        ->paginate(25)
        ->withQueryString();

    $warehouses = Warehouse::orderBy('name')->get();

    return view('backoffice.inventory.stock.index', compact('stocks', 'warehouses'));
}
```

**`StockMovementController@store`** — manual adjustment:
```php
public function store(StoreStockMovementRequest $request)
{
    abort_unless(auth()->user()->can('inventory.stock_movements.create'), 403);

    app(StockService::class)->adjust(
        productId:  $request->product_id,
        quantity:   abs((float)$request->quantity),
        type:       $request->type,
        notes:      $request->notes ?? '',
        warehouseId: $request->warehouse_id,
    );

    return redirect()->route('bo.inventory.movements.index')
        ->with('success', 'Mouvement de stock enregistré.');
}
```

**`StockTransferController@execute`**:
```php
public function execute(StockTransfer $transfer)
{
    abort_unless(auth()->user()->can('inventory.stock_movements.create'), 403);
    abort_unless($transfer->status === 'pending', 422, 'Ce transfert a déjà été exécuté.');

    $items = $transfer->items->map(fn($i) => [
        'product_id'           => $i->product_id,
        'quantity_transferred' => $i->quantity_requested, // transfer full quantity
    ])->toArray();

    app(StockService::class)->transfer($transfer, $items);

    return redirect()->route('bo.inventory.transfers.show', $transfer)
        ->with('success', 'Transfert exécuté avec succès.');
}
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/inventory.php` | Filled |
| `app/Services/Inventory/StockService.php` | New |
| `app/Http/Controllers/Backoffice/Inventory/WarehouseController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Inventory/ProductStockController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Inventory/StockMovementController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Inventory/StockTransferController.php` | Rewritten |
| `app/Policies/WarehousePolicy.php` | New |
| All Inventory Blade views | New |

---

## 6. Acceptance Criteria

- [ ] Creating invoice for tracked product → `StockService::adjust('out')` called
- [ ] Receiving goods from PO → `StockService::adjust('in')` called
- [ ] Over-sell attempt → exception thrown → 422 response
- [ ] Transfer: stock deducted from source AND added to destination in same transaction
- [ ] Every stock change creates a `StockMovement` record (full audit trail)
- [ ] Low-stock filter works in ProductStock index

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Inventory/WarehouseCrudTest.php` | Feature | Warehouse CRUD |
| `tests/Feature/Inventory/StockAdjustmentTest.php` | Feature | Manual adjustment + movement log |
| `tests/Feature/Inventory/StockTransferTest.php` | Feature | Transfer atomicity |
| `tests/Unit/Services/StockServiceTest.php` | Unit | Over-sell prevention, transfer math |

---

## 8. Multi-Tenant Pitfalls

- `StockService::adjust()` — product and warehouse must both belong to same tenant
- Validate `warehouse_id` in FormRequest with `.where('tenant_id', TenantContext::id())`
- `StockMovement` has `tenant_id` — must be filled by `BelongsToTenant`
- Never allow `quantity_on_hand` to be updated directly — only through `StockService`

---

## 9. Schema Notes

**`product_stocks` columns:** `tenant_id`, `product_id`, `warehouse_id`, `quantity_on_hand` (decimal), `reorder_level` (decimal)

**`stock_movements` columns:** `tenant_id`, `product_id`, `warehouse_id`, `type` (in/out/adjustment), `quantity` (decimal), `reference_type` (nullable string), `reference_id` (nullable uuid), `notes`, `movement_date`

Do NOT add columns — use existing schema exactly as defined.

---

## 10. UI Instructions

- **Warehouse list:** use `customers.blade.php` structure (same table pattern)
- **Stock levels:** use `inventory.blade.php` reference
- **Movements:** read-only list with filter by product, warehouse, type, date range
- **Transfers:** show source/destination warehouses prominently; "Execute" button only visible for `pending` status
