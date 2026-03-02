# Phase 10 — Dashboard KPIs

> **Depends on:** Phases 2–7 (all business modules must have real data)
> **Complexity:** S
> **Why last:** KPIs are aggregations of all other modules. Build this after the data exists.

---

## 1. Objective

Replace the stub `DashboardController@index` with real aggregated KPIs:
- Revenue cards (MTD, YTD, outstanding)
- Invoice status breakdown
- Recent invoices
- Top customers
- Low-stock alerts
- Revenue trend chart (12-month)

All queries cached for 5 minutes. All data strictly scoped to current tenant.

---

## 2. Scope

**Routes:** `routes/backoffice/dashboard.php` (already routed — controller only)
**Controller:** `app/Http/Controllers/Backoffice/DashboardController.php` (update)
**Reuse:** `ReportService` from Phase 8
**No new routes or models needed.**

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| KPI aggregation | `app/Services/Reports/ReportService.php` (extend) | Existing Service |
| Dashboard caching | `Cache::remember()` with tenant-keyed keys | Controller |
| Authorization | None — dashboard is accessible to all authenticated tenant users | — |

---

## 4. Ordered Task Breakdown

### Task 10.1 — Add KPI methods to `ReportService`

```php
// Append to app/Services/Reports/ReportService.php

public function dashboardKpis(): array
{
    $tenantId = $this->tenantId();
    $cacheKey = "dashboard:kpis:{$tenantId}";

    return Cache::remember($cacheKey, 300, function () use ($tenantId) {
        $now     = now();
        $mtdFrom = $now->copy()->startOfMonth()->toDateString();
        $ytdFrom = $now->copy()->startOfYear()->toDateString();
        $today   = $now->toDateString();

        // Revenue MTD (invoices, non-cancelled)
        $revenueMtd = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$mtdFrom, $today])
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');

        // Outstanding (unpaid invoices)
        $outstanding = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'partial'])
            ->sum('total_amount');

        // Overdue invoices (due_date < today, not paid)
        $overdueCount = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'partial'])
            ->where('due_date', '<', $today)
            ->count();

        // Total customers
        $customerCount = \App\Models\CRM\Customer::where('tenant_id', $tenantId)->count();

        // Invoice status breakdown (for donut chart)
        $statusBreakdown = Invoice::where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Recent invoices (last 5)
        $recentInvoices = Invoice::where('tenant_id', $tenantId)
            ->with('customer:id,name')
            ->latest('invoice_date')
            ->limit(5)
            ->get();

        // Top customers (YTD)
        $topCustomers = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$ytdFrom, $today])
            ->whereNotIn('status', ['cancelled'])
            ->with('customer:id,name')
            ->selectRaw('customer_id, SUM(total_amount) as revenue')
            ->groupBy('customer_id')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Revenue last 12 months (for line chart)
        $revenueTrend = Invoice::where('tenant_id', $tenantId)
            ->where('invoice_date', '>=', now()->subMonths(11)->startOfMonth())
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw("DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Low stock alerts
        $lowStockCount = \App\Models\Inventory\ProductStock::where('tenant_id', $tenantId)
            ->whereRaw('quantity_on_hand <= reorder_level')
            ->count();

        return compact(
            'revenueMtd', 'outstanding', 'overdueCount',
            'customerCount', 'statusBreakdown', 'recentInvoices',
            'topCustomers', 'revenueTrend', 'lowStockCount'
        );
    });
}
```

### Task 10.2 — Rewrite `DashboardController@index`

```php
// app/Http/Controllers/Backoffice/DashboardController.php
<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index()
    {
        $kpis = $this->reportService->dashboardKpis();
        return view('backoffice.dashboard', $kpis);
    }
}
```

### Task 10.3 — Create `resources/views/backoffice/dashboard.blade.php`

Reference: `resources/views/admin-dashboard.blade.php`

Dynamic sections:

**KPI Cards:**
```blade
{{-- Revenue MTD --}}
<div class="col-xl-3 col-md-6">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <span class="badge bg-primary-light p-3 rounded-circle">
                        <i class="ti ti-currency-dollar fs-4 text-primary"></i>
                    </span>
                </div>
                <div class="flex-grow-1 ms-3">
                    <p class="text-muted mb-1">Chiffre d'affaires (mois)</p>
                    <h4 class="mb-0">{{ number_format($revenueMtd, 2) }} {{ config('app.currency') }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Outstanding --}}
<div class="col-xl-3 col-md-6">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <span class="badge bg-warning-light p-3 rounded-circle">
                        <i class="ti ti-clock fs-4 text-warning"></i>
                    </span>
                </div>
                <div class="flex-grow-1 ms-3">
                    <p class="text-muted mb-1">Impayés en cours</p>
                    <h4 class="mb-0">{{ number_format($outstanding, 2) }} {{ config('app.currency') }}</h4>
                    @if($overdueCount > 0)
                        <small class="text-danger">{{ $overdueCount }} en retard</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Customers --}}
<div class="col-xl-3 col-md-6">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <span class="badge bg-success-light p-3 rounded-circle">
                        <i class="ti ti-users fs-4 text-success"></i>
                    </span>
                </div>
                <div class="flex-grow-1 ms-3">
                    <p class="text-muted mb-1">Clients actifs</p>
                    <h4 class="mb-0">{{ number_format($customerCount) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Low stock --}}
<div class="col-xl-3 col-md-6">
    <div class="card {{ $lowStockCount > 0 ? 'border-danger' : '' }}">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <span class="badge {{ $lowStockCount > 0 ? 'bg-danger-light' : 'bg-info-light' }} p-3 rounded-circle">
                        <i class="ti ti-package fs-4 {{ $lowStockCount > 0 ? 'text-danger' : 'text-info' }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1 ms-3">
                    <p class="text-muted mb-1">Alertes stock bas</p>
                    <h4 class="mb-0 {{ $lowStockCount > 0 ? 'text-danger' : '' }}">{{ $lowStockCount }}</h4>
                    @if($lowStockCount > 0)
                        <small><a href="{{ route('bo.inventory.stock.index', ['low_stock' => 1]) }}" class="text-danger">Voir les produits</a></small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
```

**Revenue Trend Chart:**
```blade
{{-- Pass data as JSON for Chart.js --}}
<script>
const revenueData = {
    labels: {!! json_encode($revenueTrend->pluck('month')) !!},
    datasets: [{
        label: 'Chiffre d\'affaires',
        data: {!! json_encode($revenueTrend->pluck('revenue')->map(fn($v) => (float)$v)) !!},
        borderColor: '#2563eb',
        fill: false,
    }]
};
</script>
```

**Recent Invoices Table:**
```blade
@forelse($recentInvoices as $invoice)
<tr>
    <td><a href="{{ route('bo.sales.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
    <td>{{ $invoice->customer->name }}</td>
    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
    <td>{{ number_format($invoice->total_amount, 2) }} {{ config('app.currency') }}</td>
    <td>
        @php $statusLabels = ['draft'=>'Brouillon','sent'=>'Envoyée','partial'=>'Partielle','paid'=>'Payée','cancelled'=>'Annulée']; @endphp
        <span class="badge bg-{{ ['draft'=>'secondary','sent'=>'info','partial'=>'warning','paid'=>'success','cancelled'=>'danger'][$invoice->status] }}-light text-{{ ['draft'=>'secondary','sent'=>'info','partial'=>'warning','paid'=>'success','cancelled'=>'danger'][$invoice->status] }}">
            {{ $statusLabels[$invoice->status] ?? $invoice->status }}
        </span>
    </td>
</tr>
@empty
<tr><td colspan="5" class="text-center">Aucune facture récente.</td></tr>
@endforelse
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `app/Http/Controllers/Backoffice/DashboardController.php` | Rewritten |
| `app/Services/Reports/ReportService.php` | Extended (dashboardKpis method) |
| `resources/views/backoffice/dashboard.blade.php` | New |

---

## 6. Acceptance Criteria

- [ ] Dashboard loads in < 500ms (caching applied — verify with query log: max 10 queries)
- [ ] Revenue MTD shows correct sum for current month's non-cancelled invoices
- [ ] Outstanding amount matches sum of `sent` + `partial` invoices
- [ ] Overdue count correct (due_date < today, status in sent/partial)
- [ ] Low-stock card links correctly to inventory report with filter
- [ ] Revenue trend chart shows 12 data points
- [ ] All data scoped to current tenant (verify with TenantIsolationTest)
- [ ] Cache TTL: refreshes after 5 minutes (test by flushing cache)

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Dashboard/DashboardKpiTest.php` | Feature | KPI accuracy, tenant isolation, caching |

---

## 8. Multi-Tenant Pitfalls

- Cache key MUST include `tenant_id`:
  ```php
  Cache::remember("dashboard:kpis:{$tenantId}", 300, fn() => ...)
  ```
  Without this, Tenant B sees Tenant A's dashboard data.
- Explicit `where('tenant_id', $tenantId)` as defense-in-depth even though TenantScope is active
- DATE_FORMAT SQL function works in MySQL — use `strftime` for SQLite in tests:
  ```php
  $groupBy = config('database.default') === 'sqlite'
      ? "strftime('%Y-%m', invoice_date)"
      : "DATE_FORMAT(invoice_date, '%Y-%m')";
  ```

---

## 9. Schema Notes

All queries use existing columns. No schema changes in this phase.

---

## 10. UI Instructions

- **Reference:** `resources/views/admin-dashboard.blade.php` — copy exact structure
- Use the same KPI card component pattern from the reference
- Charts: use the theme's existing Chart.js initialization pattern
- Top customers table: same format as other data tables in the theme
- "Voir plus" links route to their respective module index pages
