<?php

namespace App\Policies;

use App\Models\Sales\Payment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.invoices.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('sales.invoices.view')
            && $payment->tenant_id === TenantContext::id();
    }

    public function create(User $user): bool
    {
        return $user->can('sales.invoices.create');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->can('sales.invoices.delete')
            && $payment->tenant_id === TenantContext::id();
    }
}
