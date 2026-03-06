<?php

namespace App\Http\Requests\Pro\Store;

use App\Http\Requests\TenantFormRequest;

class StoreInvoiceReminderRequest extends TenantFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id'   => ['required', 'uuid', $this->tenantExists('invoices')],
            'type'         => ['required', 'in:before_due,on_due,after_due'],
            'channel'      => ['required', 'in:email,sms,whatsapp,in_app'],
            'scheduled_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required'   => 'La facture est obligatoire.',
            'invoice_id.exists'     => 'La facture sélectionnée est invalide.',
            'type.required'         => 'Le type de rappel est obligatoire.',
            'type.in'               => 'Le type de rappel est invalide.',
            'channel.required'      => 'Le canal de notification est obligatoire.',
            'channel.in'            => 'Le canal de notification est invalide.',
            'scheduled_at.required' => 'La date de planification est obligatoire.',
        ];
    }
}
