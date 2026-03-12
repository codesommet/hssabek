<?php

namespace App\Notifications;

use App\Models\Sales\Invoice;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
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
        $dueDate    = $this->invoice->due_date?->format('d/m/Y') ?? 'Non définie';

        $mail = (new MailMessage)
            ->subject('Facture ' . $this->invoice->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint votre facture n° ' . $this->invoice->number . '.')
            ->line('Montant total : ' . number_format($this->invoice->total, 2, ',', ' ') . ' ' . $currency)
            ->line('Date d\'échéance : ' . $dueDate);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => 'facture-' . $this->invoice->number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail->line('Merci pour votre confiance.')
            ->salutation('Cordialement');
    }
}
