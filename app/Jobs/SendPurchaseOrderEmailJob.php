<?php

namespace App\Jobs;

use App\Models\Purchases\PurchaseOrder;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\PurchaseOrderSentNotification;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendPurchaseOrderEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $purchaseOrderId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.product', 'items.unit', 'items.taxGroup'])->findOrFail($this->purchaseOrderId);
        $supplier = $purchaseOrder->supplier;

        if (!$supplier?->email) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => 'N/A',
                'subject'    => 'Bon de commande ' . $purchaseOrder->number,
                'type'       => 'purchase_order',
                'entity_id'  => $purchaseOrder->id,
                'status'     => 'failed',
                'error'      => 'Aucune adresse email pour ce fournisseur.',
                'created_at' => now(),
            ]);
            return;
        }

        try {
            $settings = $tenant->settings;
            $pdfContent = Pdf::loadView('pdf.templates.free.purchase-order.model-1', [
                'purchaseOrder' => $purchaseOrder,
                'settings'      => $settings,
                'tenant'        => $tenant,
                'currency'      => $tenant->default_currency ?? 'MAD',
            ])->setPaper('a4', 'portrait')->output();

            $tempPath = sys_get_temp_dir() . '/bon-commande-' . $purchaseOrder->number . '-' . uniqid() . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $supplier->email)
                ->notify(new PurchaseOrderSentNotification($purchaseOrder, $tenant, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Bon de commande ' . $purchaseOrder->number,
                'type'       => 'purchase_order',
                'entity_id'  => $purchaseOrder->id,
                'status'     => 'sent',
                'sent_at'    => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $supplier->email,
                'subject'    => 'Bon de commande ' . $purchaseOrder->number,
                'type'       => 'purchase_order',
                'entity_id'  => $purchaseOrder->id,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
                'created_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendPurchaseOrderEmailJob failed permanently for purchase order {$this->purchaseOrderId}: {$e->getMessage()}");
    }
}
