<?php

namespace App\Policies;

use App\Models\Inventory\Warehouse;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.warehouses.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.warehouses.view')
            && $warehouse->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.warehouses.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.warehouses.edit')
            && $warehouse->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.warehouses.delete')
            && $warehouse->tenant_id === TenantContext::id();
    }
}
