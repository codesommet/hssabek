<?php

namespace App\Policies;

use App\Models\Inventory\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.stock_movements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.stock_movements.create');
    }
}
