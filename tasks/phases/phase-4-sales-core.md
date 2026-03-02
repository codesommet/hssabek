# Phase 4 — Sales Core (Quotes → Invoices → Payments → Credit Notes)

> **Depends on:** Phase 0, Phase 2 (CRM), Phase 3 (Catalog)
> **Complexity:** L
> **Critical path:** This is the core revenue feature. Build it correctly or the accounting books will be wrong.

---

## 1. Objective

Build the complete quote-to-cash pipeline:
1. **Quote** → create, edit, convert to invoice
2. **Invoice** → create with line items + charges, send, track status, calculate totals server-side
3. **Payment** → record payment, allocate to invoice(s)
4. **Credit Note** → issue against an invoice, apply to outstanding balance

Every total (subtotal, tax, grand total) is calculated **server-side only**. Client-submitted totals are ignored.

---

## 2. Scope

**Route file:** `routes/backoffice/sales.php` (currently empty — fill it)
**Controllers (rewrite from JSON stubs):**
- `app/Http/Controllers/Backoffice/Sales/QuoteController.php`
- `app/Http/Controllers/Backoffice/Sales/InvoiceController.php`
- `app/Http/Controllers/Backoffice/Sales/PaymentController.php`
- `app/Http/Controllers/Backoffice/Sales/CreditNoteController.php`

**Models (existing — do not modify schema):**
- `Invoice` — `tenant_id`, `customer_id`, `invoice_number`, `invoice_date`, `due_date`, `subtotal`, `tax_amount`, `total_amount`, `status`, `notes`, `signature_id`
- `InvoiceItem` — `invoice_id`, `tenant_id`, `product_id`, `description`, `quantity`, `unit_price`, `tax_category_id`, `tax_amount`, `subtotal`, `total`
- `InvoiceCharge` — `invoice_id`, `tenant_id`, `description`, `amount`, `type`
- `Quote` / `QuoteItem` / `QuoteCharge` — parallel structure to Invoice
- `Payment` — `tenant_id`, `customer_id`, `amount`, `payment_date`, `payment_method_id`, `bank_account_id`, `reference`, `notes`
- `PaymentAllocation` — `payment_id`, `invoice_id`, `amount`
- `CreditNote` / `CreditNoteItem` / `CreditNoteApplication` — parallel structure

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| Tax calculation | `app/Services/Sales/TaxCalculationService.php` | NEW Service |
| Invoice orchestration | `app/Services/Sales/InvoiceService.php` | NEW Service |
| Quote orchestration | `app/Services/Sales/QuoteService.php` | NEW Service |
| Payment + allocation | `app/Services/Sales/PaymentService.php` | NEW Service |
| Credit note application | `app/Services/Sales/CreditNoteService.php` | NEW Service |
| Invoice authorization | `app/Policies/InvoicePolicy.php` | NEW Policy |
| Quote authorization | `app/Policies/QuotePolicy.php` | NEW Policy |
| Payment authorization | `app/Policies/PaymentPolicy.php` | NEW Policy |
| Invoice validation | `app/Http/Requests/Sales/Store/StoreInvoiceRequest.php` | Audit + fix |
| Payment validation | `app/Http/Requests/Sales/Store/StorePaymentRequest.php` | Audit + fix |
| Credit note validation | `app/Http/Requests/Sales/Store/StoreCreditNoteRequest.php` | Audit |
| Invoice send email | `app/Notifications/InvoiceSentNotification.php` | NEW Notification |
| Invoice send job | `app/Jobs/SendInvoiceEmailJob.php` | NEW Job |

---

## 4. Ordered Task Breakdown

### Task 4.1 — Implement `TaxCalculationService`

```php
// app/Services/Sales/TaxCalculationService.php
<?php

namespace App\Services\Sales;

use App\Models\Catalog\TaxCategory;

class TaxCalculationService
{
    /**
     * Calculate totals for a set of line items.
     * Each item: ['quantity', 'unit_price', 'tax_category_id']
     * Returns: ['subtotal', 'tax_amount', 'total_amount', 'items_with_tax']
     */
    public function calculateItems(array $items): array
    {
        $subtotal  = 0.0;
        $taxAmount = 0.0;
        $itemResults = [];

        // Pre-load tax categories to avoid N+1
        $taxCategoryIds = collect($items)->pluck('tax_category_id')->filter()->unique();
        $taxCategories  = TaxCategory::whereIn('id', $taxCategoryIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $lineSubtotal = round((float)$item['quantity'] * (float)$item['unit_price'], 2);
            $lineTax      = 0.0;

            if (!empty($item['tax_category_id'])) {
                $taxCategory = $taxCategories->get($item['tax_category_id']);
                if ($taxCategory) {
                    $lineTax = round($lineSubtotal * ($taxCategory->rate / 100), 2);
                }
            }

            $lineTotal = $lineSubtotal + $lineTax;

            $itemResults[] = array_merge($item, [
                'subtotal'   => $lineSubtotal,
                'tax_amount' => $lineTax,
                'total'      => $lineTotal,
            ]);

            $subtotal  += $lineSubtotal;
            $taxAmount += $lineTax;
        }

        return [
            'subtotal'       => round($subtotal, 2),
            'tax_amount'     => round($taxAmount, 2),
            'total_amount'   => round($subtotal + $taxAmount, 2),
            'items_with_tax' => $itemResults,
        ];
    }

    /**
     * Calculate additional charges (shipping, handling, etc.)
     */
    public function calculateCharges(array $charges): float
    {
        return round(array_sum(array_column($charges, 'amount')), 2);
    }
}
```

### Task 4.2 — Implement `InvoiceService`

```php
// app/Services/Sales/InvoiceService.php
<?php

namespace App\Services\Sales;

use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\InvoiceCharge;
use App\Services\System\DocumentNumberService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly TaxCalculationService $taxService,
        private readonly DocumentNumberService $docService,
    ) {}

    /**
     * Create an invoice with line items, charges, and calculated totals.
     * NEVER trust client-submitted total amounts.
     */
    public function create(array $validated): Invoice
    {
        return DB::transaction(function () use ($validated) {
            $items   = $validated['items'] ?? [];
            $charges = $validated['charges'] ?? [];

            $totals = $this->taxService->calculateItems($items);
            $chargesTotal = $this->taxService->calculateCharges($charges);

            $invoice = Invoice::create([
                'customer_id'    => $validated['customer_id'],
                'invoice_number' => $this->docService->next('invoice'),
                'invoice_date'   => $validated['invoice_date'],
                'due_date'       => $validated['due_date'],
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'draft',
                // Server-calculated — never from request:
                'subtotal'       => $totals['subtotal'],
                'tax_amount'     => $totals['tax_amount'],
                'total_amount'   => $totals['total_amount'] + $chargesTotal,
            ]);

            foreach ($totals['items_with_tax'] as $item) {
                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'product_id'      => $item['product_id'] ?? null,
                    'description'     => $item['description'],
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'tax_category_id' => $item['tax_category_id'] ?? null,
                    'tax_amount'      => $item['tax_amount'],
                    'subtotal'        => $item['subtotal'],
                    'total'           => $item['total'],
                ]);
            }

            foreach ($charges as $charge) {
                InvoiceCharge::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $charge['description'],
                    'amount'      => $charge['amount'],
                    'type'        => $charge['type'] ?? 'shipping',
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Transition invoice status — enforces allowed state machine.
     */
    public function transition(Invoice $invoice, string $newStatus): void
    {
        $allowed = [
            'draft'    => ['sent', 'cancelled'],
            'sent'     => ['partial', 'paid', 'cancelled'],
            'partial'  => ['paid', 'cancelled'],
            'paid'     => [],
            'cancelled'=> [],
        ];

        $permitted = $allowed[$invoice->status] ?? [];
        if (!in_array($newStatus, $permitted)) {
            throw new \DomainException(
                "Transition de statut invalide : {$invoice->status} → {$newStatus}"
            );
        }

        $invoice->update(['status' => $newStatus]);
    }

    /**
     * Recalculate and update invoice totals from its items.
     * Use after editing line items.
     */
    public function recalculate(Invoice $invoice): void
    {
        $invoice->loadMissing(['items', 'charges']);

        $items = $invoice->items->map(fn($i) => [
            'quantity'        => $i->quantity,
            'unit_price'      => $i->unit_price,
            'tax_category_id' => $i->tax_category_id,
        ])->toArray();

        $totals = $this->taxService->calculateItems($items);
        $chargesTotal = $invoice->charges->sum('amount');

        $invoice->update([
            'subtotal'     => $totals['subtotal'],
            'tax_amount'   => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'] + $chargesTotal,
        ]);
    }
}
```

### Task 4.3 — Implement `PaymentService`

```php
// app/Services/Sales/PaymentService.php
public function recordAndAllocate(array $validated): \App\Models\Sales\Payment
{
    return DB::transaction(function () use ($validated) {
        $payment = \App\Models\Sales\Payment::create([
            'customer_id'       => $validated['customer_id'],
            'amount'            => $validated['amount'],
            'payment_date'      => $validated['payment_date'],
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'bank_account_id'   => $validated['bank_account_id'] ?? null,
            'reference'         => $validated['reference'] ?? null,
            'notes'             => $validated['notes'] ?? null,
        ]);

        // Allocate to invoices
        $allocations = $validated['allocations'] ?? [];
        foreach ($allocations as $alloc) {
            $invoice = Invoice::findOrFail($alloc['invoice_id']);

            // Anti-over-allocation check
            $alreadyPaid = $invoice->payments()
                ->where('status', '!=', 'cancelled')
                ->sum('pivot_amount'); // sum of PaymentAllocation.amount for this invoice

            $outstanding = $invoice->total_amount - $alreadyPaid;

            if ((float)$alloc['amount'] > $outstanding + 0.01) { // 0.01 float tolerance
                throw new \DomainException(
                    "Montant d'allocation ({$alloc['amount']}) dépasse le solde restant ({$outstanding})."
                );
            }

            \App\Models\Sales\PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $alloc['invoice_id'],
                'amount'     => $alloc['amount'],
            ]);

            // Update invoice status
            $newPaid = $alreadyPaid + (float)$alloc['amount'];
            $newStatus = match(true) {
                $newPaid >= $invoice->total_amount - 0.01 => 'paid',
                $newPaid > 0 => 'partial',
                default => $invoice->status,
            };
            $invoice->update(['status' => $newStatus]);
        }

        return $payment;
    });
}
```

### Task 4.4 — Fill `routes/backoffice/sales.php`

```php
<?php

use App\Http\Controllers\Backoffice\Sales\{QuoteController, InvoiceController, PaymentController, CreditNoteController};
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->as('sales.')->group(function () {
    // Quotes
    Route::resource('quotes', QuoteController::class);
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice'])
        ->name('quotes.convert');
    Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])
        ->name('quotes.duplicate');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send',      [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/cancel',    [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::get('invoices/{invoice}/download',   [InvoiceController::class, 'download'])->name('invoices.download');

    // Payments
    Route::resource('payments', PaymentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Credit Notes
    Route::resource('credit-notes', CreditNoteController::class);
    Route::post('credit-notes/{creditNote}/apply', [CreditNoteController::class, 'apply'])
        ->name('credit-notes.apply');
});
```

### Task 4.5 — Rewrite `InvoiceController` (Thin)

```php
// app/Http/Controllers/Backoffice/Sales/InvoiceController.php
public function store(StoreInvoiceRequest $request)
{
    abort_unless(auth()->user()->can('sales.invoices.create'), 403);

    $invoice = $this->invoiceService->create($request->validated());

    return redirect()->route('bo.sales.invoices.show', $invoice)
        ->with('success', 'Facture créée avec succès.');
}

public function send(Invoice $invoice)
{
    abort_unless(auth()->user()->can('sales.invoices.edit'), 403);
    $this->invoiceService->transition($invoice, 'sent');
    dispatch(new SendInvoiceEmailJob($invoice));
    return redirect()->back()->with('success', 'Facture envoyée au client.');
}

public function cancel(Invoice $invoice)
{
    abort_unless(auth()->user()->can('sales.invoices.edit'), 403);
    $this->invoiceService->transition($invoice, 'cancelled');
    return redirect()->back()->with('success', 'Facture annulée.');
}

public function download(Invoice $invoice)
{
    abort_unless(auth()->user()->can('sales.invoices.view'), 403);
    // PDF generation handled in Phase 9
    // For now: redirect back with info message
    return redirect()->back()->with('info', 'La génération PDF sera disponible prochainement.');
}
```

### Task 4.6 — Update `StoreInvoiceRequest` — Remove Client-Submitted Totals

```php
// app/Http/Requests/Sales/Store/StoreInvoiceRequest.php
public function rules(): array
{
    return [
        'customer_id'  => ['required', 'uuid', Rule::exists('customers', 'id')
            ->where('tenant_id', TenantContext::id())],
        'invoice_date' => ['required', 'date'],
        'due_date'     => ['required', 'date', 'after_or_equal:invoice_date'],
        'notes'        => ['nullable', 'string', 'max:2000'],

        // Line items
        'items'                    => ['required', 'array', 'min:1'],
        'items.*.description'      => ['required', 'string', 'max:500'],
        'items.*.quantity'         => ['required', 'numeric', 'min:0.001'],
        'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
        'items.*.product_id'       => ['nullable', 'uuid', Rule::exists('products', 'id')
            ->where('tenant_id', TenantContext::id())],
        'items.*.tax_category_id'  => ['nullable', 'uuid', Rule::exists('tax_categories', 'id')
            ->where('tenant_id', TenantContext::id())],

        // Additional charges (optional)
        'charges'              => ['sometimes', 'array'],
        'charges.*.description'=> ['required_with:charges', 'string', 'max:255'],
        'charges.*.amount'     => ['required_with:charges', 'numeric', 'min:0'],
        'charges.*.type'       => ['required_with:charges', 'in:shipping,handling,discount,other'],

        // ❌ DO NOT include: subtotal, tax_amount, total_amount — calculated server-side
    ];
}
```

### Task 4.7 — Create Blade Views

**Invoice views:**
- `resources/views/backoffice/sales/invoices/index.blade.php`
  Reference: `resources/views/invoices.blade.php`
- `resources/views/backoffice/sales/invoices/create.blade.php`
  Reference: `resources/views/add-invoice.blade.php`
- `resources/views/backoffice/sales/invoices/edit.blade.php`
  Reference: `resources/views/edit-invoice.blade.php`
- `resources/views/backoffice/sales/invoices/show.blade.php`
  Reference: `resources/views/invoice-details.blade.php`

**Quote views (parallel structure to invoices):**
- `resources/views/backoffice/sales/quotes/index.blade.php`
  Reference: `resources/views/quotations.blade.php`
- `resources/views/backoffice/sales/quotes/create.blade.php`
  Reference: `resources/views/add-quotation.blade.php`

**Payment views:**
- `resources/views/backoffice/sales/payments/index.blade.php`
  Reference: `resources/views/invoices.blade.php` (table pattern)

**Invoice status badge:**
```blade
@php
$statusConfig = [
    'draft'     => ['bg-secondary-light text-secondary', 'Brouillon'],
    'sent'      => ['bg-info-light text-info',           'Envoyée'],
    'partial'   => ['bg-warning-light text-warning',     'Partielle'],
    'paid'      => ['bg-success-light text-success',     'Payée'],
    'cancelled' => ['bg-danger-light text-danger',       'Annulée'],
];
[$class, $label] = $statusConfig[$invoice->status] ?? ['bg-secondary', $invoice->status];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
```

**Invoice line items (dynamic JS-driven table in create/edit):**
The line items table requires JavaScript for add/remove rows and dynamic total calculation
(for display purposes only — server recalculates on submit). Use the existing JS patterns
from the reference `add-invoice.blade.php` template.

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/sales.php` | Filled |
| `app/Services/Sales/TaxCalculationService.php` | New |
| `app/Services/Sales/InvoiceService.php` | New |
| `app/Services/Sales/QuoteService.php` | New |
| `app/Services/Sales/PaymentService.php` | New |
| `app/Services/Sales/CreditNoteService.php` | New |
| `app/Http/Controllers/Backoffice/Sales/InvoiceController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Sales/QuoteController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Sales/PaymentController.php` | Rewritten |
| `app/Http/Controllers/Backoffice/Sales/CreditNoteController.php` | Rewritten |
| `app/Http/Requests/Sales/Store/StoreInvoiceRequest.php` | Fixed (no totals) |
| `app/Http/Requests/Sales/Store/StoreQuoteRequest.php` | Fixed |
| `app/Http/Requests/Sales/Store/StorePaymentRequest.php` | Fixed |
| `app/Policies/InvoicePolicy.php` | New |
| `app/Policies/QuotePolicy.php` | New |
| `app/Policies/PaymentPolicy.php` | New |
| `app/Jobs/SendInvoiceEmailJob.php` | New |
| `app/Notifications/InvoiceSentNotification.php` | New |
| All Sales Blade views | New |

---

## 6. Acceptance Criteria

- [ ] Invoice created → auto-number `INV-00001` assigned (never null, never duplicated)
- [ ] Invoice totals computed server-side (changing `total_amount` in POST has no effect)
- [ ] Invoice status transitions enforced: cannot move from `paid` → `draft`
- [ ] Payment allocation prevents over-payment (exception thrown if amount > outstanding)
- [ ] Quote → Invoice conversion copies all items + sets new invoice_number
- [ ] Credit note applied reduces invoice outstanding balance
- [ ] `sent` action dispatches `SendInvoiceEmailJob` to queue
- [ ] All permissions `sales.invoices.*` enforced
- [ ] Customer from another tenant rejected in `customer_id` validation
- [ ] Tax from another tenant rejected in `tax_category_id` validation
- [ ] Concurrent invoice creation with same sequence → no duplicate number

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Sales/InvoiceCreateTest.php` | Feature | Full creation with items + totals |
| `tests/Feature/Sales/InvoiceStatusTest.php` | Feature | State machine transitions |
| `tests/Feature/Sales/PaymentAllocationTest.php` | Feature | Over-payment prevention |
| `tests/Feature/Sales/QuoteConvertTest.php` | Feature | Quote → Invoice conversion |
| `tests/Unit/Services/TaxCalculationServiceTest.php` | Unit | Tax math accuracy |
| `tests/Unit/Services/InvoiceServiceTest.php` | Unit | Total computation |
| `tests/Unit/Services/PaymentServiceTest.php` | Unit | Allocation logic |

---

## 8. Multi-Tenant Pitfalls

- ❌ NEVER trust `customer_id` from request without verifying it belongs to current tenant
- ❌ NEVER trust `tax_category_id` without verifying it belongs to current tenant
- ✅ DO: All `Rule::exists()` calls must include `.where('tenant_id', TenantContext::id())`
- ✅ DO: `DocumentNumberService::next()` uses `lockForUpdate()` — concurrent invoice creation is safe
- ✅ DO: `SendInvoiceEmailJob` must set `TenantContext::set($tenant)` at start of `handle()`
- ✅ DO: Payment allocation must use `Invoice::findOrFail()` (TenantScope applied) to prevent cross-tenant allocation

---

## 9. Mass-Assignment Safeguards

- `subtotal`, `tax_amount`, `total_amount` — NEVER in `StoreInvoiceRequest::rules()`
- `invoice_number` — NEVER in `StoreInvoiceRequest::rules()` — generated by `DocumentNumberService`
- `status` — NEVER directly settable by client — only via `InvoiceService::transition()`
- `tenant_id` — removed from `$fillable` in Phase 0

---

## 10. Schema Notes

**`invoices` columns:** `tenant_id`, `customer_id`, `invoice_number` (string), `invoice_date`, `due_date`, `subtotal` (decimal:2), `tax_amount` (decimal:2), `total_amount` (decimal:2), `status` (draft/sent/partial/paid/cancelled), `notes`, `signature_id`

**`invoice_items` columns:** `invoice_id`, `tenant_id`, `product_id` (nullable), `description`, `quantity` (decimal), `unit_price` (decimal:2), `tax_category_id` (nullable), `tax_amount` (decimal:2), `subtotal` (decimal:2), `total` (decimal:2)

**`payment_allocations` columns:** `payment_id`, `invoice_id`, `amount` (decimal:2)

All decimal columns use `decimal:2` cast — never use float arithmetic. Use `round()` to 2 places.

---

## 11. UI Instructions

- **Invoice list reference:** `resources/views/invoices.blade.php`
- **Invoice create reference:** `resources/views/add-invoice.blade.php` (line items JS table)
- **Invoice detail reference:** `resources/views/invoice-details.blade.php`
- **Quote list reference:** `resources/views/quotations.blade.php`
- **Quote create reference:** `resources/views/add-quotation.blade.php`
- Line item totals are calculated client-side in JS for **display purposes** only
- Server always recalculates — never trust the displayed total
- Status badge uses color coding (see code sample above)
- "Convert to Invoice" button: only visible when quote status allows conversion
- "Send" button: only visible for `draft` or `sent` invoices
