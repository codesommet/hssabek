<?php

namespace App\Policies;

use App\Models\Finance\Loan;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class LoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.loans.view');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $user->can('finance.loans.view')
            && $loan->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('finance.loans.create');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->can('finance.loans.edit')
            && $loan->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->can('finance.loans.delete')
            && $loan->tenant_id === TenantContext::id();
    }
}
