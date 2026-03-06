<?php

namespace App\Http\Requests\Purchases\Store;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'required|string|max:255',
            'email'              => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->where('tenant_id', TenantContext::id()),
            ],
            'phone'              => 'nullable|string|max:30',
            'tax_id'             => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('suppliers', 'tax_id')->where('tenant_id', TenantContext::id()),
            ],
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'status'             => 'required|in:active,inactive',
            'notes'              => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'Le nom du fournisseur est obligatoire.',
            'name.max'                   => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.email'                => 'L\'adresse e-mail n\'est pas valide.',
            'email.unique'               => 'Cette adresse e-mail est déjà utilisée par un autre fournisseur.',
            'tax_id.unique'              => 'Cet identifiant fiscal est déjà utilisé par un autre fournisseur.',
            'payment_terms_days.integer' => 'Le délai de paiement doit être un nombre entier.',
            'payment_terms_days.min'     => 'Le délai de paiement ne peut pas être négatif.',
            'status.required'            => 'Le statut est obligatoire.',
            'status.in'                  => 'Le statut doit être actif ou inactif.',
        ];
    }
}
