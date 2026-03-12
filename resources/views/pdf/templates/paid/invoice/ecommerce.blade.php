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

    <div>
        {{-- ─── Header ───────────────────────────────────────── --}}
        <div class="d-flex align-items-center justify-content-between border-bottom flex-wrap row-gap-3 mb-3 pb-3">
            <div>
                <h5 class="mb-2">FACTURE</h5>
                <div>
                    <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    <div>
                        @if(!empty($company['tax_id']))
                            <p class="mb-1">IF : <span class="text-dark">{{ $company['tax_id'] }}</span></p>
                        @endif
                        @if(!empty($company['address']))
                            <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])){{ $company['postal_code'] }}@endif @if(!empty($company['country'])), {{ $company['country'] }}@endif</span></p>
                        @endif
                        @if(!empty($company['phone']))
                            <p class="mb-1">Tél : <span class="text-dark">{{ $company['phone'] }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <div class="mb-1"><img src="{{ $logoPath }}" height="40" alt="Logo"></div>
                    @endif
                @endif
                <p class="mb-1 text-end">Original pour le destinataire</p>
                <p class="mb-1 text-end">N° Facture : <span class="text-dark">{{ $invoice->number }}</span></p>
                <p class="mb-1 text-end">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
            </div>
        </div>

        {{-- ─── Addresses ────────────────────────────────────── --}}
        <div class="row">
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="fs-16 text-gray mb-2">Facturé à :</h6>
                            <div>
                                <p class="mb-0 fs-18 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="fs-16 text-gray mb-2">Adresse de facturation :</h6>
                            <div>
                                <p class="mb-0 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                                @if(!empty($billTo['address']))
                                    <p class="mb-0 text-dark">{{ $billTo['address'] }}</p>
                                @endif
                                @if(!empty($billTo['city']))
                                    <p class="mb-0 text-dark">{{ $billTo['city'] }}@if(!empty($billTo['postal_code'])), {{ $billTo['postal_code'] }}@endif</p>
                                @endif
                                @if(!empty($billTo['country']))
                                    <p class="mb-0 text-dark">{{ $billTo['country'] }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="mb-3">
                    <h6 class="fs-16 text-gray mb-2">Adresse de livraison :</h6>
                    <div>
                        <p class="mb-0 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                        @if(!empty($billTo['address']))
                            <p class="mb-0 text-dark">{{ $billTo['address'] }}</p>
                        @endif
                        @if(!empty($billTo['city']))
                            <p class="mb-0 text-dark">{{ $billTo['city'] }}@if(!empty($billTo['postal_code'])), {{ $billTo['postal_code'] }}@endif</p>
                        @endif
                        @if(!empty($billTo['country']))
                            <p class="mb-0 text-dark">{{ $billTo['country'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Items Table ──────────────────────────────────── --}}
        <div class="table-responsive">
            <table class="table table-nowrap table-bordered">
                <thead class="thead-primary">
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th>Prix unit.</th>
                        <th>Qté</th>
                        @if($invoice->enable_tax)
                            <th>Taux TVA</th>
                            <th>Montant TVA</th>
                        @endif
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items->sortBy('position') as $index => $item)
                    <tr>
                        <td class="text-dark">{{ $index + 1 }}</td>
                        <td>
                            <div>
                                <p class="text-dark mb-0">{{ $item->label }}</p>
                                @if($item->description)
                                    <span class="d-block">{{ $item->description }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-dark">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                        <td>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                        @if($invoice->enable_tax)
                            <td class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }}</td>
                            <td>{{ number_format($item->line_total - $item->line_subtotal, 2, ',', ' ') }} ({{ number_format($item->tax_rate, 0) }}%)</td>
                        @endif
                        <td class="text-dark text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endforeach

                    {{-- Charges --}}
                    @if($invoice->charges->count())
                        @php $colsBefore = $invoice->enable_tax ? 5 : 3; @endphp
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <tr>
                            <td colspan="{{ $colsBefore }}" class="border-0"></td>
                            <td class="text-dark fw-medium border-0">{{ $charge->label }}</td>
                            <td class="text-dark text-end fw-medium border-0">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                        @endforeach
                    @endif

                    {{-- Subtotal --}}
                    @php $colsBefore = $invoice->enable_tax ? 5 : 3; @endphp
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-0"></td>
                        <td class="text-dark fw-medium border-0">Sous-total HT</td>
                        <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @if($invoice->tax_total > 0)
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-0"></td>
                        <td class="text-dark fw-medium border-0">TVA</td>
                        <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif
                    @if($invoice->discount_total > 0)
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-0"></td>
                        <td class="text-dark fw-medium border-0">Remise</td>
                        <td class="text-dark text-end fw-medium border-0">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif

                    {{-- Total TTC --}}
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="text-dark border-0">{{ $invoice->items->count() }} article(s)</td>
                        <td class="text-dark fw-medium border-0">
                            <h6>Total TTC</h6></td>
                        <td class="text-dark text-end fw-medium border-0">
                            <h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ─── Total in Words ───────────────────────────────── --}}
        <div class="py-3 border-bottom mb-3 d-flex align-items-center justify-content-between">
            @if($invoice->total_in_words)
            <p class="text-dark">Arrêtée la présente facture à la somme de :
                <br> {{ $invoice->total_in_words }}</p>
            @else
            <p></p>
            @endif
            <div class="d-flex align-items-center">
                <span class="text-dark fw-medium text-center me-2"><h6>Total TTC</h6></span>
                <span class="text-dark text-end fw-medium"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></span>
            </div>
        </div>

        {{-- ─── Bank Details & Signature ─────────────────────── --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
            <div class="mb-3">
                @if(!empty($bank))
                <h6 class="mb-2">Coordonnées bancaires</h6>
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
                        <p class="mb-0">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p>
                    @endif
                </div>
                @endif
            </div>
            <div class="text-center mb-3">
                <p class="mb-1">Pour {{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
                @include('pdf.partials.signature')
            </div>
        </div>

        {{-- ─── Terms ────────────────────────────────────────── --}}
        @if($invoice->terms)
        <div class="border-bottom mb-3 pb-3">
            <h6 class="mb-2">Conditions générales :</h6>
            {!! nl2br(e($invoice->terms)) !!}
        </div>
        @endif

        {{-- ─── Footer ───────────────────────────────────────── --}}
        <div class="border-bottom text-center pb-3">
            <p>Merci pour votre confiance</p>
        </div>
    </div>

</div>
</body>
</html>
