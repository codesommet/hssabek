# Phase 5 — Purchases (Suppliers, Purchase Orders, Vendor Bills)

> **Depends on:** Phase 0, Phase 3 (Catalog products needed for PO line items)
> **Complexity:** L
> **Pattern:** Mirrors Phase 4 (Sales) on the procurement side.

---

## 1. Objective

Build the purchase-to-pay pipeline:
1. **Supplier** — CRUD (mirrors Customer)
2. **Purchase Order (PO)** — create with line items, mark as received (creates GoodsReceipt)
3. **Vendor Bill** — created from received PO, payment allocation to supplier

---

## 2. Scope

**Route file:** `routes/backoffice/purchases.php` (currently empty)
**Controllers (rewrite):**
- `app/Http/Controllers/Backoffice/Purchases/SupplierController.php`
- `app/Http/Controllers/Backoffice/Purchases/PurchaseOrderController.php`
- `app/Http/Controllers/Backoffice/Purchases/VendorBillController.php`

**Models (existing — do not modify schema):**
- `Supplier` — `tenant_id`, `name`, `email`, `phone`, `tax_id`, `currency_id`, `payment_terms`, `status`, `notes`
- `PurchaseOrder` — `tenant_id`, `supplier_id`, `po_number`, `order_date`, `expected_delivery`, `subtotal`, `tax_amount`, `total_amount`, `status`, `notes`
- `PurchaseOrderItem` — `purchase_order_id`, `tenant_id`, `product_id`, `description`, `quantity`, `unit_price`, `tax_category_id`, `tax_amount`, `subtotal`, `total`
- `GoodsReceipt` — `tenant_id`, `purchase_order_id`, `receipt_date`, `notes`, `status`
- `GoodsReceiptItem` — `goods_receipt_id`, `purchase_order_item_id`, `product_id`, `quantity_received`
- `VendorBill` — `tenant_id`, `supplier_id`, `purchase_order_id`, `bill_number`, `bill_date`, `due_date`, `subtotal`, `tax_amount`, `total_amount`, `status`
- `SupplierPayment` — `tenant_id`, `supplier_id`, `amount`, `payment_date`, `payment_method_id`, `bank_account_id`, `reference`
- `SupplierPaymentAllocation` — `supplier_payment_id`, `vendor_bill_id`, `amount`
- `DebitNote` / `DebitNoteItem` / `DebitNoteApplication` — vendor-side credit notes

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| PO totals calculation | Reuse `TaxCalculationService` from Phase 4 | Existing Service |
| PO orchestration | `app/Services/Purchases/PurchaseOrderService.php` | NEW Service |
| Supplier payment allocation | `app/Services/Purchases/SupplierPaymentService.php` | NEW Service |
| Authorization | `app/Policies/SupplierPolicy.php`, `PurchaseOrderPolicy.php`, `VendorBillPolicy.php` | NEW Policies |
| PO number sequence | Reuse `DocumentNumberService` with type `'purchase_order'` | Existing Service |
| Bill number sequence | Reuse `DocumentNumberService` with type `'vendor_bill'` | Existing Service |

---

## 4. Ordered Task Breakdown

### Task 5.1 — Fill `routes/backoffice/purchases.php`

```php
<?php

use App\Http\Controllers\Backoffice\Purchases\{SupplierController, PurchaseOrderController, VendorBillController};
use Illuminate\Support\Facades\Route;

Route::prefix('purchases')->as('purchases.')->group(function () {
    // Suppliers
    Route::resource('suppliers', SupplierController::class);

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/receive',
        [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/cancel',
        [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    // Vendor Bills
    Route::resource('vendor-bills', VendorBillController::class);
    Route::post('vendor-bills/{vendorBill}/pay',
        [VendorBillController::class, 'pay'])->name('vendor-bills.pay');
});
```

### Task 5.2 — Implement `SupplierController`

Exact same pattern as `CustomerController`. Mirror it:
- `index()` — paginated list with search, `withCount(['purchaseOrders'])`
- `create()` / `store()` — use `StoreSupplierRequest`
- `show()` — load `purchaseOrders`, `vendorBills`
- `edit()` / `update()` — use `UpdateSupplierRequest`
- `destroy()` — SoftDelete

Permission checks use `purchases.suppliers.*` permissions.

### Task 5.3 — Implement `PurchaseOrderService`

```php
// app/Services/Purchases/PurchaseOrderService.php
public function create(array $validated): PurchaseOrder
{
    return DB::transaction(function () use ($validated) {
        $totals = $this->taxService->calculateItems($validated['items'] ?? []);

        $po = PurchaseOrder::create([
            'supplier_id'       => $validated['supplier_id'],
            'po_number'         => $this->docService->next('purchase_order'),
            'order_date'        => $validated['order_date'],
            'expected_delivery' => $validated['expected_delivery'] ?? null,
            'notes'             => $validated['notes'] ?? null,
            'status'            => 'draft',
            'subtotal'          => $totals['subtotal'],
            'tax_amount'        => $totals['tax_amount'],
            'total_amount'      => $totals['total_amount'],
        ]);

        foreach ($totals['items_with_tax'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id'        => $item['product_id'] ?? null,
                'description'       => $item['description'],
                'quantity'          => $item['quantity'],
                'unit_price'        => $item['unit_price'],
                'tax_category_id'   => $item['tax_category_id'] ?? null,
                'tax_amount'        => $item['tax_amount'],
                'subtotal'          => $item['subtotal'],
                'total'             => $item['total'],
            ]);
        }

        return $po;
    });
}

/**
 * Mark PO as received — creates GoodsReceipt automatically.
 */
public function receive(PurchaseOrder $po, array $receivedQtys): GoodsReceipt
{
    return DB::transaction(function () use ($po, $receivedQtys) {
        abort_unless(in_array($po->status, ['draft', 'sent', 'partial']), 422,
            'Ce bon de commande ne peut pas être réceptionné dans son état actuel.');

        $receipt = GoodsReceipt::create([
            'purchase_order_id' => $po->id,
            'receipt_date'      => now(),
            'status'            => 'received',
        ]);

        foreach ($po->items as $item) {
            $qtyReceived = $receivedQtys[$item->id] ?? 0;
            if ($qtyReceived <= 0) continue;

            GoodsReceiptItem::create([
                'goods_receipt_id'      => $receipt->id,
                'purchase_order_item_id'=> $item->id,
                'product_id'            => $item->product_id,
                'quantity_received'     => $qtyReceived,
            ]);

            // Increase stock if product is tracked
            if ($item->product_id) {
                app(\App\Services\Inventory\StockService::class)
                    ->adjust($item->product_id, $qtyReceived, 'purchase_receipt',
                        "Réception BC #{$po->po_number}");
            }
        }

        $po->update(['status' => 'received']);
        return $receipt;
    });
}
```

### Task 5.4 — Create Blade Views

- `resources/views/backoffice/purchases/suppliers/index.blade.php`
  Reference: `resources/views/customers.blade.php` (same structure)
- `resources/views/backoffice/purchases/suppliers/create.blade.php`
  Reference: `resources/views/add-customer.blade.php`
- `resources/views/backoffice/purchases/purchase-orders/index.blade.php`
  Reference: `resources/views/purchase-orders.blade.php`
- `resources/views/backoffice/purchases/purchase-orders/create.blade.php`
  Reference: `resources/views/add-invoice.blade.php` (same line items pattern)
- `resources/views/backoffice/purchases/vendor-bills/index.blade.php`
  Reference: `resources/views/purchases.blade.php`

PO status badge mirrors invoice status (draft/sent/partial/received/cancelled).

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/purchases.php` | Filled |
| `app/Services/Purchases/PurchaseOrderService.php` | New |
| `app/Services/Purchases/SupplierPaymentService.php` | New |
| `app/Http/Controllers/Backoffice/Purchases/SupplierController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Purchases/PurchaseOrderController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Purchases/VendorBillController.php` | Rewritten |
| `app/Policies/SupplierPolicy.php` | New |
| `app/Policies/PurchaseOrderPolicy.php` | New |
| `app/Policies/VendorBillPolicy.php` | New |
| All Purchases Blade views | New |

---

## 6. Acceptance Criteria

- [ ] PO created with auto-number `PO-00001`
- [ ] PO received → `GoodsReceipt` created → tracked product stock increased
- [ ] `VendorBill` can be created from received PO
- [ ] Supplier payment allocation prevents over-payment
- [ ] All `purchases.*` permissions enforced
- [ ] Supplier from another tenant not accessible

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Purchases/SupplierCrudTest.php` | Feature | Supplier CRUD |
| `tests/Feature/Purchases/PurchaseOrderTest.php` | Feature | PO creation + receive flow |
| `tests/Feature/Purchases/VendorBillTest.php` | Feature | Bill creation + payment |

---

## 8. Multi-Tenant Pitfalls

- Validate `supplier_id` belongs to current tenant in `StorePurchaseOrderRequest`
- Validate `product_id` in items belongs to current tenant
- `SupplierPaymentAllocation` must only reference vendor bills from same tenant
- `GoodsReceipt` has `tenant_id` — must be auto-filled by `BelongsToTenant`

---

## 9. Schema Notes

**`purchase_orders` columns:** `tenant_id`, `supplier_id`, `po_number`, `order_date`, `expected_delivery`, `subtotal`, `tax_amount`, `total_amount`, `status` (draft/sent/partial/received/cancelled), `notes`

**`goods_receipts` columns:** `tenant_id`, `purchase_order_id`, `receipt_date`, `notes`, `status`

Do NOT add `invoice_number` to PO — it has its own `po_number`. Do NOT rename any column.

---

## 10. UI Instructions

- Supplier list → mirror `customers.blade.php` exactly
- PO create → mirror `add-invoice.blade.php` (same line items pattern)
- "Receive Goods" button: only visible when PO status is `draft`, `sent`, or `partial`
- Vendor bill status: `draft` / `pending` / `partial` / `paid` / `cancelled`
