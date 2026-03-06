<?php

namespace App\Traits;

use App\Services\Tenancy\TenantContext;

/**
 * Provides a currency accessor that reads from the tenant's default_currency.
 *
 * Use on models where the per-row `currency` column has been removed
 * in favour of the tenant-level setting.
 */
trait UsesTenantCurrency
{
    public function getCurrencyAttribute(): string
    {
        return TenantContext::get()?->default_currency ?? 'MAD';
    }
}
