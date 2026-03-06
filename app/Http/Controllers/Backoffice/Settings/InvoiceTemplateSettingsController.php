<?php

namespace App\Http\Controllers\Backoffice\Settings;

use App\Http\Controllers\Controller;
use App\Models\Templates\TemplateCatalog;
use App\Models\Tenancy\TenantSetting;
use App\Services\Sales\PdfService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class InvoiceTemplateSettingsController extends Controller
{
    public function index()
    {
        $tenant = TenantContext::get();
        $settings = $tenant->settings;
        $currentTemplate = $settings->invoice_settings['pdf_template'] ?? 'default';

        // Document types with French labels
        $documentTypes = [
            'invoice'          => 'Factures',
            'quote'            => 'Devis',
            'credit_note'      => 'Avoirs',
            'delivery_challan' => 'Bons de livraison',
            'purchase_order'   => 'Bons de commande',
        ];

        // All active catalog templates grouped by document_type
        $allTemplates = TemplateCatalog::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Tenant's accessible template IDs
        $ownedTemplateIds = DB::table('tenant_templates')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->pluck('template_id')
            ->toArray();

        $purchasedTemplateIds = DB::table('template_purchases')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->pluck('template_id')
            ->toArray();

        $accessibleIds = array_unique(array_merge($ownedTemplateIds, $purchasedTemplateIds));

        // Split: "Mes modèles" (free + owned) vs "Boutique" (paid, not owned)
        $myTemplates = collect();
        $storeTemplates = collect();

        foreach ($allTemplates as $tpl) {
            if ($tpl->is_free || in_array($tpl->id, $accessibleIds)) {
                $myTemplates->push($tpl);
            } else {
                $storeTemplates->push($tpl);
            }
        }

        $myTemplatesGrouped = $myTemplates->groupBy('document_type');
        $storeTemplatesGrouped = $storeTemplates->groupBy('document_type');

        return view('backoffice.settings.invoice-templates', compact(
            'documentTypes',
            'myTemplatesGrouped',
            'storeTemplatesGrouped',
            'currentTemplate',
            'settings',
            'tenant'
        ));
    }

    public function activate(string $template)
    {
        $catalogTemplate = TemplateCatalog::where('code', $template)
            ->where('is_active', true)
            ->first();

        if (!$catalogTemplate && !array_key_exists($template, PdfService::TEMPLATES)) {
            return redirect()->route('bo.settings.invoice-templates.index')
                ->with('error', 'Modèle invalide.');
        }

        $tenant = TenantContext::get();

        // If paid template, verify access
        if ($catalogTemplate && !$catalogTemplate->is_free) {
            $hasAccess = DB::table('tenant_templates')
                ->where('tenant_id', $tenant->id)
                ->where('template_id', $catalogTemplate->id)
                ->where('status', 'active')
                ->exists();

            if (!$hasAccess) {
                $hasAccess = DB::table('template_purchases')
                    ->where('tenant_id', $tenant->id)
                    ->where('template_id', $catalogTemplate->id)
                    ->where('status', 'paid')
                    ->exists();
            }

            if (!$hasAccess) {
                return redirect()->route('bo.settings.invoice-templates.index')
                    ->with('error', 'Vous n\'avez pas accès à ce modèle.');
            }
        }

        $setting = $tenant->settings ?? TenantSetting::create(['tenant_id' => $tenant->id]);

        $invoiceSettings = $setting->invoice_settings ?? [];
        $invoiceSettings['pdf_template'] = $template;
        $setting->invoice_settings = $invoiceSettings;
        $setting->save();

        $name = $catalogTemplate ? $catalogTemplate->name : (PdfService::TEMPLATES[$template]['name'] ?? $template);

        return redirect()->route('bo.settings.invoice-templates.index')
            ->with('success', 'Modèle "' . $name . '" activé avec succès.');
    }

    public function preview(string $template)
    {
        $catalogTemplate = TemplateCatalog::where('code', $template)
            ->where('is_active', true)
            ->first();

        if (!$catalogTemplate && !array_key_exists($template, PdfService::TEMPLATES)) {
            abort(404);
        }

        $tenant = TenantContext::get();
        $settings = $tenant->settings;

        $invoiceSettings = $settings->invoice_settings ?? [];
        $invoiceSettings['pdf_template'] = $template;
        $settings->invoice_settings = $invoiceSettings;

        $invoice = \App\Models\Sales\Invoice::with(['customer', 'items.product', 'items.unit', 'items.taxGroup', 'charges'])
            ->latest()
            ->first();

        if ($invoice) {
            $pdfService = new PdfService();
            return $pdfService->invoiceResponse($invoice, 'inline');
        }

        return response()->view('pdf.preview-placeholder', [
            'templateName' => $catalogTemplate ? $catalogTemplate->name : PdfService::TEMPLATES[$template]['name'],
            'tenant' => $tenant,
            'settings' => $settings,
        ]);
    }

    public function purchase(string $templateId)
    {
        $tenant = TenantContext::get();

        $catalogTemplate = TemplateCatalog::where('id', $templateId)
            ->where('is_active', true)
            ->where('is_free', false)
            ->first();

        if (!$catalogTemplate) {
            return redirect()->route('bo.settings.invoice-templates.index')
                ->with('error', 'Modèle introuvable.');
        }

        // Check if already owned or purchased
        $alreadyOwned = DB::table('tenant_templates')
            ->where('tenant_id', $tenant->id)
            ->where('template_id', $catalogTemplate->id)
            ->where('status', 'active')
            ->exists();

        $alreadyPurchased = DB::table('template_purchases')
            ->where('tenant_id', $tenant->id)
            ->where('template_id', $catalogTemplate->id)
            ->whereIn('status', ['paid', 'pending'])
            ->exists();

        if ($alreadyOwned || $alreadyPurchased) {
            return redirect()->route('bo.settings.invoice-templates.index')
                ->with('error', 'Vous possédez déjà ce modèle.');
        }

        // Redirect to WhatsApp for payment
        $phone = '212632582096'; // 0632582096 in international format
        $message = "Bonjour, je souhaite acheter le modèle de facture \"{$catalogTemplate->name}\" "
            . "(Réf: {$catalogTemplate->code}) au prix de " . number_format($catalogTemplate->price, 2) . " " . ($catalogTemplate->currency ?? 'MAD') . ". "
            . "Entreprise: {$tenant->name}. Merci.";

        $whatsappUrl = 'https://wa.me/' . $phone . '?text=' . urlencode($message);

        return redirect()->away($whatsappUrl);
    }
}
