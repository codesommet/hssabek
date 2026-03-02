# Phase 9 — PDF Generation & Email Notifications

> **Depends on:** Phase 4 (Sales — invoices/quotes must exist first)
> **Complexity:** L
> **Critical:** Required before "Send Invoice" feature is usable.

---

## 1. Objective

1. Generate professional PDF documents for invoices, quotes, and purchase orders using tenant branding (logo, address, color scheme from `TenantSetting`)
2. Send invoices to customers via email, queued asynchronously
3. Record every sent email in `EmailLog`

---

## 2. Scope

**New package (add to composer.json):**
```bash
composer require barryvdh/laravel-dompdf
```

**New files:**
- `app/Services/Sales/PdfService.php`
- `app/Jobs/SendInvoiceEmailJob.php` (already referenced in Phase 4)
- `app/Notifications/InvoiceSentNotification.php`
- `app/Notifications/QuoteSentNotification.php`
- `resources/views/pdf/invoice.blade.php`
- `resources/views/pdf/quote.blade.php`
- `resources/views/pdf/purchase-order.blade.php`

**Controllers updated:**
- `InvoiceController::download()` — now returns actual PDF
- `InvoiceController::send()` — dispatches real email job
- `QuoteController::download()` — returns PDF
- `QuoteController::send()` — dispatches real email job

**Models (existing — do not modify):**
- `EmailLog` — `tenant_id`, `to_email`, `subject`, `body`, `status`, `sent_at`, `error_message`, `loggable_type`, `loggable_id`

---

## 3. Required Architectural Placement

| Concern | File | Type |
|---------|------|------|
| PDF generation | `app/Services/Sales/PdfService.php` | NEW Service |
| Email queuing | `app/Jobs/SendInvoiceEmailJob.php` | NEW Job |
| Email notification | `app/Notifications/InvoiceSentNotification.php` | NEW Notification |
| Email logging | Inside `SendInvoiceEmailJob::handle()` | Job logic |

---

## 4. Ordered Task Breakdown

### Task 9.1 — Install and Configure DomPDF

```bash
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Configure `config/dompdf.php`:
```php
'options' => [
    'font_dir'             => storage_path('fonts/'),
    'font_cache'           => storage_path('fonts/'),
    'temp_dir'             => sys_get_temp_dir(),
    'chroot'               => realpath(base_path()),
    'enable_remote'        => true,   // Allow logo image from storage
    'default_media_type'   => 'print',
    'default_paper_size'   => 'a4',
    'default_font'         => 'sans-serif',
    'dpi'                  => 96,
    'enable_font_subsetting' => true,
],
```

### Task 9.2 — Implement `PdfService`

```php
// app/Services/Sales/PdfService.php
<?php

namespace App\Services\Sales;

use App\Models\Sales\Invoice;
use App\Models\Sales\Quote;
use App\Models\Purchases\PurchaseOrder;
use App\Services\Tenancy\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Generate invoice PDF and return as streamed response.
     */
    public function invoice(Invoice $invoice, string $disposition = 'inline'): Response
    {
        $invoice->loadMissing(['customer', 'items.taxCategory', 'charges', 'tenant', 'signature']);
        $settings = TenantContext::get()?->settings;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'  => $invoice,
            'settings' => $settings,
            'tenant'   => TenantContext::get(),
        ])->setPaper('a4', 'portrait');

        $filename = 'facture-' . $invoice->invoice_number . '.pdf';

        return ($disposition === 'download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }

    /**
     * Generate quote PDF.
     */
    public function quote(Quote $quote, string $disposition = 'inline'): Response
    {
        $quote->loadMissing(['customer', 'items.taxCategory', 'charges', 'tenant']);
        $settings = TenantContext::get()?->settings;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote'    => $quote,
            'settings' => $settings,
            'tenant'   => TenantContext::get(),
        ])->setPaper('a4', 'portrait');

        $filename = 'devis-' . $quote->quote_number . '.pdf';

        return ($disposition === 'download')
            ? $pdf->download($filename)
            : $pdf->stream($filename);
    }
}
```

### Task 9.3 — Update `InvoiceController::download()` and `::send()`

```php
// app/Http/Controllers/Backoffice/Sales/InvoiceController.php

public function download(Invoice $invoice)
{
    abort_unless(auth()->user()->can('sales.invoices.view'), 403);
    return app(PdfService::class)->invoice($invoice, 'download');
}

public function send(Invoice $invoice)
{
    abort_unless(auth()->user()->can('sales.invoices.edit'), 403);

    $this->invoiceService->transition($invoice, 'sent');

    dispatch(new SendInvoiceEmailJob(
        invoiceId: $invoice->id,
        tenantId:  TenantContext::id(),
        userId:    auth()->id(),
    ));

    return redirect()->back()
        ->with('success', 'Facture envoyée au client par email.');
}
```

### Task 9.4 — Implement `SendInvoiceEmailJob`

```php
// app/Jobs/SendInvoiceEmailJob.php
<?php

namespace App\Jobs;

use App\Models\Sales\Invoice;
use App\Models\System\EmailLog;
use App\Models\Tenancy\Tenant;
use App\Notifications\InvoiceSentNotification;
use App\Services\Sales\PdfService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds between retries

    public function __construct(
        public readonly string $invoiceId,
        public readonly string $tenantId,
        public readonly string $userId,
    ) {}

    public function handle(PdfService $pdfService): void
    {
        $tenant  = Tenant::findOrFail($this->tenantId);
        TenantContext::set($tenant); // Required for TenantScope to work in job

        $invoice = Invoice::with(['customer', 'items', 'charges'])->findOrFail($this->invoiceId);
        $customer = $invoice->customer;

        if (!$customer->email) {
            EmailLog::create([
                'to_email'       => 'N/A',
                'subject'        => 'Facture ' . $invoice->invoice_number,
                'status'         => 'failed',
                'error_message'  => 'Aucune adresse email pour ce client.',
                'loggable_type'  => Invoice::class,
                'loggable_id'    => $invoice->id,
            ]);
            return;
        }

        try {
            // Generate PDF as temp file
            $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
                'invoice'  => $invoice,
                'settings' => $tenant->settings,
                'tenant'   => $tenant,
            ])->output();

            $tempPath = sys_get_temp_dir() . '/invoice-' . $invoice->invoice_number . '.pdf';
            file_put_contents($tempPath, $pdfContent);

            Notification::route('mail', $customer->email)
                ->notify(new InvoiceSentNotification($invoice, $tempPath));

            @unlink($tempPath);

            EmailLog::create([
                'to_email'      => $customer->email,
                'subject'       => 'Facture ' . $invoice->invoice_number,
                'status'        => 'sent',
                'sent_at'       => now(),
                'loggable_type' => Invoice::class,
                'loggable_id'   => $invoice->id,
            ]);

        } catch (\Throwable $e) {
            EmailLog::create([
                'to_email'      => $customer->email,
                'subject'       => 'Facture ' . $invoice->invoice_number,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'loggable_type' => Invoice::class,
                'loggable_id'   => $invoice->id,
            ]);
            throw $e; // Re-throw so queue retries
        }
    }

    public function failed(\Throwable $e): void
    {
        // Final failure after all retries — log it
        \Log::error("SendInvoiceEmailJob failed permanently for invoice {$this->invoiceId}: {$e->getMessage()}");
    }
}
```

### Task 9.5 — Implement `InvoiceSentNotification`

```php
// app/Notifications/InvoiceSentNotification.php
public function toMail(mixed $notifiable): MailMessage
{
    $tenant = TenantContext::get();
    return (new MailMessage)
        ->subject('Facture ' . $this->invoice->invoice_number . ' — ' . ($tenant?->name ?? ''))
        ->greeting('Bonjour,')
        ->line('Veuillez trouver ci-joint votre facture n° ' . $this->invoice->invoice_number . '.')
        ->line('Montant total : ' . number_format($this->invoice->total_amount, 2) . ' ' . config('app.currency'))
        ->line('Date d\'échéance : ' . $this->invoice->due_date->format('d/m/Y'))
        ->action('Voir la facture', route('bo.sales.invoices.show', $this->invoice))
        ->attach($this->pdfPath, ['as' => 'facture-' . $this->invoice->invoice_number . '.pdf'])
        ->line('Merci pour votre confiance.');
}
```

### Task 9.6 — Create PDF Blade Templates

**`resources/views/pdf/invoice.blade.php`:**
- A4 portrait HTML template
- Tenant logo (from MediaLibrary) at top-left
- Tenant name, address, tax ID from `$settings->company_settings`
- Customer name and address from `$invoice->customer`
- Invoice number, date, due date
- Line items table: description, qty, unit price, tax, total
- Subtotal, tax, grand total footer
- Signature image (if `$invoice->signature`)
- Payment terms and notes
- Use inline CSS only (DomPDF doesn't support external stylesheets reliably)
- Font: `DejaVu Sans` or `sans-serif` (DomPDF safe)

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f5f5f5; padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .totals-table { width: 300px; margin-left: auto; margin-top: 20px; }
        .total-row { font-weight: bold; font-size: 13px; }
        .badge-status { padding: 3px 8px; border-radius: 3px; font-size: 10px; }
    </style>
</head>
<body>
    {{-- Header: Tenant vs Customer --}}
    <table class="header-table">
        <tr>
            <td style="width:50%">
                @if($tenant && $tenant->getFirstMedia('logo'))
                    <img src="{{ $tenant->getFirstMediaUrl('logo') }}" height="50" alt="logo">
                @endif
                <div><strong>{{ $settings?->company_settings['company_name'] ?? $tenant?->name }}</strong></div>
                <div>{{ $settings?->company_settings['address'] ?? '' }}</div>
                <div>IF: {{ $settings?->company_settings['tax_id'] ?? '' }}</div>
            </td>
            <td style="width:50%; text-align:right">
                <h2 style="color:#2563eb">FACTURE</h2>
                <div>N° {{ $invoice->invoice_number }}</div>
                <div>Date : {{ $invoice->invoice_date->format('d/m/Y') }}</div>
                <div>Échéance : {{ $invoice->due_date->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    {{-- Customer Info --}}
    <div style="margin: 20px 0">
        <strong>Facturé à :</strong><br>
        {{ $invoice->customer->name }}<br>
        {{ $invoice->customer->email }}<br>
        {{ $invoice->customer->phone }}
    </div>

    {{-- Items Table --}}
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right">Qté</th>
                <th style="text-align:right">Prix unitaire</th>
                <th style="text-align:right">TVA</th>
                <th style="text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align:right">{{ $item->quantity }}</td>
                <td style="text-align:right">{{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align:right">{{ number_format($item->tax_amount, 2) }}</td>
                <td style="text-align:right">{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr><td>Sous-total</td><td style="text-align:right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>TVA</td><td style="text-align:right">{{ number_format($invoice->tax_amount, 2) }}</td></tr>
        <tr class="total-row" style="font-size:13px;font-weight:bold">
            <td>Total TTC</td>
            <td style="text-align:right">{{ number_format($invoice->total_amount, 2) }} {{ config('app.currency') }}</td>
        </tr>
    </table>

    @if($invoice->notes)
    <div style="margin-top:30px; font-size:10px; color:#666">
        <strong>Notes :</strong> {{ $invoice->notes }}
    </div>
    @endif
</body>
</html>
```

---

## 5. Deliverables

| File | Action |
|------|--------|
| `composer.json` | `barryvdh/laravel-dompdf` added |
| `config/dompdf.php` | Published + configured |
| `app/Services/Sales/PdfService.php` | New |
| `app/Jobs/SendInvoiceEmailJob.php` | New |
| `app/Notifications/InvoiceSentNotification.php` | New |
| `app/Notifications/QuoteSentNotification.php` | New |
| `resources/views/pdf/invoice.blade.php` | New |
| `resources/views/pdf/quote.blade.php` | New |
| `InvoiceController::download()` | Updated |
| `InvoiceController::send()` | Updated |

---

## 6. Acceptance Criteria

- [ ] `GET /backoffice/sales/invoices/{id}/download` → returns PDF file (Content-Type: application/pdf)
- [ ] PDF contains correct invoice number, customer name, line items, totals
- [ ] PDF shows tenant logo and company settings
- [ ] "Send" button dispatches job to queue (`jobs` table has a record)
- [ ] After job runs: email delivered, `email_logs` record has `status = 'sent'`
- [ ] If customer has no email: `email_logs` record has `status = 'failed'`, no exception
- [ ] Failed job (e.g. SMTP down): retried 3 times, then `failed_jobs` record created

---

## 7. Tests Required

| Test File | Type | Covers |
|-----------|------|--------|
| `tests/Feature/Sales/InvoicePdfTest.php` | Feature | Download returns PDF, correct content type |
| `tests/Feature/Sales/InvoiceSendTest.php` | Feature | Send dispatches job, email_log created |
| `tests/Unit/Jobs/SendInvoiceEmailJobTest.php` | Unit | Job handles missing email gracefully |

---

## 8. Multi-Tenant Pitfalls

- `SendInvoiceEmailJob` MUST call `TenantContext::set($tenant)` at start of `handle()` — without this, `Invoice::findOrFail()` might fail if scope requires context
- Cache key for PDF generation (if any) must include `tenant_id`
- Tenant logo URL must be accessible — ensure storage is linked (`php artisan storage:link`)
- Never expose one tenant's invoice PDF URL to another tenant (route model binding with TenantScope handles this)

---

## 9. Schema Notes

**`email_logs` columns (existing):** `tenant_id`, `to_email`, `subject`, `body` (nullable), `status` (pending/sent/failed), `sent_at` (nullable), `error_message` (nullable), `loggable_type` (nullable), `loggable_id` (nullable)

Do NOT add `pdf_path` to `email_logs` — PDFs are temporary files, not stored.

---

## 10. UI Instructions

- Download button: `<a href="{{ route('bo.sales.invoices.download', $invoice) }}" target="_blank">` (opens in new tab)
- Send button: POST form with CSRF, only visible for non-cancelled invoices with customer email
- PDF template must use inline CSS only (no external stylesheets — DomPDF limitation)
- Colors in PDF should match tenant branding if `company_settings['brand_color']` is set
