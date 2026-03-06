<?php

namespace App\Policies;

use App\Models\Pro\InvoiceReminder;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class InvoiceReminderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoices.view');
    }

    public function view(User $user, InvoiceReminder $invoiceReminder): bool
    {
        return $user->can('sales.invoices.view')
            && $invoiceReminder->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoices.create');
    }

    public function update(User $user, InvoiceReminder $invoiceReminder): bool
    {
        return $user->can('sales.invoices.edit')
            && $invoiceReminder->tenant_id === TenantContext::id();
    }

    public function delete(User $user, InvoiceReminder $invoiceReminder): bool
    {
        return $user->can('sales.invoices.delete')
            && $invoiceReminder->tenant_id === TenantContext::id();
    }
}
