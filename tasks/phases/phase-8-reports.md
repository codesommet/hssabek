# Phase 8 — Reports

> **Depends on:** All business modules (Phases 2–7)
> **Complexity:** M

---

## 1. Objective

Provide read-only analytical reports scoped strictly to the current tenant:
- Sales Report (revenue by period, top customers, invoice status breakdown)
- Customer Report (receivables, customer activity)
- Purchase Report (spending by supplier, PO status)
- Finance Report (income vs expense, cash flow by account)
- Inventory Report (stock levels, low-stock alerts, movement history)

---

## 2. Scope

**Route file:** `routes/backoffice/reports.php` (currently empty — controllers don't exist)
**New Controllers:**
- `app/Http/Controllers/Backoffice/Reports/SalesReportController.php`
- `app/Http/Controllers/Backoffice/Reports/CustomerReportController.php`
- `app/Http/Controllers/Backoffice/Reports/PurchaseReportController.php`
- `app/Http/Controllers/Backoffice/Reports/FinanceReportController.php`
- `app/Http/Controllers/Backoffice/Reports/InventoryReportController.php`

**New Service:**
- `app/Services/Reports/ReportService.php`

**New Job (for export):**
- `app/Jobs/ExportReportJob.php`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| All aggregate queries | `app/Services/Reports/ReportService.php` | NEW Service |
| Authorization | Inline `abort_unless(can(...))` — no dedicated Policy | Controller |
| CSV export | `app/Jobs/ExportReportJob.php` | NEW Job |
| Caching | `Cache::remember("report:{$tenantId}:{$key}", 300, fn() => ...)` | Controller / Service |

**Reports are read-only.** No create/update/delete. No FormRequests needed.

---

## 4. Ordered Task Breakdown

### Task 8.1 — Fill `routes/backoffice/reports.php`

```php
<?php

use App\Http\Controllers\Backoffice\Reports\{SalesReportController, CustomerReportController, PurchaseReportController, FinanceReportController, InventoryReportController};
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->as('reports.')->group(function () {
    Route::get('sales',     [SalesReportController::class,    'index'])->name('sales');
    Route::get('customers', [CustomerReportController::class, 'index'])->name('customers');
    Route::get('purchases', [PurchaseReportController::class, 'index'])->name('purchases');
    Route::get('finance',   [FinanceReportController::class,  'index'])->name('finance');
    Route::get('inventory', [InventoryReportController::class,'index'])->name('inventory');

    // Export endpoints
    Route::post('sales/export',     [SalesReportController::class,    'export'])->name('sales.export');
    Route::post('finance/export',   [FinanceReportController::class,  'export'])->name('finance.export');
    Route::post('inventory/export', [InventoryReportController::class,'export'])->name('inventory.export');
});
```

### Task 8.2 — Implement `ReportService`

```php
// app/Services/Reports/ReportService.php
<?php

namespace App\Services\Reports;

use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\Finance\Expense;
use App\Models\Finance\Income;
use App\Models\Inventory\ProductStock;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private function tenantId(): string
    {
        return TenantContext::id() ?? throw new \RuntimeException('No tenant context.');
    }

    /**
     * Revenue summary for a date range.
     */
    public function salesSummary(string $from, string $to): array
    {
        $tenantId = $this->tenantId();
        $cacheKey = "report:sales:{$tenantId}:{$from}:{$to}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $from, $to) {
            $invoices = Invoice::where('tenant_id', $tenantId)
                ->whereBetween('invoice_date', [$from, $to])
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw('
                    COUNT(*) as invoice_count,
                    SUM(total_amount) as total_revenue,
                    SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END) as collected,
                    SUM(CASE WHEN status != "paid" THEN total_amount ELSE 0 END) as outstanding
                ')
                ->first();

            $byMonth = Invoice::where('tenant_id', $tenantId)
                ->whereBetween('invoice_date', [$from, $to])
                ->whereNotIn('status', ['cancelled'])
                ->selectRaw("DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(total_amount) as revenue")
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $topCustomers = Invoice::where('tenant_id', $tenantId)
                ->whereBetween('invoice_date', [$from, $to])
                ->whereNotIn('status', ['cancelled'])
                ->with('customer:id,name')
                ->selectRaw('customer_id, SUM(total_amount) as total')
                ->groupBy('customer_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get();

            return compact('invoices', 'byMonth', 'topCustomers');
        });
    }

    /**
     * Finance summary: income vs expenses by category.
     */
    public function financeSummary(string $from, string $to): array
    {
        $tenantId = $this->tenantId();
        $cacheKey = "report:finance:{$tenantId}:{$from}:{$to}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $from, $to) {
            $totalExpenses = Expense::where('tenant_id', $tenantId)
                ->whereBetween('expense_date', [$from, $to])
                ->sum('amount');

            $totalIncome = Income::where('tenant_id', $tenantId)
                ->whereBetween('income_date', [$from, $to])
                ->sum('amount');

            $expensesByCategory = Expense::where('tenant_id', $tenantId)
                ->whereBetween('expense_date', [$from, $to])
                ->with('category:id,name')
                ->selectRaw('finance_category_id, SUM(amount) as total')
                ->groupBy('finance_category_id')
                ->orderByDesc('total')
                ->get();

            return compact('totalExpenses', 'totalIncome', 'expensesByCategory');
        });
    }

    /**
     * Low stock report.
     */
    public function lowStockReport(): \Illuminate\Support\Collection
    {
        return ProductStock::where('tenant_id', $this->tenantId())
            ->whereRaw('quantity_on_hand <= reorder_level')
            ->with(['product:id,name,sku', 'warehouse:id,name'])
            ->orderBy('quantity_on_hand')
            ->get();
    }
}
```

### Task 8.3 — Implement `SalesReportController`

```php
// app/Http/Controllers/Backoffice/Reports/SalesReportController.php
public function index(Request $request)
{
    abort_unless(auth()->user()->can('reports.sales.view'), 403);

    $from = $request->date('from', 'Y-m-d') ?? now()->startOfMonth()->toDateString();
    $to   = $request->date('to', 'Y-m-d')   ?? now()->toDateString();

    $data = app(ReportService::class)->salesSummary($from, $to);

    return view('backoffice.reports.sales', array_merge($data, compact('from', 'to')));
}

public function export(Request $request)
{
    abort_unless(auth()->user()->can('reports.sales.view'), 403);

    $from = $request->input('from', now()->startOfMonth()->toDateString());
    $to   = $request->input('to',   now()->toDateString());

    dispatch(new ExportReportJob(
        tenantId: TenantContext::id(),
        type:     'sales',
        from:     $from,
        to:       $to,
        userId:   auth()->id(),
    ));

    return redirect()->back()
        ->with('info', 'L\'export est en cours. Vous serez notifié lorsqu\'il sera prêt.');
}
```

Use same pattern for all other report controllers.

### Task 8.4 — Implement `ExportReportJob`

```php
// app/Jobs/ExportReportJob.php
class ExportReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $type,
        public readonly string $from,
        public readonly string $to,
        public readonly string $userId,
    ) {}

    public function handle(ReportService $reportService): void
    {
        $tenant = \App\Models\Tenancy\Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $data = match($this->type) {
            'sales'   => $reportService->salesSummary($this->from, $this->to),
            'finance' => $reportService->financeSummary($this->from, $this->to),
            default   => throw new \InvalidArgumentException("Report type unknown: {$this->type}"),
        };

        // Generate CSV
        $filename = "report-{$this->type}-{$this->from}-{$this->to}.csv";
        $path = storage_path("app/exports/{$this->tenantId}/{$filename}");

        // ... CSV generation logic ...

        // Notify user when complete (future: add notification)
    }
}
```

### Task 8.5 — Create Blade Views

- `resources/views/backoffice/reports/sales.blade.php`
  Reference: `resources/views/sales-report.blade.php`
- `resources/views/backoffice/reports/customers.blade.php`
  Reference: `resources/views/customers-report.blade.php`
- `resources/views/backoffice/reports/finance.blade.php`
  Reference: closest settings/chart template
- `resources/views/backoffice/reports/inventory.blade.php`
  Reference: `resources/views/inventory.blade.php`

All report pages must include:
- Date range filter (`from` + `to` date inputs)
- Summary KPI cards
- Data table
- Export button (POST form to `.export` route)

---

## 5. Deliverables

| File | Action |
|------|--------|
| `routes/backoffice/reports.php` | Filled |
| `app/Services/Reports/ReportService.php` | New |
| `app/Http/Controllers/Backoffice/Reports/*.php` (5 controllers) | New |
| `app/Jobs/ExportReportJob.php` | New |
| All Reports Blade views | New |

---

## 6. Acceptance Criteria

- [ ] Sales report loads with correct revenue totals for selected date range
- [ ] Finance report shows income vs expense correctly
- [ ] Low-stock report only shows products for current tenant
- [ ] Cache is used (5 min TTL) — verify with query log
- [ ] Export dispatches a job to queue (check `jobs` table)
- [ ] All `reports.*` permissions enforced
- [ ] No cross-tenant data in any report query

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Reports/SalesReportTest.php` | Feature | Date range, totals, tenant isolation |
| `tests/Feature/Reports/FinanceReportTest.php` | Feature | Income vs expense |
| `tests/Feature/Reports/LowStockReportTest.php` | Feature | Low stock threshold |

---

## 8. Multi-Tenant Pitfalls

- ❌ NEVER: `Invoice::whereBetween(...)` without also specifying `where('tenant_id', ...)`
- ✅ DO: Use `TenantScope` (automatic via model) — but in `ReportService`, the scope IS applied automatically since models use it
- ✅ DO: Always verify `TenantContext::set()` in `ExportReportJob::handle()`
- ✅ DO: Cache key must include `tenant_id` — never share cache across tenants
  ```php
  Cache::remember("report:sales:{$tenantId}:{$from}:{$to}", 300, fn() => ...)
  ```

---

## 9. Schema Notes

All queries use existing columns. No schema changes needed for this phase.

---

## 10. UI Instructions

- **Sales report reference:** `resources/views/sales-report.blade.php`
- **Customer report reference:** `resources/views/customers-report.blade.php`
- Charts: use the theme's built-in chart JS components (already loaded in theme)
- KPI cards: match the style from `admin-dashboard.blade.php`
- Date range filter: use the same input pattern as the reference template
- Export button: styled as secondary action, not primary
