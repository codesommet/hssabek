<?php

namespace App\Policies;

use App\Models\Sales\Refund;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.refunds.view');
    }

    public function view(User $user, Refund $refund): bool
    {
        return $user->can('sales.refunds.view')
            && $refund->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.refunds.create');
    }

    public function update(User $user, Refund $refund): bool
    {
        return $user->can('sales.refunds.edit')
            && $refund->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Refund $refund): bool
    {
        return $user->can('sales.refunds.delete')
            && $refund->tenant_id === TenantContext::id();
    }
}
