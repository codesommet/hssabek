<?php

namespace App\Models\Catalog;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCategory extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
    ];

    public function taxGroups(): HasMany
    {
        return $this->hasMany(TaxGroup::class);
    }
}
