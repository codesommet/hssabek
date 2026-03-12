<?php

namespace App\Notifications;

use App\Models\Purchases\PurchaseOrder;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PurchaseOrderSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PurchaseOrder $purchaseOrder,
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
            ->subject('Bon de commande ' . $this->purchaseOrder->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint le bon de commande n° ' . $this->purchaseOrder->number . '.')
            ->line('Montant total : ' . number_format($this->purchaseOrder->total, 2, ',', ' ') . ' ' . $currency)
            ->line('Date : ' . $this->purchaseOrder->date?->format('d/m/Y'))
            ->attach($this->pdfPath, [
                'as'   => 'bon-commande-' . $this->purchaseOrder->number . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('Merci pour votre collaboration.')
            ->salutation('Cordialement');
    }
}
