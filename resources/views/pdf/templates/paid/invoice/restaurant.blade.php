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
    <div class="pb-3 mb-3 border-bottom border-3 border-primary">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-3 rounded">
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" alt="Logo" style="max-height: 50px;">
                    @endif
                @endif
                <p class="mb-1">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
                <div class="inv-details">
                    <div class="inv-date-rest">
                        <p class="text-start text-white">N° Facture : <span>{{ $invoice->number }}</span></p>
                    </div>
                    <div class="triangle-right"></div>
                </div>
            </div>
            <div class="text-end">
                <p class="mb-1">Original pour le destinataire</p>
                <h6 class="text-primary mb-2">FACTURE</h6>
                <div>
                    <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    <div>
                        @if(!empty($company['address']))
                        <p class="mb-1">Adresse : <span>{{ $company['address'] }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Bill To ────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <p class="mb-1">Facturé à :</p>
            <h6 class="mb-1 fs-16">{{ $billTo['name'] ?? '' }}</h6>
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <table class="table">
                <thead class="thead border-top border-start-0 border-end-0 border-bottom border-3 border-dark p-2">
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th>Prix unitaire</th>
                        <th>Qté</th>
                        @if($invoice->enable_tax)
                        <th>TVA %</th>
                        @endif
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items->sortBy('position') as $index => $item)
                    <tr class="border-dark">
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
                        @if($invoice->enable_tax)
                        <td><span class="text-dark">{{ number_format($item->tax_rate, 2, ',', ' ') }}%</span></td>
                        @endif
                        <td><span class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</span></td>
                    </tr>
                    @endforeach

                    {{-- Charges --}}
                    @if($invoice->charges->count())
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <tr class="border-dark">
                            <td></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-dark mb-1">{{ $charge->label }}</span>
                                </div>
                            </td>
                            <td><span class="text-dark">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</span></td>
                            <td><span class="text-dark">1</span></td>
                            @if($invoice->enable_tax)
                            <td><span class="text-dark">{{ number_format($charge->tax_rate, 2, ',', ' ') }}%</span></td>
                            @endif
                            <td><span class="text-dark">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</span></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Totals ─────────────────────────────────────────── --}}
    <div class="row mb-2">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex flex-column">
                    <span class="text-dark text-end fw-semibold mb-1">Sous-total HT</span>
                    @if($invoice->discount_total > 0)
                    <span class="text-dark text-end fw-semibold mb-1">Remise</span>
                    @endif
                    @if($invoice->enable_tax)
                    <span class="text-dark text-end fw-semibold">TVA</span>
                    @endif
                </div>
                <div class="d-flex flex-column text-end">
                    <span class="text-dark fw-semibold mb-1">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</span>
                    @if($invoice->discount_total > 0)
                    <span class="text-dark fw-semibold mb-1">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</span>
                    @endif
                    @if($invoice->enable_tax)
                    <span class="text-dark fw-semibold">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Total Bar ──────────────────────────────────────── --}}
    <div class="row border-top border-bottom border-3 border-dark p-3 align-items-center">
        <div class="col-md-8">
            <span class="text-dark">Total articles / Qté : {{ $invoice->items->count() }} / {{ rtrim(rtrim(number_format($invoice->items->sum('quantity'), 2, ',', ' '), '0'), ',') }}</span>
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

    {{-- ─── Total in words ─────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="row py-3 border-bottom border-bottom border-3 border-dark mb-3 d-flex align-items-center">
        <div class="col-md-12">
            <div class="d-flex align-items-center justify-content-end">
                <p class="text-gary">Arrêtée la présente facture à la somme de :<span class="text-dark"> {{ $invoice->total_in_words }}</span></p>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Bank Details & Terms ───────────────────────────── --}}
    <div class="row d-flex align-items-center flex-wrap border-bottom mb-3">
        @if(!empty($bank))
        <div class="col-md-4">
            <div class="mb-3">
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
            </div>
        </div>
        @endif
        @if($invoice->terms)
        <div class="col-md-8">
            <div class="mb-3">
                <h6 class="mb-2">Conditions générales :</h6>
                <p class="mb-0">{!! nl2br(e($invoice->terms)) !!}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="row border border-start-0 border-end-0 border-dark text-center text-white bg-light p-2">
        <div class="col-md-12">
            <p class="text-gray">Merci pour votre confiance</p>
        </div>
    </div>

</div>
</body>
</html>
