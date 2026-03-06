<?php

namespace App\Http\Requests\Inventory\Store;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => [
                'required', 'uuid',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'to_warehouse_id'   => [
                'required', 'uuid', 'different:from_warehouse_id',
                Rule::exists('warehouses', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'notes'             => 'nullable|string|max:1000',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => [
                'required', 'uuid',
                Rule::exists('products', 'id')->where('tenant_id', TenantContext::id()),
            ],
            'items.*.quantity'  => 'required|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.required'   => 'L\'entrepôt source est obligatoire.',
            'from_warehouse_id.exists'     => 'L\'entrepôt source est invalide.',
            'to_warehouse_id.required'     => 'L\'entrepôt de destination est obligatoire.',
            'to_warehouse_id.exists'       => 'L\'entrepôt de destination est invalide.',
            'to_warehouse_id.different'    => 'L\'entrepôt de destination doit être différent de la source.',
            'items.required'               => 'Vous devez ajouter au moins un produit.',
            'items.min'                    => 'Vous devez ajouter au moins un produit.',
            'items.*.product_id.required'  => 'Le produit est obligatoire pour chaque ligne.',
            'items.*.product_id.exists'    => 'Un produit sélectionné est invalide.',
            'items.*.quantity.required'    => 'La quantité est obligatoire pour chaque ligne.',
            'items.*.quantity.min'         => 'La quantité doit être supérieure à zéro.',
        ];
    }
}
