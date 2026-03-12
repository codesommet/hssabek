<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    @include('pdf.partials.theme-css')
</head>
<body>
    @php
        $company = $settings?->company_settings ?? [];
        $billTo = $invoice->bill_to_snapshot ?? [];
        $billFrom = $invoice->bill_from_snapshot ?? [];
        $bank = $invoice->bank_details_snapshot ?? [];
        $currency = $invoice->currency ?? ($company['currency'] ?? 'MAD');
        $statusLabels = ['paid' => 'PAYÉE', 'partial' => 'PARTIELLE', 'sent' => 'ENVOYÉE', 'draft' => 'BROUILLON', 'overdue' => 'EN RETARD', 'void' => 'ANNULÉE'];
    @endphp

    {{-- Header: company info left, logo + date + invoice number right --}}
    <div class="mb-3 border-bottom">
        <div class="d-flex align-items-center justify-content-between flex-wrap p-3 rounded">
            <div class="">
                <h6 class="mb-2 text-primary">FACTURE</h6>
                <div>
                    <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    <div>
                        <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] ?? '' }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])) {{ $company['postal_code'] }}@endif @if(!empty($company['country'])) {{ $company['country'] }}@endif</span></p>
                        @if(!empty($company['phone']))
                            <p class="mb-1">Tél : <span class="text-dark">{{ $company['phone'] }}</span></p>
                        @endif
                        @if(!empty($company['tax_id']))
                            <p class="mb-1">IF : <span class="text-dark">{{ $company['tax_id'] }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-end">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" height="50" alt="logo">
                    @endif
                @endif
                <p class="mb-1">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
                <div class="inv-details">
                    <div class="inv-date-nine">
                        <p class="text-dark">N° Facture : <span>{{ $invoice->number }}</span></p>
                    </div>
                    <div class="triangle-left"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customer info on light background: Invoice To + Pay To + Due date/Status --}}
    <div class="row bg-light p-2 mb-3">
        <div class="col-lg-7">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <h6 class="fs-16 text-gray mb-2">Facturé à :</h6>
                        <div>
                            <p class="mb-0 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                            @if(!empty($billTo['address']))
                                <p class="mb-0 text-dark">{{ $billTo['address'] }}@if(!empty($billTo['city'])), {{ $billTo['city'] }}@endif @if(!empty($billTo['postal_code'])) {{ $billTo['postal_code'] }}@endif @if(!empty($billTo['country'])) {{ $billTo['country'] }}@endif</p>
                            @endif
                            @if(!empty($billTo['email'])) <p class="mb-0 text-dark">{{ $billTo['email'] }}</p> @endif
                            @if(!empty($billTo['phone'])) <p class="mb-0 text-dark">{{ $billTo['phone'] }}</p> @endif
                            @if(!empty($billTo['tax_id'])) <p class="mb-0 text-dark">IF : {{ $billTo['tax_id'] }}</p> @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <h6 class="fs-16 text-gray mb-2">Payé à :</h6>
                        <div>
                            <p class="mb-0 text-dark">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
                            @if(!empty($company['address']))
                                <p class="mb-0 text-dark">{{ $company['address'] }}@if(!empty($company['city'])), {{ $company['city'] }}@endif</p>
                            @endif
                            @if(!empty($company['email'])) <p class="mb-0 text-dark">{{ $company['email'] }}</p> @endif
                            @if(!empty($company['phone'])) <p class="mb-0 text-dark">{{ $company['phone'] }}</p> @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="mb-3">
                <div>
                    <p class="mb-3 text-dark">Échéance <br> <span class="badge bg-orange-transparent text-orange">{{ $invoice->due_date?->format('d/m/Y') }}</span></p>
                    <p class="text-dark">Statut du paiement <br> <span class="text-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'info') }}">{{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <div class="table-responsive">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Désignation</th>
                    <th>Qté</th>
                    <th>Prix unitaire</th>
                    @if($invoice->enable_tax)
                    <th>TVA</th>
                    @endif
                    <th class="text-end">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items->sortBy('position') as $index => $item)
                <tr>
                    <td class="text-dark">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->label }}
                        @if($item->description)
                            <br><span style="font-size: 0.85em; color: #888;">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="text-dark">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                    <td>
                        <div>
                            <span class="d-block mb-1">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</span>
                            @if($item->discount_amount > 0)
                                <p class="text-primary">après rem. {{ number_format($item->line_subtotal / ($item->quantity ?: 1), 2, ',', ' ') }} {{ $currency }}</p>
                            @endif
                        </div>
                    </td>
                    @if($invoice->enable_tax)
                    <td class="text-dark">{{ number_format($item->tax_rate, 2) }}%</td>
                    @endif
                    <td class="text-dark text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endforeach

                {{-- Totals summary row --}}
                <tr>
                    <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}"></td>
                    <td class="text-dark">
                        <div>
                            <h6 class="fs-14 fw-medium mb-2 pb-2">Sous-total HT</h6>
                            @if($invoice->enable_tax)
                            <h6 class="fs-14 fw-medium mb-2 pb-2">TVA</h6>
                            @endif
                            @if($invoice->discount_total > 0)
                            <h6 class="fs-14 fw-medium mb-2 pb-2">Remise</h6>
                            @endif
                            @if($invoice->round_off != 0)
                            <h6 class="fs-14 fw-medium mb-0">Arrondi</h6>
                            @endif
                        </div>
                    </td>
                    <td class="text-dark text-end fw-medium">
                        <div>
                            <h6 class="fs-14 fw-medium mb-2 pb-2">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</h6>
                            @if($invoice->enable_tax)
                            <h6 class="fs-14 fw-medium mb-2 pb-2">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</h6>
                            @endif
                            @if($invoice->discount_total > 0)
                            <h6 class="fs-14 fw-medium mb-2 pb-2">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</h6>
                            @endif
                            @if($invoice->round_off != 0)
                            <h6 class="fs-14 fw-medium mb-0">{{ number_format($invoice->round_off, 2, ',', ' ') }} {{ $currency }}</h6>
                            @endif
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Total TTC bar --}}
    <div class="row border-top border-bottom p-2">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div class="text-end">
                    <span class="fw-bold fs-18 text-end text-primary">Total TTC</span>
                </div>
                <div class="text-end">
                    <span class="fw-bold fs-18 text-primary">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Amount paid / due --}}
    @if($invoice->amount_paid > 0)
    <div class="row border-bottom p-2">
        <div class="col-md-8"></div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between">
                <div><span class="fw-medium">Montant payé</span></div>
                <div><span class="fw-medium">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $currency }}</span></div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-1">
                <div><span class="fw-bold">Solde dû</span></div>
                <div><span class="fw-bold">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</span></div>
            </div>
        </div>
    </div>
    @endif

    {{-- Total in words --}}
    @if($invoice->total_in_words)
    <div class="row py-3 border-bottom mb-3 d-flex align-items-center">
        <div class="col-lg-12">
            <div class="d-flex align-items-center justify-content-center">
                <p class="text-dark">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Charges --}}
    @if($invoice->charges->count())
    <div class="table-responsive mb-3">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th colspan="{{ $invoice->enable_tax ? 3 : 2 }}">Frais supplémentaires</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->charges->sortBy('position') as $charge)
                <tr>
                    <td>{{ $charge->label }}</td>
                    @if($invoice->enable_tax)
                    <td class="text-dark">{{ number_format($charge->tax_rate, 2) }}%</td>
                    @endif
                    <td class="text-dark text-end">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Bank details & Terms --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
        <div class="d-flex align-items-center mb-3">
            @if(!empty($bank))
            <div>
                <h6 class="mb-2">Coordonnées bancaires :</h6>
                <div>
                    @if(!empty($bank['bank_name'])) <p class="mb-1">Banque : <span class="text-dark">{{ $bank['bank_name'] }}</span></p> @endif
                    @if(!empty($bank['account_name'])) <p class="mb-1">Titulaire : <span class="text-dark">{{ $bank['account_name'] }}</span></p> @endif
                    @if(!empty($bank['rib'])) <p class="mb-1">RIB : <span class="text-dark">{{ $bank['rib'] }}</span></p> @endif
                    @if(!empty($bank['iban'])) <p class="mb-1">IBAN : <span class="text-dark">{{ $bank['iban'] }}</span></p> @endif
                    @if(!empty($bank['swift'])) <p class="mb-1">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p> @endif
                </div>
            </div>
            @endif
        </div>
        <div class="mb-3">
            @if($invoice->terms)
                <h6 class="mb-2">Conditions générales :</h6>
                <p class="mb-1">{!! nl2br(e($invoice->terms)) !!}</p>
            @endif
        </div>
    </div>

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="border-bottom mb-3 pb-3">
        <p class="mb-2 fw-semibold">Notes :</p>
        <p class="text-dark">{!! nl2br(e($invoice->notes)) !!}</p>
    </div>
    @endif

    {{-- Thank you --}}
    <div class="border-bottom text-center pb-3">
        <p>Merci pour votre confiance</p>
    </div>

    {{-- Signature --}}
    @include('pdf.partials.signature')

</body>
</html>
