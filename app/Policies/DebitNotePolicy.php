<?php

namespace App\Policies;

use App\Models\Purchases\DebitNote;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class DebitNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchases.debit_notes.view');
    }

    public function view(User $user, DebitNote $debitNote): bool
    {
        return $user->can('purchases.debit_notes.view')
            && $debitNote->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('purchases.debit_notes.create');
    }

    public function update(User $user, DebitNote $debitNote): bool
    {
        return $user->can('purchases.debit_notes.edit')
            && $debitNote->tenant_id === TenantContext::id();
    }

    public function delete(User $user, DebitNote $debitNote): bool
    {
        return $user->can('purchases.debit_notes.delete')
            && $debitNote->tenant_id === TenantContext::id();
    }
}
