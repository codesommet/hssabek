<?php

namespace App\Models\System;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    use HasUuids, BelongsToTenant;

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'key',
        'prefix',
        'next_number',
        'format',
        'reset_policy',
    ];

    protected $casts = [
        'next_number' => 'integer',
        'format' => 'array',
    ];
}
