<?php

namespace App\Notifications;

use App\Models\Purchases\DebitNote;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DebitNoteSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly DebitNote $debitNote,
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
            ->subject('Note de débit ' . $this->debitNote->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint la note de débit n° ' . $this->debitNote->number . '.')
            ->line('Montant total : ' . number_format($this->debitNote->total, 2, ',', ' ') . ' ' . $currency)
            ->line('Date : ' . $this->debitNote->date?->format('d/m/Y'))
            ->attach($this->pdfPath, [
                'as'   => 'note-debit-' . $this->debitNote->number . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('Merci pour votre collaboration.')
            ->salutation('Cordialement');
    }
}
