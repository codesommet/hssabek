<?php

namespace App\Jobs;

use App\Models\Purchases\VendorBill;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\VendorBillSentNotification;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendVendorBillEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $vendorBillId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $vendorBill = VendorBill::with(['supplier', 'items.product', 'items.unit', 'items.taxGroup'])->findOrFail($this->vendorBillId);
        $supplier = $vendorBill->supplier;

        if (!$supplier?->email) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => 'N/A',
                'subject'    => 'Facture fournisseur ' . $vendorBill->number,
                'type'       => 'vendor_bill',
                'entity_id'  => $vendorBill->id,
                'status'     => 'failed',
                'error'      => 'Aucune adresse email pour ce fournisseur.',
                'created_at' => now(),
            ]);
            return;
        }

        try {
            $settings = $tenant->settings;
            $pdfContent = Pdf::loadView('pdf.templates.free.vendor-bill.model-1', [
                'vendorBill' => $vendorBill,
                'settings'   => $settings,
                'tenant'     => $tenant,
                'currency'   => $tenant->default_currency ?? 'MAD',
            ])->setPaper('a4', 'portrait')->output();

            $tempPath = sys_get_temp_dir() . '/facture-fournisseur-' . $vendorBill->number . '-' . uniqid() . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $supplier->email)
                ->notify(new VendorBillSentNotification($vendorBill, $tenant, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Facture fournisseur ' . $vendorBill->number,
                'type'       => 'vendor_bill',
                'entity_id'  => $vendorBill->id,
                'status'     => 'sent',
                'sent_at'    => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Facture fournisseur ' . $vendorBill->number,
                'type'       => 'vendor_bill',
                'entity_id'  => $vendorBill->id,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
                'created_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendVendorBillEmailJob failed permanently for vendor bill {$this->vendorBillId}: {$e->getMessage()}");
    }
}
