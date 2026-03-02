<?php

namespace App\Models\Tenancy;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'name',
        'signature_image',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];
}
