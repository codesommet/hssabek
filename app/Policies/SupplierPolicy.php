<?php

namespace App\Policies;

use App\Models\Purchases\Supplier;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.suppliers.view');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('purchases.suppliers.view')
            && $supplier->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.suppliers.create');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('purchases.suppliers.edit')
            && $supplier->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('purchases.suppliers.delete')
            && $supplier->tenant_id === TenantContext::id();
    }
}
