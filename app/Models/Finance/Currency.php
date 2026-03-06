<?php

namespace App\Models\Finance;

use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = 'code';
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'precision',
    ];

    protected $casts = [
        'precision' => 'integer',
    ];

    /**
     * Scope to only currencies configured for the current tenant
     * (default currency + currencies with exchange rates).
     */
    public function scopeForTenant(Builder $query): Builder
    {
        $tenant = TenantContext::get();
        $defaultCurrency = $tenant->default_currency ?? 'MAD';

        $rates = ExchangeRate::where('tenant_id', $tenant->id)->get(['base_currency', 'quote_currency']);

        $tenantCurrencyCodes = $rates->pluck('quote_currency')
            ->merge($rates->pluck('base_currency'))
            ->push($defaultCurrency)
            ->unique()
            ->values();

        return $query->whereIn('code', $tenantCurrencyCodes)->orderBy('code');
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency', 'code');
    }
}
