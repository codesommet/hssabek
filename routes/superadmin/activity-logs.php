<?php

use App\Http\Controllers\SuperAdmin\ActivityLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin — Activity Log Routes
|--------------------------------------------------------------------------
*/

Route::prefix('activity-logs')->as('activity-logs.')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
    Route::delete('/{activityLog}', [ActivityLogController::class, 'destroy'])->name('destroy');
    Route::post('/clear', [ActivityLogController::class, 'clear'])->name('clear');
});
