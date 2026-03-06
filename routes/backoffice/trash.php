<?php

use App\Http\Controllers\Backoffice\TrashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Trash (Corbeille) Routes
|--------------------------------------------------------------------------
*/

Route::prefix('trash')->as('trash.')->group(function () {
    Route::get('/', [TrashController::class, 'index'])
        ->name('index');

    Route::delete('/{type}/empty', [TrashController::class, 'emptyType'])
        ->name('empty');

    Route::post('/{type}/{id}/restore', [TrashController::class, 'restore'])
        ->name('restore');

    Route::delete('/{type}/{id}', [TrashController::class, 'forceDelete'])
        ->name('force-delete');
});
