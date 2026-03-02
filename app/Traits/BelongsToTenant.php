<?php

namespace App\Traits;

use App\Models\Tenancy\Tenant;
use App\Scopes\TenantScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait for models that belong to a tenant.
 *
 * Automatically:
 * - Boots the TenantScope global scope
 * - Fills tenant_id on creating if not already set
 * - Provides the tenant() relationship
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Register the global tenant scope
        static::addGlobalScope(new TenantScope());

        // Auto-fill tenant_id when creating a new model
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenantId = TenantContext::id();

                // Only fall back to auth user if TenantContext is not set,
                // and avoid calling auth()->user() during authentication
                // (it can trigger recursive User model resolution)
                if ($tenantId === null && auth()->hasUser()) {
                    $tenantId = auth()->user()?->tenant_id;
                }

                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    /**
     * Get the tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
