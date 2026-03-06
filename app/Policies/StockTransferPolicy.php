<?php

namespace App\Policies;

use App\Models\Inventory\StockTransfer;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.stock_movements.view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $user->can('inventory.stock_movements.view')
            && $transfer->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.stock_movements.create');
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        return $user->can('inventory.stock_movements.edit')
            && $transfer->tenant_id === TenantContext::id();
    }

    public function delete(User $user, StockTransfer $transfer): bool
    {
        return $user->can('inventory.stock_movements.delete')
            && $transfer->tenant_id === TenantContext::id();
    }
}
