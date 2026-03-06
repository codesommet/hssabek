<?php

namespace App\Http\Requests\Finance\Update;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('finance_categories')->where('tenant_id', TenantContext::id())
                    ->ignore($this->route('finance_category')),
            ],
            'type'      => 'required|in:expense,income',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique'   => 'Une catégorie avec ce nom existe déjà.',
            'type.required' => 'Le type de catégorie est obligatoire.',
            'type.in'       => 'Le type doit être "dépense" ou "revenu".',
        ];
    }
}
