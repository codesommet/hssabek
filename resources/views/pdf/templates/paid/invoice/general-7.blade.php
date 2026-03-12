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
        {{-- Gradient header bar: "Facture" + logo --}}
        <div class="border-bottom mb-3 pb-3">
            <div class="bg-gradient-primary d-flex align-items-center justify-content-between rounded p-3">
                <h5>Facture</h5>
                <div>
                    @if($tenant)
                        @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                        @if($logoPath && file_exists($logoPath))
                            <img src="{{ $logoPath }}" height="50" alt="logo">
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Company info + invoice meta --}}
        <div class="border-bottom mb-3">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <div>
                            <p class="fw-semibold text-dark mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
                            <p class="text-dark">Adresse : {{ $company['address'] ?? '' }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])) {{ $company['postal_code'] }}@endif @if(!empty($company['country'])) {{ $company['country'] }}@endif</p>
                            @if(!empty($company['phone']))
                                <p class="text-dark mb-1">Tél : {{ $company['phone'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-lg-end mb-2">
                        <p class="mb-1">N° Facture : <span class="text-dark">{{ $invoice->number }}</span></p>
                        <p class="mb-1">Date de facturation : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
                        @if($invoice->reference_number)
                            <p class="mb-1">Réf : <span class="text-dark">{{ $invoice->reference_number }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Customer information header --}}
        <div class="p-3 pt-0">
            <p>Informations client</p>
        </div>

        {{-- Billing, Shipping, Due date/Status --}}
        <div class="row">
            <div class="col-lg-4">
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
            <div class="col-lg-4">
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
            <div class="col-lg-4">
                <div class="mb-3 bg-light p-3 rounded">
                    <span class="d-block mb-1">Échéance</span>
                    <h6 class="mb-2 fs-16 fw-semibold">{{ $invoice->due_date?->format('d/m/Y') }}</h6>
                    <span class="d-block mb-1">Statut du paiement</span>
                    <p class="text-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'info') }} fs-16 fw-semibold">{{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}</p>
                </div>
            </div>
        </div>

        {{-- Items table --}}
        <div class="table-responsive mb-3">
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

                    {{-- Total TTC row --}}
                    <tr>
                        <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="text-dark"></td>
                        <td class="text-dark fw-medium"><h6>Total TTC</h6></td>
                        <td class="text-dark text-end fw-medium"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>

                    {{-- Amount paid / due --}}
                    @if($invoice->amount_paid > 0)
                    <tr>
                        <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="text-dark"></td>
                        <td class="text-dark fw-medium"><h6 class="fs-14">Montant payé</h6></td>
                        <td class="text-dark text-end fw-medium"><h6 class="fs-14">{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                    <tr>
                        <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="text-dark"></td>
                        <td class="text-dark fw-medium"><h6 class="fs-14">Solde dû</h6></td>
                        <td class="text-dark text-end fw-medium"><h6 class="fs-14">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                    @endif

                    {{-- Total in words --}}
                    @if($invoice->total_in_words)
                    <tr>
                        <td colspan="{{ $invoice->enable_tax ? 6 : 5 }}" class="text-end">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

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
        <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom">
            <div class="d-flex align-items-start mb-3">
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
                    <p class="mb-2">Conditions générales :</p>
                    <p class="mb-1 text-dark">{!! nl2br(e($invoice->terms)) !!}</p>
                @endif
            </div>
        </div>

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="border-bottom mb-3">
            <p class="bg-primary-subtle p-2 text-dark fs-13 mb-3">{!! nl2br(e($invoice->notes)) !!}</p>
        </div>
        @endif

        {{-- Thank you --}}
        <div class="border-bottom pb-3">
            <p>Merci pour votre confiance</p>
        </div>

        {{-- Signature --}}
        @include('pdf.partials.signature')
    </div>

</body>
</html>
