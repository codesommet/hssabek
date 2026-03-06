<?php

namespace App\Policies;

use App\Models\Catalog\Unit;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.products.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('inventory.products.edit')
            && $unit->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('inventory.products.delete')
            && $unit->tenant_id === TenantContext::id();
    }
}
