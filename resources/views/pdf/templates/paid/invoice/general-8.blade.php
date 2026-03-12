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

    <div>
        {{-- Header: title + logo --}}
        <div class="d-flex align-items-center justify-content-between rounded flex-wrap row-gap-3 mb-2">
            <h5 class="text-primary">Facture</h5>
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <div class="mb-1"><img src="{{ $logoPath }}" height="50" alt="logo"></div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Company info --}}
        <div class="mb-3">
            <div class="row">
                <div class="mb-2">
                    <h6 class="fs-16 mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                    @if(!empty($company['tax_id']))
                        <p class="mb-1">IF : <span class="text-dark">{{ $company['tax_id'] }}</span></p>
                    @endif
                    @if(!empty($company['ice']))
                        <p class="mb-1">ICE : <span class="text-dark">{{ $company['ice'] }}</span></p>
                    @endif
                    <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] ?? '' }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])) {{ $company['postal_code'] }}@endif @if(!empty($company['country'])) {{ $company['country'] }}@endif</span></p>
                    @if(!empty($company['phone']))
                        <p class="mb-1">Tél : <span class="text-dark">{{ $company['phone'] }}</span></p>
                    @endif
                    @if(!empty($company['email']))
                        <p class="mb-1">Email : <span class="text-dark">{{ $company['email'] }}</span></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Customer card: billing + shipping left, meta grid right --}}
        <div class="card rounded-0 shadow-none mb-3 border-bottom">
            <div class="card-body p-0">
                <div class="row gx-0">
                    <div class="col-lg-7 d-flex">
                        <div class="p-3 border-end flex-fill">
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
                            <div class="mb-3">
                                <h6 class="fs-16 text-gray-5 mb-2">Payé à :</h6>
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
                    <div class="col-lg-5 d-flex">
                        <div class="row flex-fill gx-0 align-items-center">
                            <div class="col-md-6">
                                <div class="border-end border-bottom text-center p-3">
                                    <span class="d-block mb-1">N° Facture :</span>
                                    <p class="fw-semibold text-primary">{{ $invoice->number }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border-bottom text-center p-3">
                                    <span class="d-block mb-1">Date de facturation :</span>
                                    <p class="fw-semibold text-primary">{{ $invoice->issue_date?->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border-end p-3">
                                    <div class="text-center">
                                        <span class="d-block mb-1">Statut du paiement :</span>
                                        <p class="fw-semibold text-primary">{{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3">
                                    <div class="text-center">
                                        <span class="d-block mb-1">Échéance :</span>
                                        <p class="fw-semibold text-primary">{{ $invoice->due_date?->format('d/m/Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        <div class="py-3 border-top-0 border-bottom mb-3 d-flex align-items-center justify-content-between">
            @if($invoice->total_in_words)
                <p class="text-dark">Arrêtée la présente facture à la somme de :<br>{{ $invoice->total_in_words }}</p>
            @else
                <p></p>
            @endif
            <div class="d-flex align-items-center">
                <span class="border-end-0"></span>
                <span class="text-dark fw-medium border-end-0 border-start-0 text-center me-2"><h6>Total TTC</h6></span>
                <span class="text-dark text-end fw-medium border-start-0"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></span>
            </div>
        </div>

        {{-- Amount paid / due --}}
        @if($invoice->amount_paid > 0)
        <div class="d-flex align-items-center justify-content-between mb-3">
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
        <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
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

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="border-bottom mb-3">
            <p class="bg-primary-subtle p-2 text-dark fs-13 mb-3">{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
        @endif

        {{-- Terms --}}
        @if($invoice->terms)
        <div class="border-bottom mb-3">
            <p class="mb-2 fw-semibold">Conditions générales :</p>
            <p class="text-dark mb-3">{!! nl2br(e($invoice->terms)) !!}</p>
        </div>
        @endif

        {{-- Thank you --}}
        <div class="border-bottom pb-3">
            <p>Merci pour votre confiance</p>
        </div>
    </div>

</body>
</html>
