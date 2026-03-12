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

    {{-- Header bar: logo left, "FACTURE" right, on light bg --}}
    <div class="mb-3 p-2 bg-light">
        <div class="d-flex align-items-center justify-content-between flex-wrap p-3 rounded">
            <div class="">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" height="50" alt="logo">
                    @endif
                @endif
            </div>
            <div class="text-end">
                <h6 class="mb-2 text-primary">FACTURE</h6>
            </div>
        </div>
    </div>

    {{-- Invoice details line with gradient block --}}
    <div class="invoice-five-details d-flex">
        <div class="gradient-block me-4"></div>
        <div class="d-flex align-items-center gap-2">
            <div class="text-dark fs-13 me-4">N° Facture :<span>{{ $invoice->number }}</span></div>
            <div class="text-dark fs-13">Date de facturation :<span> {{ $invoice->issue_date?->format('d/m/Y') }}</span></div>
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

    {{-- Client details + company details --}}
    <div class="d-flex align-items-center justify-content-between border-bottom flex-wrap row-gap-3 mb-3 pb-3">
        <div>
            <h5 class="mb-2">Détails du client :</h5>
            <div>
                <h6 class="mb-1">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</h6>
                <div class="mb-2">
                    @if(!empty($billTo['tax_id']))
                        <p>IF : <span class="text-dark">{{ $billTo['tax_id'] }}</span></p>
                    @endif
                </div>
                <h6 class="mb-1 fw-semibold text-gray mb-2">Statut du paiement</h6>
                <h6 class="mb-1 text-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'info') }}">{{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}</h6>
            </div>
        </div>
        <div>
            <h6 class="mb-2 text-end">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
            @if(!empty($company['tax_id'])) <p class="mb-1 text-end">IF : <span class="text-dark">{{ $company['tax_id'] }}</span></p> @endif
            @if(!empty($company['ice'])) <p class="mb-1 text-end">ICE : <span class="text-dark">{{ $company['ice'] }}</span></p> @endif
            @if(!empty($company['address'])) <p class="mb-1 text-end">Adresse : <span class="text-dark">{{ $company['address'] }}@if(!empty($company['city'])), {{ $company['city'] }}@endif</span></p> @endif
            @if(!empty($company['phone'])) <p class="mb-1 text-end">Tél : <span class="text-dark">{{ $company['phone'] }}</span></p> @endif
        </div>
    </div>

    {{-- Addresses: billing left, shipping right --}}
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <h6 class="fs-16 text-gray-5 mb-2">Adresse de facturation :</h6>
                <div>
                    <p class="mb-0 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                    @if(!empty($billTo['address']))
                        <p class="mb-0 text-dark">{{ $billTo['address'] }}@if(!empty($billTo['city'])), {{ $billTo['city'] }}@endif @if(!empty($billTo['postal_code'])) {{ $billTo['postal_code'] }}@endif @if(!empty($billTo['country'])) {{ $billTo['country'] }}@endif</p>
                    @endif
                    @if(!empty($billTo['email'])) <p class="mb-0 text-dark">{{ $billTo['email'] }}</p> @endif
                    @if(!empty($billTo['phone'])) <p class="mb-0 text-dark">{{ $billTo['phone'] }}</p> @endif
                </div>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="mb-3">
                <h6 class="fs-16 text-gray-5 mb-2">Adresse de livraison :</h6>
                <div>
                    <p class="mb-0 text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                    @if(!empty($billTo['address']))
                        <p class="mb-0 text-dark">{{ $billTo['address'] }}@if(!empty($billTo['city'])), {{ $billTo['city'] }}@endif @if(!empty($billTo['postal_code'])) {{ $billTo['postal_code'] }}@endif @if(!empty($billTo['country'])) {{ $billTo['country'] }}@endif</p>
                    @endif
                    @if(!empty($billTo['email'])) <p class="mb-0 text-dark">{{ $billTo['email'] }}</p> @endif
                    @if(!empty($billTo['phone'])) <p class="mb-0 text-dark">{{ $billTo['phone'] }}</p> @endif
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

    {{-- Total in words + Amount payable --}}
    <div class="py-3 border-top-0 border-bottom d-flex align-items-center justify-content-between">
        @if($invoice->total_in_words)
            <p class="text-dark mb-0">Arrêtée la présente facture à la somme de :<br>{{ $invoice->total_in_words }}</p>
        @else
            <p class="mb-0"></p>
        @endif
        <div class="d-flex align-items-center">
            <span class="border-end-0"></span>
            <span class="text-dark fw-medium border-end-0 border-start-0 text-center me-2"><h6>Total TTC</h6></span>
            <span class="text-dark text-end fw-medium border-start-0"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></span>
        </div>
    </div>

    {{-- Amount paid / due --}}
    @if($invoice->amount_paid > 0)
    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
        <div></div>
        <div>
            <p class="mb-1 text-dark fw-medium">Montant payé : {{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $currency }}</p>
            <p class="mb-0 text-dark fw-bold">Solde dû : {{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</p>
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

    {{-- Bank details & Signature --}}
    <div class="d-flex align-items-center py-3 justify-content-between flex-wrap border-bottom mb-3">
        <div class="mb-3">
            @if(!empty($bank))
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <h6 class="mb-2">Coordonnées bancaires</h6>
                    @if(!empty($bank['bank_name'])) <p class="mb-1">Banque : <span class="text-dark">{{ $bank['bank_name'] }}</span></p> @endif
                    @if(!empty($bank['account_name'])) <p class="mb-1">Titulaire : <span class="text-dark">{{ $bank['account_name'] }}</span></p> @endif
                    @if(!empty($bank['rib'])) <p class="mb-1">RIB : <span class="text-dark">{{ $bank['rib'] }}</span></p> @endif
                    @if(!empty($bank['iban'])) <p class="mb-1">IBAN : <span class="text-dark">{{ $bank['iban'] }}</span></p> @endif
                    @if(!empty($bank['swift'])) <p class="mb-0">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p> @endif
                </div>
            </div>
            @endif
        </div>
        <div class="text-center mb-3">
            <p class="mb-1">Pour {{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
            @include('pdf.partials.signature')
        </div>
    </div>

    {{-- Terms --}}
    @if($invoice->terms)
    <div class="d-flex align-items-center flex-wrap border-bottom mb-3">
        <div class="mb-3">
            <p class="mb-2 fs-13 text-gray">Conditions générales :</p>
            <p class="mb-1 text-dark">{!! nl2br(e($invoice->terms)) !!}</p>
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if($invoice->notes)
    <div class="border-bottom mb-3 pb-3">
        <p class="mb-2 fw-semibold">Notes :</p>
        <p class="text-dark">{!! nl2br(e($invoice->notes)) !!}</p>
    </div>
    @endif

    {{-- Thank you --}}
    <div class="border-bottom pb-3">
        <p>Merci pour votre confiance</p>
    </div>

</body>
</html>
