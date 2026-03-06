<?php

namespace App\Policies;

use App\Models\Sales\DeliveryChallan;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class DeliveryChallanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.delivery_challans.view');
    }

    public function view(User $user, DeliveryChallan $deliveryChallan): bool
    {
        return $user->can('sales.delivery_challans.view')
            && $deliveryChallan->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.delivery_challans.create');
    }

    public function update(User $user, DeliveryChallan $deliveryChallan): bool
    {
        return $user->can('sales.delivery_challans.edit')
            && $deliveryChallan->tenant_id === TenantContext::id();
    }

    public function delete(User $user, DeliveryChallan $deliveryChallan): bool
    {
        return $user->can('sales.delivery_challans.delete')
            && $deliveryChallan->tenant_id === TenantContext::id();
    }
}
