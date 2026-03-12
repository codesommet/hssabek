<?php

namespace App\Jobs;

use App\Models\Purchases\DebitNote;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\DebitNoteSentNotification;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendDebitNoteEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $debitNoteId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $debitNote = DebitNote::with(['supplier', 'items.product', 'items.unit', 'items.taxGroup', 'vendorBill'])->findOrFail($this->debitNoteId);
        $supplier = $debitNote->supplier;

        if (!$supplier?->email) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => 'N/A',
                'subject'    => 'Note de débit ' . $debitNote->number,
                'type'       => 'debit_note',
                'entity_id'  => $debitNote->id,
                'status'     => 'failed',
                'error'      => 'Aucune adresse email pour ce fournisseur.',
                'created_at' => now(),
            ]);
            return;
        }

        try {
            $settings = $tenant->settings;
            $pdfContent = Pdf::loadView('pdf.templates.free.debit-note.model-1', [
                'debitNote' => $debitNote,
                'settings'  => $settings,
                'tenant'    => $tenant,
                'currency'  => $tenant->default_currency ?? 'MAD',
            ])->setPaper('a4', 'portrait')->output();

            $tempPath = sys_get_temp_dir() . '/note-debit-' . $debitNote->number . '-' . uniqid() . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $supplier->email)
                ->notify(new DebitNoteSentNotification($debitNote, $tenant, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Note de débit ' . $debitNote->number,
                'type'       => 'debit_note',
                'entity_id'  => $debitNote->id,
                'status'     => 'sent',
                'sent_at'    => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Note de débit ' . $debitNote->number,
                'type'       => 'debit_note',
                'entity_id'  => $debitNote->id,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
                'created_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendDebitNoteEmailJob failed permanently for debit note {$this->debitNoteId}: {$e->getMessage()}");
    }
}
