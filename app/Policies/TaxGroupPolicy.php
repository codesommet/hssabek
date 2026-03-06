<?php

namespace App\Policies;

use App\Models\Catalog\TaxGroup;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class TaxGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.products.create');
    }

    public function update(User $user, TaxGroup $taxGroup): bool
    {
        return $user->can('inventory.products.edit')
            && $taxGroup->tenant_id === TenantContext::id();
    }

    public function delete(User $user, TaxGroup $taxGroup): bool
    {
        return $user->can('inventory.products.delete')
            && $taxGroup->tenant_id === TenantContext::id();
    }
}
