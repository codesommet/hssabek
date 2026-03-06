<?php

namespace App\Http\Requests\Pro\Update;

use App\Http\Requests\TenantFormRequest;

class UpdateRecurringInvoiceRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'uuid', $this->tenantExists('customers')],
            'interval' => ['sometimes', 'in:weekly,monthly,quarterly,yearly'],
            'next_run_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:next_run_at'],
            'is_active' => ['sometimes', 'boolean'],
            'payload' => ['sometimes', 'nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists'         => 'Le client sélectionné est invalide.',
            'template_invoice_id.exists' => 'La facture modèle sélectionnée est invalide.',
            'interval.in'               => 'L\'intervalle doit être : semaine, mois ou année.',
            'end_at.after_or_equal'      => 'La date de fin doit être postérieure ou égale à la prochaine exécution.',
        ];
    }
}
