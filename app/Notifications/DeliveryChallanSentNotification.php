<?php

namespace App\Notifications;

use App\Models\Sales\DeliveryChallan;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryChallanSentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly DeliveryChallan $deliveryChallan,
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

        return (new MailMessage)
            ->subject('Bon de livraison ' . $this->deliveryChallan->number . ' — ' . $tenantName)
            ->greeting('Bonjour,')
            ->line('Veuillez trouver ci-joint votre bon de livraison n° ' . $this->deliveryChallan->number . '.')
            ->line('Date : ' . $this->deliveryChallan->date?->format('d/m/Y'))
            ->attach($this->pdfPath, [
                'as'   => 'bon-livraison-' . $this->deliveryChallan->number . '.pdf',
                'mime' => 'application/pdf',
            ])
            ->line('Merci pour votre confiance.')
            ->salutation('Cordialement');
    }
}
