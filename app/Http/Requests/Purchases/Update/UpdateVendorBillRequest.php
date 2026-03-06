<?php

namespace App\Http\Requests\Purchases\Update;

use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'supplier_id'       => ['required', 'uuid', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'reference_number'  => 'nullable|string|max:100',
            'issue_date'        => 'required|date',
            'due_date'          => 'nullable|date|after_or_equal:issue_date',
            'subtotal'          => 'required|numeric|min:0',
            'tax_total'         => 'nullable|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'notes'             => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required'    => 'Le fournisseur est obligatoire.',
            'supplier_id.exists'      => 'Le fournisseur sélectionné est invalide.',
            'issue_date.required'     => 'La date d\'émission est obligatoire.',
            'due_date.after_or_equal' => 'La date d\'échéance doit être postérieure ou égale à la date d\'émission.',
            'subtotal.required'       => 'Le sous-total est obligatoire.',
            'total.required'          => 'Le total est obligatoire.',
        ];
    }
}
