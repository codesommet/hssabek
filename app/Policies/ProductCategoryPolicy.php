<?php

namespace App\Policies;

use App\Models\Catalog\ProductCategory;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.products.create');
    }

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.products.edit')
            && $category->tenant_id === TenantContext::id();
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->can('inventory.products.delete')
            && $category->tenant_id === TenantContext::id();
    }
}
