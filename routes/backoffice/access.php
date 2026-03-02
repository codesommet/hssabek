<?php

use App\Http\Controllers\Backoffice\Access\RolesPermissionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice Access Control Routes (Tenant)
|--------------------------------------------------------------------------
|
| Roles & Permissions management for tenant admins.
| Tenant admin can CRUD roles within their tenant.
| Tenant admin can assign global permissions to their roles.
| Tenant admin CANNOT CRUD permissions (read-only).
|
*/

Route::prefix('access')->as('access.')->group(function () {

    // Roles management — requires access.roles.* permissions
    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])
        ->middleware('permission:access.roles.view')
        ->name('roles.index');

    Route::post('/roles', [RolesPermissionsController::class, 'store'])
        ->middleware('permission:access.roles.create')
        ->name('roles.store');

    Route::put('/roles/{role}', [RolesPermissionsController::class, 'update'])
        ->middleware('permission:access.roles.edit')
        ->name('roles.update');

    Route::delete('/roles/{role}', [RolesPermissionsController::class, 'destroy'])
        ->middleware('permission:access.roles.delete')
        ->name('roles.destroy');

    // Role permissions assignment — requires access.permissions.edit
    Route::get('/roles/{role}/permissions', [RolesPermissionsController::class, 'permissions'])
        ->middleware('permission:access.permissions.view')
        ->name('roles.permissions');

    Route::post('/roles/{role}/sync-permissions', [RolesPermissionsController::class, 'syncPermissions'])
        ->middleware('permission:access.permissions.edit')
        ->name('roles.sync-permissions');

    // Permissions catalog (read-only for tenant admin)
    Route::get('/permissions', [RolesPermissionsController::class, 'permissionsList'])
        ->middleware('permission:access.permissions.view')
        ->name('permissions.index');
});
