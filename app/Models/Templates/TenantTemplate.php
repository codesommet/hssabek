<?php

namespace App\Models\Templates;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TenantTemplate extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'template_catalog_id',
        'name',
        'template_html',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function templateCatalog(): BelongsTo
    {
        return $this->belongsTo(TemplateCatalog::class);
    }

    public function preference(): HasOne
    {
        return $this->hasOne(TenantTemplatePreference::class);
    }
}
