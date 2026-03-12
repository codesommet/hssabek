<?php

namespace App\Jobs;

use App\Models\Sales\CreditNote;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\CreditNoteSentNotification;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendCreditNoteEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $creditNoteId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $creditNote = CreditNote::with(['customer', 'items.product', 'items.unit', 'items.taxGroup', 'invoice'])->findOrFail($this->creditNoteId);
        $customer = $creditNote->customer;

        if (!$customer?->email) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => 'N/A',
                'subject'    => 'Avoir ' . $creditNote->number,
                'type'       => 'credit_note',
                'entity_id'  => $creditNote->id,
                'status'     => 'failed',
                'error'      => 'Aucune adresse email pour ce client.',
                'created_at' => now(),
            ]);
            return;
        }

        try {
            $settings = $tenant->settings;
            $pdfContent = Pdf::loadView('pdf.templates.free.credit-note.model-1', [
                'creditNote' => $creditNote,
                'settings'   => $settings,
                'tenant'     => $tenant,
                'currency'   => $tenant->default_currency ?? 'MAD',
            ])->setPaper('a4', 'portrait')->output();

            $tempPath = sys_get_temp_dir() . '/avoir-' . $creditNote->number . '-' . uniqid() . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $customer->email)
                ->notify(new CreditNoteSentNotification($creditNote, $tenant, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $customer->email,
                'subject'    => 'Avoir ' . $creditNote->number,
                'type'       => 'credit_note',
                'entity_id'  => $creditNote->id,
                'status'     => 'sent',
                'sent_at'    => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $customer->email,
                'subject'    => 'Avoir ' . $creditNote->number,
                'type'       => 'credit_note',
                'entity_id'  => $creditNote->id,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
                'created_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendCreditNoteEmailJob failed permanently for credit note {$this->creditNoteId}: {$e->getMessage()}");
    }
}
