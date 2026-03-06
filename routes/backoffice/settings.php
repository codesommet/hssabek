<?php

use App\Http\Controllers\Backoffice\AccountSettingsController;
use App\Http\Controllers\Backoffice\Settings\AppearanceSettingsController;
use App\Http\Controllers\Backoffice\Settings\BarcodeSettingsController;
use App\Http\Controllers\Backoffice\Settings\CompanySettingsController;
use App\Http\Controllers\Backoffice\Settings\DeleteAccountController;
use App\Http\Controllers\Backoffice\Settings\CurrencySettingsController;
use App\Http\Controllers\Backoffice\Settings\EmailTemplateSettingsController;
use App\Http\Controllers\Backoffice\Settings\InvoiceSettingsController;
use App\Http\Controllers\Backoffice\Settings\InvoiceTemplateSettingsController;
use App\Http\Controllers\Backoffice\Settings\LocalizationSettingsController;
use App\Http\Controllers\Backoffice\Settings\NotificationSettingsController;
use App\Http\Controllers\Backoffice\Settings\PlansBillingsController;
use App\Http\Controllers\Backoffice\Settings\PaymentMethodController;
use App\Http\Controllers\Backoffice\Settings\SecuritySettingsController;
use App\Http\Controllers\Backoffice\Settings\SignatureSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Settings Routes
|--------------------------------------------------------------------------
*/

// Account Settings
Route::prefix('account/settings')->as('account.settings.')->group(function () {
    Route::get('/', [AccountSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [AccountSettingsController::class, 'update'])->name('update');
    Route::put('/password', [AccountSettingsController::class, 'updatePassword'])->name('password');
    Route::put('/avatar', [AccountSettingsController::class, 'updateAvatar'])->name('avatar');
    Route::delete('/avatar', [AccountSettingsController::class, 'destroyAvatar'])->name('avatar.destroy');
});

// Company Settings
Route::prefix('settings/company')->as('settings.company.')->group(function () {
    Route::get('/', [CompanySettingsController::class, 'edit'])->name('edit');
    Route::put('/', [CompanySettingsController::class, 'update'])->name('update');
});

// Invoice Settings
Route::prefix('settings/invoice')->as('settings.invoice.')->group(function () {
    Route::get('/', [InvoiceSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [InvoiceSettingsController::class, 'update'])->name('update');
});

// Localization Settings
Route::prefix('settings/locale')->as('settings.locale.')->group(function () {
    Route::get('/', [LocalizationSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [LocalizationSettingsController::class, 'update'])->name('update');
});

// Signatures Settings
Route::prefix('settings/signatures')->as('settings.signatures.')->group(function () {
    Route::get('/', [SignatureSettingsController::class, 'index'])->name('index');
    Route::post('/', [SignatureSettingsController::class, 'store'])->name('store');
    Route::put('/{signature}', [SignatureSettingsController::class, 'update'])->name('update');
    Route::put('/{signature}/toggle', [SignatureSettingsController::class, 'toggleStatus'])->name('toggle');
    Route::delete('/{signature}', [SignatureSettingsController::class, 'destroy'])->name('destroy');
});

// Invoice Templates Settings
Route::prefix('settings/invoice-templates')->as('settings.invoice-templates.')->group(function () {
    Route::get('/', [InvoiceTemplateSettingsController::class, 'index'])->name('index');
    Route::post('/{template}/activate', [InvoiceTemplateSettingsController::class, 'activate'])->name('activate');
    Route::get('/{template}/preview', [InvoiceTemplateSettingsController::class, 'preview'])->name('preview');
    Route::post('/{templateId}/purchase', [InvoiceTemplateSettingsController::class, 'purchase'])->name('purchase');
});

// Currencies / Exchange Rates Settings
Route::prefix('settings/currencies')->as('settings.currencies.')->group(function () {
    Route::get('/', [CurrencySettingsController::class, 'index'])->name('index');
    Route::post('/', [CurrencySettingsController::class, 'store'])->name('store');
    Route::post('/exchange-rate', [CurrencySettingsController::class, 'storeExchangeRate'])->name('exchange-rate.store');
    Route::put('/{exchangeRate}', [CurrencySettingsController::class, 'update'])->name('update');
    Route::delete('/{exchangeRate}', [CurrencySettingsController::class, 'destroy'])->name('destroy');
    Route::post('/{currency}/set-default', [CurrencySettingsController::class, 'setDefault'])->name('set-default');
});

// Tax Rates Settings — REDIRECTED to Catalog module (canonical location)
// All tax CRUD is handled at bo.catalog.tax-rates / bo.catalog.tax-categories / bo.catalog.tax-groups
Route::get('settings/tax-rates', fn () => redirect()->route('bo.catalog.tax-rates.index'))
    ->name('settings.tax-rates.index');

// Barcode Settings
Route::prefix('settings/barcode')->as('settings.barcode.')->group(function () {
    Route::get('/', [BarcodeSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [BarcodeSettingsController::class, 'update'])->name('update');
});

// Plans & Billings Settings
Route::prefix('settings/plans-billings')->as('settings.plans-billings.')->group(function () {
    Route::get('/', [PlansBillingsController::class, 'index'])->name('index');
});

// Notification Settings
Route::prefix('settings/notifications')->as('settings.notifications.')->group(function () {
    Route::get('/', [NotificationSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [NotificationSettingsController::class, 'update'])->name('update');
});

// Email Templates Settings
Route::prefix('settings/email-templates')->as('settings.email-templates.')->group(function () {
    Route::get('/', [EmailTemplateSettingsController::class, 'index'])->name('index');
    Route::post('/', [EmailTemplateSettingsController::class, 'store'])->name('store');
    Route::put('/{template}', [EmailTemplateSettingsController::class, 'update'])->name('update');
    Route::put('/{template}/toggle', [EmailTemplateSettingsController::class, 'toggleStatus'])->name('toggle');
    Route::delete('/{template}', [EmailTemplateSettingsController::class, 'destroy'])->name('destroy');
});

// Appearance Settings
Route::prefix('settings/appearance')->as('settings.appearance.')->group(function () {
    Route::get('/', [AppearanceSettingsController::class, 'edit'])->name('edit');
    Route::put('/', [AppearanceSettingsController::class, 'update'])->name('update');
});

// Payment Methods Settings
Route::prefix('settings/payment-methods')->as('settings.payment-methods.')->group(function () {
    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
    Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('update');
    Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
});

// Security Settings
Route::prefix('settings/security')->as('settings.security.')->group(function () {
    Route::get('/', [SecuritySettingsController::class, 'index'])->name('index');
    Route::delete('/sessions/{sessionId}', [SecuritySettingsController::class, 'revokeSession'])->name('revoke-session');
    Route::post('/deactivate', [SecuritySettingsController::class, 'deactivate'])->name('deactivate');
});

// Delete Account Request
Route::post('settings/delete-account', [DeleteAccountController::class, 'store'])
    ->name('settings.delete-account.store');
