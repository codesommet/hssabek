<?php

namespace App\Policies;

use App\Models\Sales\Quote;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        return $user->can('sales.quotes.view')
            && $quote->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.quotes.create');
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->can('sales.quotes.edit')
            && $quote->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $user->can('sales.quotes.delete')
            && $quote->tenant_id === TenantContext::id();
    }
}
