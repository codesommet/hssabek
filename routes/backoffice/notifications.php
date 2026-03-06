<?php

use App\Http\Controllers\Backoffice\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Notification Routes
|--------------------------------------------------------------------------
*/

Route::prefix('notifications')->as('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
    Route::post('/delete-all', [NotificationController::class, 'destroyAll'])->name('destroyAll');
    Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
});
