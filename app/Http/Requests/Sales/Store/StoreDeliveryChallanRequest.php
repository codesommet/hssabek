<?php

namespace App\Http\Requests\Sales\Store;

use App\Http\Requests\TenantFormRequest;

class StoreDeliveryChallanRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id'  => ['required', 'uuid', $this->tenantExists('customers')],
            'quote_id'     => ['nullable', 'uuid', $this->tenantExists('quotes')],
            'invoice_id'   => ['nullable', 'uuid', $this->tenantExists('invoices')],
            'challan_date' => ['required', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:challan_date'],
            'enable_tax'   => ['nullable', 'boolean'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:2000'],
            'terms'        => ['nullable', 'string', 'max:2000'],
            'delivered_at' => ['nullable', 'date'],

            'items'                  => ['nullable', 'array'],
            'items.*.product_id'     => ['nullable', 'uuid', $this->tenantExists('products')],
            'items.*.label'          => ['nullable', 'string', 'max:255'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'     => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'  => 'Le client est obligatoire.',
            'customer_id.exists'    => 'Le client sélectionné est invalide.',
            'quote_id.exists'       => 'Le devis sélectionné est invalide.',
            'invoice_id.exists'     => 'La facture sélectionnée est invalide.',
            'challan_date.required' => 'La date du bon de livraison est obligatoire.',
            'due_date.after_or_equal' => 'La date d\'échéance doit être postérieure ou égale à la date du bon.',
            'notes.max'             => 'Les notes ne doivent pas dépasser 2000 caractères.',
            'items.*.quantity.required' => 'La quantité est obligatoire pour chaque article.',
            'items.*.quantity.min'      => 'La quantité doit être supérieure à 0.',
        ];
    }
}
