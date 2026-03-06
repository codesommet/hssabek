<?php

namespace App\Policies;

use App\Models\Finance\BankAccount;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.bank_accounts.view');
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('finance.bank_accounts.view')
            && $bankAccount->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('finance.bank_accounts.create');
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('finance.bank_accounts.edit')
            && $bankAccount->tenant_id === TenantContext::id();
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('finance.bank_accounts.delete')
            && $bankAccount->tenant_id === TenantContext::id();
    }
}
