<?php

use App\Http\Controllers\Backoffice\CRM\CustomerController;
use App\Http\Controllers\Backoffice\CRM\CustomerAddressController;
use App\Http\Controllers\Backoffice\CRM\CustomerContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice CRM Routes (Tenant)
|--------------------------------------------------------------------------
*/

Route::prefix('crm')->as('crm.')->group(function () {

    // Customers
    Route::prefix('customers')->as('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('permission:crm.customers.view')
            ->name('index');

        Route::get('/create', [CustomerController::class, 'create'])
            ->middleware(['permission:crm.customers.create', 'plan.limit:customers'])
            ->name('create');

        Route::post('/', [CustomerController::class, 'store'])
            ->middleware(['permission:crm.customers.create', 'plan.limit:customers'])
            ->name('store');

        Route::get('/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:crm.customers.view')
            ->name('show');

        Route::get('/{customer}/edit', [CustomerController::class, 'edit'])
            ->middleware('permission:crm.customers.edit')
            ->name('edit');

        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('permission:crm.customers.edit')
            ->name('update');

        Route::delete('/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:crm.customers.delete')
            ->name('destroy');

        // Addresses (nested under customer)
        Route::post('/{customer}/addresses', [CustomerAddressController::class, 'store'])
            ->middleware('permission:crm.customers.edit')
            ->name('addresses.store');

        // Contacts (nested under customer)
        Route::post('/{customer}/contacts', [CustomerContactController::class, 'store'])
            ->middleware('permission:crm.customers.edit')
            ->name('contacts.store');
    });

    // Addresses (standalone for update/delete)
    Route::put('/addresses/{address}', [CustomerAddressController::class, 'update'])
        ->middleware('permission:crm.customers.edit')
        ->name('addresses.update');

    Route::delete('/addresses/{address}', [CustomerAddressController::class, 'destroy'])
        ->middleware('permission:crm.customers.delete')
        ->name('addresses.destroy');

    // Contacts (standalone for update/delete)
    Route::put('/contacts/{contact}', [CustomerContactController::class, 'update'])
        ->middleware('permission:crm.customers.edit')
        ->name('contacts.update');

    Route::delete('/contacts/{contact}', [CustomerContactController::class, 'destroy'])
        ->middleware('permission:crm.customers.delete')
        ->name('contacts.destroy');
});
