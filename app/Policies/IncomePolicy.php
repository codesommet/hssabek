<?php

namespace App\Policies;

use App\Models\Finance\Income;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class IncomePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.incomes.view');
    }

    public function view(User $user, Income $income): bool
    {
        return $user->can('finance.incomes.view')
            && $income->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('finance.incomes.create');
    }

    public function update(User $user, Income $income): bool
    {
        return $user->can('finance.incomes.edit')
            && $income->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Income $income): bool
    {
        return $user->can('finance.incomes.delete')
            && $income->tenant_id === TenantContext::id();
    }
}
