<?php

namespace App\Notifications;

use App\Models\Sales\Quote;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Quote $quote,
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

        $mail = (new MailMessage)
            ->subject('Devis ' . $this->quote->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint votre devis n° ' . $this->quote->number . '.')
            ->line('Montant total : ' . number_format($this->quote->total, 2, ',', ' ') . ' ' . $currency);

        if ($this->quote->expiry_date) {
            $mail->line('Valide jusqu\'au : ' . $this->quote->expiry_date->format('d/m/Y'));
        }

        return $mail
            ->attach($this->pdfPath, [
                'as'   => 'devis-' . $this->quote->number . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('N\'hésitez pas à nous contacter pour toute question.')
            ->salutation('Cordialement');
    }
}
