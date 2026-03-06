<?php

namespace App\Services\Finance;

use App\Models\Finance\ExchangeRate;
use App\Services\Tenancy\TenantContext;

class CurrencyService
{
    public function convert(float $amount, string $fromCode, string $toCode): float
    {
        if ($fromCode === $toCode) {
            return round($amount, 2);
        }

        $tenantId = TenantContext::id();

        $rate = ExchangeRate::where('tenant_id', $tenantId)
            ->where('base_currency', $fromCode)
            ->where('quote_currency', $toCode)
            ->latest('date')
            ->value('rate');

        if (! $rate) {
            throw new \RuntimeException(
                "Taux de change introuvable : {$fromCode} → {$toCode}"
            );
        }

        return round($amount * (float) $rate, 2);
    }
}
