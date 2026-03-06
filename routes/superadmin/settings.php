<?php

use App\Http\Controllers\SuperAdmin\DeleteAccountRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin — Settings Routes
|--------------------------------------------------------------------------
*/

// Delete Account Requests
Route::prefix('delete-requests')->as('delete-requests.')->group(function () {
    Route::get('/', [DeleteAccountRequestController::class, 'index'])->name('index');
    Route::post('/{deleteRequest}/confirm', [DeleteAccountRequestController::class, 'confirm'])->name('confirm');
    Route::post('/{deleteRequest}/cancel', [DeleteAccountRequestController::class, 'cancel'])->name('cancel');
});
