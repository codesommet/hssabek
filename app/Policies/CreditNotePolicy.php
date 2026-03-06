<?php

namespace App\Policies;

use App\Models\Sales\CreditNote;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.credit_notes.view');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->can('sales.credit_notes.view')
            && $creditNote->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.credit_notes.create');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $user->can('sales.credit_notes.edit')
            && $creditNote->tenant_id === TenantContext::id();
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return $user->can('sales.credit_notes.delete')
            && $creditNote->tenant_id === TenantContext::id();
    }
}
