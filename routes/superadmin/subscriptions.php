<?php

use App\Http\Controllers\SuperAdmin\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin — Subscription Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('subscriptions')->as('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
});
