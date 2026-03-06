<?php

namespace App\Http\Requests\Inventory\Store;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'    => [
                'required', 'uuid',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'warehouse_id'  => [
                'required', 'uuid',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'movement_type' => 'required|in:stock_in,stock_out,adjustment_in,adjustment_out',
            'quantity'      => 'required|numeric|min:0.001',
            'note'          => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'    => 'Le produit est obligatoire.',
            'product_id.exists'      => 'Le produit sélectionné est invalide.',
            'warehouse_id.required'  => 'L\'entrepôt est obligatoire.',
            'warehouse_id.exists'    => 'L\'entrepôt sélectionné est invalide.',
            'movement_type.required' => 'Le type de mouvement est obligatoire.',
            'movement_type.in'       => 'Le type de mouvement est invalide.',
            'quantity.required'      => 'La quantité est obligatoire.',
            'quantity.numeric'       => 'La quantité doit être un nombre.',
            'quantity.min'           => 'La quantité doit être supérieure à zéro.',
            'note.max'              => 'La note ne doit pas dépasser 1000 caractères.',
        ];
    }
}
