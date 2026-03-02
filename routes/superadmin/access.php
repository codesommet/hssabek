<?php

use App\Http\Controllers\SuperAdmin\Access\RolesPermissionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Super Admin Access Control Routes
|--------------------------------------------------------------------------
|
| Full CRUD on Roles across all tenants + global.
| Full CRUD on Permissions (global catalog).
| Permission assignment for any role.
|
*/

Route::prefix('access')->as('access.')->group(function () {

    // Roles CRUD (all tenants + global)
    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])
        ->name('roles.index');

    Route::post('/roles', [RolesPermissionsController::class, 'storeRole'])
        ->name('roles.store');

    Route::put('/roles/{role}', [RolesPermissionsController::class, 'updateRole'])
        ->name('roles.update');

    Route::delete('/roles/{role}', [RolesPermissionsController::class, 'destroyRole'])
        ->name('roles.destroy');

    // Role permissions assignment
    Route::get('/roles/{role}/permissions', [RolesPermissionsController::class, 'permissions'])
        ->name('roles.permissions');

    Route::post('/roles/{role}/sync-permissions', [RolesPermissionsController::class, 'syncPermissions'])
        ->name('roles.sync-permissions');

    // Permissions CRUD (global catalog)
    Route::get('/permissions', [RolesPermissionsController::class, 'permissionsList'])
        ->name('permissions.index');

    Route::post('/permissions', [RolesPermissionsController::class, 'storePermission'])
        ->name('permissions.store');

    Route::put('/permissions/{permission}', [RolesPermissionsController::class, 'updatePermission'])
        ->name('permissions.update');

    Route::delete('/permissions/{permission}', [RolesPermissionsController::class, 'destroyPermission'])
        ->name('permissions.destroy');
});
