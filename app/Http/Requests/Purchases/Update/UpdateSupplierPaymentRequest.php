<?php

namespace App\Http\Requests\Purchases\Update;

use App\Http\Requests\TenantFormRequest;

class UpdateSupplierPaymentRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'       => ['sometimes', 'uuid', $this->tenantExists('suppliers')],
            'vendor_bill_id'    => ['sometimes', 'nullable', 'uuid', $this->tenantExists('vendor_bills')],
            'bank_account_id'   => ['sometimes', 'nullable', 'uuid', $this->tenantExists('bank_accounts')],
            'amount'            => ['sometimes', 'numeric', 'gt:0'],
            'paid_at'           => ['sometimes', 'date'],
            'payment_mode'      => ['sometimes', 'in:cash,bank_transfer,card,cheque,other'],
            'reference'         => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes'             => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.exists'     => 'Le fournisseur sélectionné est invalide.',
            'vendor_bill_id.exists'  => 'La facture fournisseur sélectionnée est invalide.',
            'bank_account_id.exists' => 'Le compte bancaire sélectionné est invalide.',
            'amount.gt'             => 'Le montant doit être supérieur à zéro.',
            'payment_mode.in'       => 'Le mode de paiement est invalide.',
        ];
    }
}
