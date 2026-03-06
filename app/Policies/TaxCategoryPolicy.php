<?php

namespace App\Policies;

use App\Models\Catalog\TaxCategory;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class TaxCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.products.create');
    }

    public function update(User $user, TaxCategory $taxCategory): bool
    {
        return $user->can('inventory.products.edit')
            && $taxCategory->tenant_id === TenantContext::id();
    }

    public function delete(User $user, TaxCategory $taxCategory): bool
    {
        return $user->can('inventory.products.delete')
            && $taxCategory->tenant_id === TenantContext::id();
    }
}
