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

    {{-- ─── Header Banner ────────────────────────────────────── --}}
    <div class="pb-3 mb-3 border-bottom border-3 border-dark">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap gap-2 p-3 rounded">
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" alt="Logo" height="40">
                    @endif
                @endif
                <p class="mb-1">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
                <div class="inv-details">
                    <div class="inv-date-no">
                        <p class="text-start text-white fs-14">N° Facture : <span>{{ $invoice->number }}</span></p>
                    </div>
                    <div class="triangle-right"></div>
                </div>
            </div>
            <div class="text-end">
                <p class="mb-1">Original pour le destinataire</p>
                <h6 class="mb-2">FACTURE</h6>
                <div>
                    <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    <div>
                        <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] ?? '' }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])){{ $company['postal_code'] }}@endif @if(!empty($company['country'])), {{ $company['country'] }}@endif</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Customer Info ────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <p class="mb-1">Facturé à :</p>
            <h6 class="mb-1 fs-16">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</h6>
        </div>
    </div>

    {{-- ─── Items Table ──────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table">
                    <thead class="thead-primary">
                        <tr>
                            <th>#</th>
                            <th>Désignation</th>
                            <th>Prix unit.</th>
                            <th>Qté</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items->sortBy('position') as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-dark mb-1">{{ $item->label }}</span>
                                    @if($item->description)
                                        <span>{{ $item->description }}</span>
                                    @endif
                                </div>
                            </td>
                            <td><span class="text-dark">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</span></td>
                            <td><span class="text-dark">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</span></td>
                            <td><span class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─── Subtotals ────────────────────────────────────────── --}}
    <div class="row mb-2">
        <div class="col-md-8">
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    <span class="text-dark text-end fw-semibold mb-1">Sous-total HT</span>
                    @if($invoice->tax_total > 0)
                        <span class="text-dark text-end fw-semibold mb-1">TVA</span>
                    @endif
                    @if($invoice->discount_total > 0)
                        <span class="text-dark text-end fw-semibold">Remise</span>
                    @endif
                </div>
                <div class="d-flex flex-column text-end">
                    <span class="text-dark fw-semibold mb-1">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</span>
                    @if($invoice->tax_total > 0)
                        <span class="text-dark fw-semibold mb-1">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</span>
                    @endif
                    @if($invoice->discount_total > 0)
                        <span class="text-dark fw-semibold">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Charges ──────────────────────────────────────────── --}}
    @if($invoice->charges->count())
    <div class="row mb-2">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    @foreach($invoice->charges->sortBy('position') as $charge)
                        <span class="text-dark text-end fw-semibold mb-1">{{ $charge->label }}</span>
                    @endforeach
                </div>
                <div class="d-flex flex-column text-end">
                    @foreach($invoice->charges->sortBy('position') as $charge)
                        <span class="text-dark fw-semibold mb-1">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Total TTC Bar ────────────────────────────────────── --}}
    <div class="row border-top border-bottom border-3 border-dark p-2">
        <div class="col-md-8">
            <span class="text-dark">{{ $invoice->items->count() }} article(s)</span>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-end">
                    <span class="fw-bold fs-18 text-end text-dark">Total TTC</span>
                </div>
                <div class="text-end">
                    <span class="fw-bold fs-18 text-dark">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Total in Words ───────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="row py-3 border-bottom border-bottom border-3 border-dark mb-3 d-flex align-items-center">
        <div class="col-lg-12">
            <div class="d-flex align-items-center justify-content-end">
                <p class="text-dark">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Bank Details & Terms ─────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
        <div class="mb-3">
            @if(!empty($bank))
            <h6 class="mb-2">Coordonnées bancaires :</h6>
            <div>
                @if(!empty($bank['bank_name']))
                    <p class="mb-1">Banque : <span class="text-dark">{{ $bank['bank_name'] }}</span></p>
                @endif
                @if(!empty($bank['account_name']))
                    <p class="mb-1">Titulaire : <span class="text-dark">{{ $bank['account_name'] }}</span></p>
                @endif
                @if(!empty($bank['rib']))
                    <p class="mb-1">RIB : <span class="text-dark">{{ $bank['rib'] }}</span></p>
                @endif
                @if(!empty($bank['iban']))
                    <p class="mb-1">IBAN : <span class="text-dark">{{ $bank['iban'] }}</span></p>
                @endif
                @if(!empty($bank['swift']))
                    <p class="mb-1">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p>
                @endif
            </div>
            @endif
        </div>
        <div class="mb-3">
            @if($invoice->terms)
            <h6 class="mb-2">Conditions générales :</h6>
            {!! nl2br(e($invoice->terms)) !!}
            @endif
        </div>
    </div>

    {{-- ─── Signature ────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-end mb-3">
        <div class="text-center">
            <p class="mb-1">Pour {{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
            @include('pdf.partials.signature')
        </div>
    </div>

    {{-- ─── Footer ───────────────────────────────────────────── --}}
    <div class="border-bottom text-center text-white bg-primary p-2">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
