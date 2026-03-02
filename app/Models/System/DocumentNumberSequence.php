<?php

namespace App\Models\System;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    use HasUuids, BelongsToTenant;

    protected $fillable = [
        'document_type',
        'prefix',
        'current_number',
        'increment_by',
        'suffix',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'increment_by' => 'integer',
    ];
}
