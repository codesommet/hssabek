<?php

namespace App\Policies;

use App\Models\Pro\Branch;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.company.view') || $user->can('access.users.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->viewAny($user)
            && $branch->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('settings.company.edit') || $user->can('access.users.edit');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $this->create($user)
            && $branch->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $this->create($user)
            && $branch->tenant_id === TenantContext::id();
    }
}
