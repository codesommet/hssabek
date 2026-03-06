<?php

namespace App\Policies;

use App\Models\Purchases\PurchaseOrder;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.purchase-orders.view');
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchases.purchase-orders.view')
            && $purchaseOrder->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.purchase-orders.create');
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchases.purchase-orders.edit')
            && $purchaseOrder->tenant_id === TenantContext::id();
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('purchases.purchase-orders.delete')
            && $purchaseOrder->tenant_id === TenantContext::id();
    }
}
