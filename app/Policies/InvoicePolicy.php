<?php

namespace App\Policies;

use App\Models\Sales\Invoice;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('sales.invoices.view')
            && $invoice->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('sales.invoices.edit')
            && $invoice->tenant_id === TenantContext::id();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('sales.invoices.delete')
            && $invoice->tenant_id === TenantContext::id();
    }
}
