<?php

namespace App\Http\Requests\Sales\Update;

use App\Http\Requests\TenantFormRequest;

class UpdateRefundRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'numeric', 'gt:0'],
            'status'      => ['required', 'in:pending,processed,failed'],
            'refunded_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'     => 'Le montant est obligatoire.',
            'amount.gt'           => 'Le montant doit être supérieur à zéro.',
            'status.required'     => 'Le statut est obligatoire.',
            'status.in'           => 'Le statut est invalide.',
            'refunded_at.required' => 'La date du remboursement est obligatoire.',
        ];
    }
}
