<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    @include('pdf.partials.theme-css')
</head>
<body>
<div class="content p-4">

    @php
        $company = $settings?->company_settings ?? [];
        $billTo = $invoice->bill_to_snapshot ?? [];
        $billFrom = $invoice->bill_from_snapshot ?? [];
        $bank = $invoice->bank_details_snapshot ?? [];
        $currency = $invoice->currency ?? 'MAD';
    @endphp

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="pb-3 mb-3">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-3 rounded">
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" alt="Logo" style="max-height: 50px;">
                    @endif
                @endif
                <p class="mb-1">Original pour le destinataire</p>
                <p class="mb-1">N° Facture : <span class="text-dark">{{ $invoice->number }}</span></p>
                <p class="mb-1">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
            </div>
            <div class="text-end">
                <h6 class="mb-1">FACTURE</h6>
                <div>
                    <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    <div>
                        @if(!empty($company['address']))
                        <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Invoice Details ────────────────────────────────── --}}
    <div class="mb-3">
        <h6 class="mb-2 fs-16 bg-light p-2 text-dark">Détails de la facture</h6>
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">Date d'émission</span>
                <p>{{ $invoice->issue_date?->format('d/m/Y') }}</p>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">Échéance</span>
                <p>{{ $invoice->due_date?->format('d/m/Y') }}</p>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">N° Facture</span>
                <p>{{ $invoice->number }}</p>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">Référence</span>
                <p>{{ $invoice->reference_number ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- ─── Items Overview ─────────────────────────────────── --}}
    <div class="mb-3">
        <h6 class="mb-2 fs-16 bg-light p-2 text-dark">Détails des articles</h6>
        @foreach($invoice->items->sortBy('position') as $index => $item)
        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">{{ $item->label }}</span>
                <p>{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</p>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">Qté</span>
                <p>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</p>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                <span class="text-dark me-2">Total</span>
                <p>{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</p>
            </div>
        </div>
        @endforeach

        {{-- Charges --}}
        @if($invoice->charges->count())
        <div class="">
            <div class="d-flex align-items-center justify-content-between gap-3">
                @foreach($invoice->charges->sortBy('position') as $charge)
                <div class="d-flex align-items-center justify-content-between flex-fill border rounded p-2">
                    <span class="text-dark me-2">{{ $charge->label }}</span>
                    <p>{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ─── Totals ─────────────────────────────────────────── --}}
    <div class="mb-3">
        <h6 class="mb-2 fs-16 bg-light p-2 text-dark">Récapitulatif</h6>
        <div class="row">
            <div class="col-lg-6 d-flex">
                <div class="flex-fill border rounded p-2">
                    <p class="mb-1 d-flex align-items-center justify-content-between">Sous-total HT : <span class="text-dark">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</span></p>
                    @if($invoice->discount_total > 0)
                    <p class="mb-1 d-flex align-items-center justify-content-between">Remise : <span class="text-dark">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</span></p>
                    @endif
                    @if($invoice->tax_total > 0)
                    <p class="mb-1 d-flex align-items-center justify-content-between">TVA : <span class="text-dark">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</span></p>
                    @endif
                    <p class="mb-0 d-flex align-items-center justify-content-between fw-bold">Total TTC : <span class="text-dark fw-bold">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</span></p>
                </div>
            </div>
            @if(!empty($bank))
            <div class="col-lg-6 d-flex">
                <div class="flex-fill border rounded p-2">
                    @if(!empty($bank['bank_name']))
                    <p class="mb-1 d-flex align-items-center justify-content-between">Banque : <span class="text-dark">{{ $bank['bank_name'] }}</span></p>
                    @endif
                    @if(!empty($bank['account_name']))
                    <p class="mb-1 d-flex align-items-center justify-content-between">Titulaire : <span class="text-dark">{{ $bank['account_name'] }}</span></p>
                    @endif
                    @if(!empty($bank['rib']))
                    <p class="mb-1 d-flex align-items-center justify-content-between">RIB : <span class="text-dark">{{ $bank['rib'] }}</span></p>
                    @endif
                    @if(!empty($bank['iban']))
                    <p class="mb-1 d-flex align-items-center justify-content-between">IBAN : <span class="text-dark">{{ $bank['iban'] }}</span></p>
                    @endif
                    @if(!empty($bank['swift']))
                    <p class="mb-0 d-flex align-items-center justify-content-between">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ─── Total in words ─────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="mb-3">
        <p>Arrêtée la présente facture à la somme de : <span class="text-dark fw-medium">{{ $invoice->total_in_words }}</span></p>
    </div>
    @endif

    {{-- ─── Notes ──────────────────────────────────────────── --}}
    @if($invoice->notes)
    <div class="mb-3">
        <h6 class="mb-2 fs-16 bg-light p-2 text-dark">Notes</h6>
        <div class="row">
            <p>{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
    </div>
    @endif

    {{-- ─── Terms ──────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="mb-3">
        <h6 class="mb-2 fs-16 bg-light p-2 text-dark">Conditions générales</h6>
        <div class="row">
            <p>{!! nl2br(e($invoice->terms)) !!}</p>
        </div>
    </div>
    @endif

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="border-top border-bottom text-center p-2">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
