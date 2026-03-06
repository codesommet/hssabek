<?php

namespace App\Policies;

use App\Models\Finance\FinanceCategory;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class FinanceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('finance.categories.create');
    }

    public function update(User $user, FinanceCategory $category): bool
    {
        return $user->can('finance.categories.edit')
            && $category->tenant_id === TenantContext::id();
    }

    public function delete(User $user, FinanceCategory $category): bool
    {
        return $user->can('finance.categories.delete')
            && $category->tenant_id === TenantContext::id();
    }
}
