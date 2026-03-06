<?php

namespace App\Policies;

use App\Models\Purchases\SupplierPayment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class SupplierPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.supplier_payments.view');
    }

    public function view(User $user, SupplierPayment $supplierPayment): bool
    {
        return $user->can('purchases.supplier_payments.view')
            && $supplierPayment->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.supplier_payments.create');
    }

    public function update(User $user, SupplierPayment $supplierPayment): bool
    {
        return $user->can('purchases.supplier_payments.view')
            && $supplierPayment->tenant_id === TenantContext::id();
    }

    public function delete(User $user, SupplierPayment $supplierPayment): bool
    {
        return $user->can('purchases.supplier_payments.delete')
            && $supplierPayment->tenant_id === TenantContext::id();
    }
}
