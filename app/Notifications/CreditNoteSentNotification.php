<?php

namespace App\Notifications;

use App\Models\Sales\CreditNote;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreditNoteSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly CreditNote $creditNote,
        public readonly Tenant $tenant,
        public readonly string $pdfPath,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $tenantName = $this->tenant->name ?? 'notre entreprise';
        $currency   = $this->tenant->default_currency ?? 'MAD';

        return (new MailMessage)
            ->subject('Avoir ' . $this->creditNote->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint votre avoir n° ' . $this->creditNote->number . '.')
            ->line('Montant total : ' . number_format($this->creditNote->total, 2, ',', ' ') . ' ' . $currency)
            ->line('Date : ' . $this->creditNote->date?->format('d/m/Y'))
            ->attach($this->pdfPath, [
                'as'   => 'avoir-' . $this->creditNote->number . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('Merci pour votre confiance.')
            ->salutation('Cordialement');
    }
}
