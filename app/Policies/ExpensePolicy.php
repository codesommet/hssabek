<?php

namespace App\Policies;

use App\Models\Finance\Expense;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->can('finance.expenses.view')
            && $expense->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('finance.expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->can('finance.expenses.edit')
            && $expense->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->can('finance.expenses.delete')
            && $expense->tenant_id === TenantContext::id();
    }
}
