<?php

namespace App\Notifications;

use App\Models\Pro\InvoiceReminder;
use App\Models\Sales\Invoice;
use App\Models\Tenancy\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Tenant $tenant,
        public readonly string $type = 'after_due',
        public readonly ?string $pdfPath = null,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $tenantName = $this->tenant->name ?? 'notre entreprise';
        $currency   = $this->tenant->default_currency ?? 'MAD';
        $dueDate    = $this->invoice->due_date?->format('d/m/Y') ?? 'Non définie';

        $mail = (new MailMessage);

        // Different subject and message based on type
        switch ($this->type) {
            case 'before_due':
                $daysUntilDue = now()->diffInDays($this->invoice->due_date, false);
                $mail->subject('Rappel : Facture ' . $this->invoice->number . ' à échéance prochaine — ' . $tenantName)
                    ->greeting('Bonjour,')
                    ->line('Nous vous rappelons que la facture n° ' . $this->invoice->number . ' arrive à échéance dans ' . abs($daysUntilDue) . ' jour(s).')
                    ->line('Montant dû : ' . number_format($this->invoice->amount_due, 2, ',', ' ') . ' ' . $currency)
                    ->line('Date d\'échéance : ' . $dueDate)
                    ->line('Merci de bien vouloir procéder au règlement avant cette date.');
                break;

            case 'on_due':
                $mail->subject('Rappel : Facture ' . $this->invoice->number . ' à échéance aujourd\'hui — ' . $tenantName)
                    ->greeting('Bonjour,')
                    ->line('Nous vous rappelons que la facture n° ' . $this->invoice->number . ' arrive à échéance aujourd\'hui.')
                    ->line('Montant dû : ' . number_format($this->invoice->amount_due, 2, ',', ' ') . ' ' . $currency)
                    ->line('Date d\'échéance : ' . $dueDate)
                    ->line('Merci de bien vouloir procéder au règlement dans les meilleurs délais.');
                break;

            case 'after_due':
            default:
                $daysOverdue = $this->invoice->due_date ? now()->diffInDays($this->invoice->due_date, false) : 0;
                $daysOverdue = abs($daysOverdue);
                $mail->subject('Rappel : Facture ' . $this->invoice->number . ' en retard de paiement — ' . $tenantName)
                    ->greeting('Bonjour,')
                    ->line('La facture n° ' . $this->invoice->number . ' est en retard de ' . $daysOverdue . ' jour(s).')
                    ->line('Montant dû : ' . number_format($this->invoice->amount_due, 2, ',', ' ') . ' ' . $currency)
                    ->line('Date d\'échéance : ' . $dueDate)
                    ->line('Nous vous prions de bien vouloir régulariser cette situation dans les plus brefs délais.');
                break;
        }

        // Attach PDF if provided
        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => 'facture-' . $this->invoice->number . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail->line('Merci pour votre confiance.')
            ->salutation('Cordialement, ' . $tenantName);
    }

    public function toArray(mixed $notifiable): array
    {
        $typeLabels = [
            'before_due' => 'Rappel avant échéance',
            'on_due'     => 'Rappel jour d\'échéance',
            'after_due'  => 'Rappel après échéance',
        ];

        return [
            'title'          => $typeLabels[$this->type] ?? 'Rappel de facture',
            'message'        => 'Rappel pour la facture ' . $this->invoice->number . ' - Montant dû : ' . number_format($this->invoice->amount_due, 2, ',', ' ') . ' ' . ($this->tenant->default_currency ?? 'MAD'),
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'amount_due'     => $this->invoice->amount_due,
            'due_date'       => $this->invoice->due_date?->toDateString(),
            'type'           => $this->type,
        ];
    }
}
