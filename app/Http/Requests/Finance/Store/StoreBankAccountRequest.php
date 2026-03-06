<?php

namespace App\Http\Requests\Finance\Store;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_holder_name' => 'required|string|max:255',
            'account_number'     => [
                'required', 'string', 'max:50',
                Rule::unique('bank_accounts')->where('tenant_id', TenantContext::id()),
            ],
            'bank_name'          => 'required|string|max:255',
            'ifsc_code'          => 'nullable|string|max:50',
            'branch'             => 'nullable|string|max:255',
            'account_type'       => 'required|in:current,savings,business,other',

            'opening_balance'    => 'required|numeric|min:0',
            'notes'              => 'nullable|string|max:2000',
            'is_active'          => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'account_holder_name.required' => 'Le nom du titulaire est obligatoire.',
            'account_number.required'      => 'Le numéro de compte est obligatoire.',
            'account_number.unique'        => 'Ce numéro de compte existe déjà.',
            'bank_name.required'           => 'Le nom de la banque est obligatoire.',
            'account_type.required'        => 'Le type de compte est obligatoire.',
            'account_type.in'              => 'Le type de compte est invalide.',

            'opening_balance.required'     => 'Le solde d\'ouverture est obligatoire.',
            'opening_balance.numeric'      => 'Le solde d\'ouverture doit être un nombre.',
            'opening_balance.min'          => 'Le solde d\'ouverture ne peut pas être négatif.',
        ];
    }
}
