<?php

namespace App\Models\Templates;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * TenantTemplate - Links purchased/premium templates to tenants.
 * Note: Free templates (is_free=true in template_catalog) are accessible
 * to all tenants without needing a record here.
 */
class TenantTemplate extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'template_id',
        'status',
        'activated_at',
        'deactivated_at',
        'expires_at',
        'activated_by',
        'source',
        'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function templateCatalog(): BelongsTo
    {
        return $this->belongsTo(TemplateCatalog::class, 'template_id');
    }

    public function activatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function preference(): HasOne
    {
        return $this->hasOne(TenantTemplatePreference::class);
    }

    /**
     * Check if template is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
