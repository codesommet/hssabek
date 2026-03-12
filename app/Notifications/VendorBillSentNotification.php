<?php

namespace App\Notifications;

use App\Models\Purchases\VendorBill;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorBillSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly VendorBill $vendorBill,
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
        $dueDate    = $this->vendorBill->due_date?->format('d/m/Y') ?? 'Non définie';

        $mail = (new MailMessage)
            ->subject('Facture fournisseur ' . $this->vendorBill->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint la facture fournisseur n° ' . $this->vendorBill->number . '.')
            ->line('Montant total : ' . number_format($this->vendorBill->total, 2, ',', ' ') . ' ' . $currency)
            ->line('Date d\'échéance : ' . $dueDate);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => 'facture-fournisseur-' . $this->vendorBill->number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail->line('Merci pour votre collaboration.')
            ->salutation('Cordialement');
    }
}
