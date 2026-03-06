<?php

namespace App\Models\Purchases;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'product_id',
        'label',
        'description',
        'quantity',
        'unit_cost',
        'discount_type',
        'discount_value',
        'tax_rate',
        'tax_group_id',
        'line_subtotal',
        'line_tax',
        'line_total',
        'received_quantity',
        'position',
    ];

    protected $casts = [
        'quantity'          => 'decimal:3',
        'unit_cost'         => 'decimal:2',
        'discount_value'    => 'decimal:4',
        'tax_rate'          => 'decimal:4',
        'line_subtotal'     => 'decimal:2',
        'line_tax'          => 'decimal:2',
        'line_total'        => 'decimal:2',
        'received_quantity' => 'decimal:3',
        'position'          => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Catalog\Product::class);
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Catalog\TaxGroup::class);
    }
}
