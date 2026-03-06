<?php

namespace App\Http\Requests\Inventory\Store;

use App\Http\Requests\TenantFormRequest;

class StoreProductStockRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'uuid', $this->tenantExists('products')],
            'warehouse_id'     => ['required', 'uuid', $this->tenantExists('warehouses')],
            'quantity'         => ['required', 'numeric', 'min:0'],
            'reorder_level'    => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'    => 'Le produit est obligatoire.',
            'product_id.exists'      => 'Le produit sélectionné est invalide.',
            'warehouse_id.required'  => 'L\'entrepôt est obligatoire.',
            'warehouse_id.exists'    => 'L\'entrepôt sélectionné est invalide.',
            'quantity.required'      => 'La quantité est obligatoire.',
            'quantity.numeric'       => 'La quantité doit être un nombre.',
            'quantity.min'           => 'La quantité ne peut pas être négative.',
        ];
    }
}
