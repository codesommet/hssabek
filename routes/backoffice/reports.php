<?php

use App\Http\Controllers\Backoffice\ReportsController;
use App\Http\Controllers\Backoffice\Reports\SalesReportController;
use App\Http\Controllers\Backoffice\Reports\CustomerReportController;
use App\Http\Controllers\Backoffice\Reports\PurchaseReportController;
use App\Http\Controllers\Backoffice\Reports\FinanceReportController;
use App\Http\Controllers\Backoffice\Reports\InventoryReportController;
use App\Http\Controllers\Backoffice\Reports\CustomReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Reports Routes (Tenant)
|--------------------------------------------------------------------------
*/

Route::prefix('reports')->as('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])
        ->middleware('permission:reports.sales.view')
        ->name('index');

    Route::get('sales', [SalesReportController::class, 'index'])
        ->middleware('permission:reports.sales.view')
        ->name('sales');

    Route::get('customers', [CustomerReportController::class, 'index'])
        ->middleware('permission:reports.customers.view')
        ->name('customers');

    Route::get('purchases', [PurchaseReportController::class, 'index'])
        ->middleware('permission:reports.purchases.view')
        ->name('purchases');

    Route::get('finance', [FinanceReportController::class, 'index'])
        ->middleware('permission:reports.finance.view')
        ->name('finance');

    Route::get('inventory', [InventoryReportController::class, 'index'])
        ->middleware('permission:reports.inventory.view')
        ->name('inventory');

    // Export endpoints
    Route::post('sales/export', [SalesReportController::class, 'export'])
        ->middleware(['permission:reports.sales.view', 'plan.limit:exports_per_month', 'throttle:report-export'])
        ->name('sales.export');

    Route::post('finance/export', [FinanceReportController::class, 'export'])
        ->middleware(['permission:reports.finance.view', 'plan.limit:exports_per_month', 'throttle:report-export'])
        ->name('finance.export');

    Route::post('inventory/export', [InventoryReportController::class, 'export'])
        ->middleware(['permission:reports.inventory.view', 'plan.limit:exports_per_month', 'throttle:report-export'])
        ->name('inventory.export');

    // Custom Reports (WYSIWYG editor + PDF/Word export)
    Route::prefix('custom')->as('custom.')->group(function () {
        Route::get('/', [CustomReportController::class, 'index'])
            ->middleware('permission:reports.custom.view')
            ->name('index');
        Route::get('create', [CustomReportController::class, 'create'])
            ->middleware('permission:reports.custom.create')
            ->name('create');
        Route::post('/', [CustomReportController::class, 'store'])
            ->middleware('permission:reports.custom.create')
            ->name('store');
        Route::get('{customReport}', [CustomReportController::class, 'show'])
            ->middleware('permission:reports.custom.view')
            ->name('show');
        Route::get('{customReport}/edit', [CustomReportController::class, 'edit'])
            ->middleware('permission:reports.custom.edit')
            ->name('edit');
        Route::put('{customReport}', [CustomReportController::class, 'update'])
            ->middleware('permission:reports.custom.edit')
            ->name('update');
        Route::delete('{customReport}', [CustomReportController::class, 'destroy'])
            ->middleware('permission:reports.custom.delete')
            ->name('destroy');
        Route::get('{customReport}/export-pdf', [CustomReportController::class, 'exportPdf'])
            ->middleware('permission:reports.custom.export')
            ->name('export-pdf');
        Route::get('{customReport}/export-word', [CustomReportController::class, 'exportWord'])
            ->middleware('permission:reports.custom.export')
            ->name('export-word');
    });
});
