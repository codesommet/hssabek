<?php

namespace App\Jobs;

use App\Models\Sales\DeliveryChallan;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\DeliveryChallanSentNotification;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendDeliveryChallanEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $deliveryChallanId,
        public readonly string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant);

        $deliveryChallan = DeliveryChallan::with(['customer', 'items.product', 'items.unit'])->findOrFail($this->deliveryChallanId);
        $customer = $deliveryChallan->customer;

        if (!$customer?->email) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => 'N/A',
                'subject'    => 'Bon de livraison ' . $deliveryChallan->number,
                'type'       => 'delivery_challan',
                'entity_id'  => $deliveryChallan->id,
                'status'     => 'failed',
                'error'      => 'Aucune adresse email pour ce client.',
                'created_at' => now(),
            ]);
            return;
        }

        try {
            $settings = $tenant->settings;
            $pdfContent = Pdf::loadView('pdf.templates.free.delivery-challan.model-1', [
                'deliveryChallan' => $deliveryChallan,
                'settings'        => $settings,
                'tenant'          => $tenant,
                'currency'        => $tenant->default_currency ?? 'MAD',
            ])->setPaper('a4', 'portrait')->output();

            $tempPath = sys_get_temp_dir() . '/bon-livraison-' . $deliveryChallan->number . '-' . uniqid() . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $customer->email)
                ->notify(new DeliveryChallanSentNotification($deliveryChallan, $tenant, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $customer->email,
                'subject'    => 'Bon de livraison ' . $deliveryChallan->number,
                'type'       => 'delivery_challan',
                'entity_id'  => $deliveryChallan->id,
                'status'     => 'sent',
                'sent_at'    => now(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'tenant_id'  => $this->tenantId,
                'to'         => $customer->email,
                'subject'    => 'Bon de livraison ' . $deliveryChallan->number,
                'type'       => 'delivery_challan',
                'entity_id'  => $deliveryChallan->id,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
                'created_at' => now(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SendDeliveryChallanEmailJob failed permanently for delivery challan {$this->deliveryChallanId}: {$e->getMessage()}");
    }
}
