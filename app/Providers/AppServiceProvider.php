<?php

namespace App\Providers;

use App\Services\System\DocumentNumberService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentNumberService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Admin bypass — tenant_id is null means platform-level super admin
        // Admin role bypass — admin users have full access within their tenant
        Gate::before(function ($user, $ability) {
            if ($user->tenant_id === null) {
                return true; // Super admin — full access
            }

            if ($user->hasRole('admin')) {
                return true; // Tenant admin — full access within tenant
            }

            return null; // Let policies/permissions decide
        });

        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\CRM\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(\App\Models\Catalog\Product::class, \App\Policies\ProductPolicy::class);
        Gate::policy(\App\Models\Sales\Invoice::class, \App\Policies\InvoicePolicy::class);
        Gate::policy(\App\Models\Sales\Quote::class, \App\Policies\QuotePolicy::class);
        Gate::policy(\App\Models\Sales\Payment::class, \App\Policies\PaymentPolicy::class);
        Gate::policy(\App\Models\Sales\CreditNote::class, \App\Policies\CreditNotePolicy::class);
        Gate::policy(\App\Models\Purchases\Supplier::class, \App\Policies\SupplierPolicy::class);
        Gate::policy(\App\Models\Purchases\PurchaseOrder::class, \App\Policies\PurchaseOrderPolicy::class);
        Gate::policy(\App\Models\Purchases\VendorBill::class, \App\Policies\VendorBillPolicy::class);
        Gate::policy(\App\Models\Inventory\Warehouse::class, \App\Policies\WarehousePolicy::class);
        Gate::policy(\App\Models\Inventory\StockTransfer::class, \App\Policies\StockTransferPolicy::class);
        Gate::policy(\App\Models\Finance\BankAccount::class, \App\Policies\BankAccountPolicy::class);
        Gate::policy(\App\Models\Finance\Expense::class, \App\Policies\ExpensePolicy::class);
        Gate::policy(\App\Models\Finance\Income::class, \App\Policies\IncomePolicy::class);
        Gate::policy(\App\Models\Finance\FinanceCategory::class, \App\Policies\FinanceCategoryPolicy::class);
        Gate::policy(\App\Models\Catalog\ProductCategory::class, \App\Policies\ProductCategoryPolicy::class);
        Gate::policy(\App\Models\Catalog\TaxCategory::class, \App\Policies\TaxCategoryPolicy::class);
        Gate::policy(\App\Models\Catalog\TaxGroup::class, \App\Policies\TaxGroupPolicy::class);
        Gate::policy(\App\Models\Catalog\Unit::class, \App\Policies\UnitPolicy::class);
        Gate::policy(\App\Models\Inventory\StockMovement::class, \App\Policies\StockMovementPolicy::class);
        Gate::policy(\App\Models\Finance\Loan::class, \App\Policies\LoanPolicy::class);
        Gate::policy(\App\Models\Sales\DeliveryChallan::class, \App\Policies\DeliveryChallanPolicy::class);
        Gate::policy(\App\Models\Sales\Refund::class, \App\Policies\RefundPolicy::class);
        Gate::policy(\App\Models\Purchases\GoodsReceipt::class, \App\Policies\GoodsReceiptPolicy::class);
        Gate::policy(\App\Models\Purchases\DebitNote::class, \App\Policies\DebitNotePolicy::class);
        Gate::policy(\App\Models\Purchases\SupplierPayment::class, \App\Policies\SupplierPaymentPolicy::class);
        Gate::policy(\App\Models\Pro\RecurringInvoice::class, \App\Policies\RecurringInvoicePolicy::class);
        Gate::policy(\App\Models\Pro\InvoiceReminder::class, \App\Policies\InvoiceReminderPolicy::class);
        Gate::policy(\App\Models\Pro\Branch::class, \App\Policies\BranchPolicy::class);

        // Share appearance settings with the head partial for theme sync
        View::composer('backoffice.layout.partials.head', function ($view) {
            $appearance = [];
            if (TenantContext::check()) {
                $tenant = TenantContext::get();
                $appearance = $tenant->settings->modules_settings['appearance'] ?? [];
            }
            $view->with('appearanceSettings', $appearance);
        });
    }
}
