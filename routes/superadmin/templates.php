<?php

use App\Http\Controllers\SuperAdmin\TemplateAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin — Template Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('templates')->as('templates.')->group(function () {
    Route::get('/', [TemplateAssignmentController::class, 'index'])->name('index');
    Route::get('/{template}', [TemplateAssignmentController::class, 'show'])->name('show');
    Route::post('/{template}/assign', [TemplateAssignmentController::class, 'assign'])->name('assign');
    Route::post('/{template}/revoke', [TemplateAssignmentController::class, 'revoke'])->name('revoke');
    Route::post('/{template}/bulk-assign', [TemplateAssignmentController::class, 'bulkAssign'])->name('bulk-assign');
    Route::post('/{template}/toggle', [TemplateAssignmentController::class, 'toggleStatus'])->name('toggle');
});
