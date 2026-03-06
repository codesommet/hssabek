<?php

namespace App\Policies;

use App\Models\Purchases\VendorBill;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class VendorBillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.vendor-bills.view');
    }

    public function view(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('purchases.vendor-bills.view')
            && $vendorBill->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.vendor-bills.create');
    }

    public function update(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('purchases.vendor-bills.edit')
            && $vendorBill->tenant_id === TenantContext::id();
    }

    public function delete(User $user, VendorBill $vendorBill): bool
    {
        return $user->can('purchases.vendor-bills.delete')
            && $vendorBill->tenant_id === TenantContext::id();
    }
}
