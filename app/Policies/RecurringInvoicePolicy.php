<?php

namespace App\Policies;

use App\Models\Pro\RecurringInvoice;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class RecurringInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoices.view');
    }

    public function view(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('sales.invoices.view')
            && $recurringInvoice->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoices.create');
    }

    public function update(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('sales.invoices.edit')
            && $recurringInvoice->tenant_id === TenantContext::id();
    }

    public function delete(User $user, RecurringInvoice $recurringInvoice): bool
    {
        return $user->can('sales.invoices.delete')
            && $recurringInvoice->tenant_id === TenantContext::id();
    }
}
