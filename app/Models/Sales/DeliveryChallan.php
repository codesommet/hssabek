<?php

namespace App\Models\Sales;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryChallan extends Model
{
    use HasUuids, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'invoice_id',
        'challan_number',
        'challan_date',
        'delivered_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'challan_date' => 'date',
        'delivered_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryChallanItem::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(DeliveryChallanCharge::class);
    }
}