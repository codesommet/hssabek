<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    @include('pdf.partials.theme-css')
</head>
<body>
<div class="invoice-wrapper">

    @php
        $company = $settings?->company_settings ?? [];
        $billTo = $invoice->bill_to_snapshot ?? [];
        $billFrom = $invoice->bill_from_snapshot ?? [];
        $bank = $invoice->bank_details_snapshot ?? [];
        $currency = $invoice->currency ?? 'MAD';
    @endphp

    {{-- ─── Header Row ─────────────────────────────────────── --}}
    <div class="row justify-content-between border-top border-bottom row-gap-3 flex-wrap py-4 mb-3">
        <div class="col-sm-4 col-md-4 col-lg-4 p-0">
            <div class="invoice-logo">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-3 image-fluid" alt="Logo" style="max-height: 50px;">
                    @endif
                @endif
                <p class="mb-3">Original pour le destinataire</p>
                <h3 class="text-primary">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h3>
            </div>
        </div>
        <div class="col-sm-8 col-md-8 col-lg-8 p-0">
            <div class="ribbon-hotel">
                <span class="text-center text-white">Adresse : {{ $company['address'] ?? '' }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['postal_code']) ? ' ' . $company['postal_code'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</span>
            </div>
        </div>
    </div>

    {{-- ─── Meta Info + Bill To ────────────────────────────── --}}
    <div class="row mb-3 justify-content-between row-gap-3">
        <div class="col-lg-9 d-flex ps-0">
            <div class="table-responsive px-0 d-flex flex-fill">
                <table class="table table-nowrap invoice-table">
                    <tbody>
                        <tr>
                            <td>N° Facture</td>
                            <td><p class="text-dark fw-medium">{{ $invoice->number }}</p></td>
                            <td>Date d'émission</td>
                            <td><p class="text-dark fw-medium">{{ $invoice->issue_date?->format('d/m/Y') }}</p></td>
                            <td>Référence</td>
                            <td><p class="text-dark fw-medium">{{ $invoice->reference_number ?? '-' }}</p></td>
                        </tr>
                        <tr>
                            <td>Date d'échéance</td>
                            <td><p class="text-dark fw-medium">{{ $invoice->due_date?->format('d/m/Y') }}</p></td>
                            <td>Statut</td>
                            <td><p class="text-dark fw-medium">{{ $invoice->status }}</p></td>
                            <td>Devise</td>
                            <td><p class="text-dark fw-medium">{{ $currency }}</p></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-3 d-flex px-0">
            <div class="border rounded p-3 flex-fill">
                <p class="fw-semibold fs-16 mb-2">Facturé à :</p>
                <p class="text-dark fs-13">
                    {{ $billTo['name'] ?? '' }}<br>
                    @if(!empty($billTo['address'])){{ $billTo['address'] }},<br>@endif
                    @if(!empty($billTo['city'])){{ $billTo['city'] }},@endif
                    @if(!empty($billTo['postal_code'])) {{ $billTo['postal_code'] }},@endif
                    @if(!empty($billTo['country']))<br>{{ $billTo['country'] }}@endif
                </p>
            </div>
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <div class="row mb-3">
        <h6 class="mb-3">Détails des prestations :</h6>
        <div class="table-responsive px-0">
            <table class="table table-nowrap invoice-table2">
                <thead class="thead-2">
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th>Prix unitaire</th>
                        <th>Qté</th>
                        @if($invoice->enable_tax)
                        <th>TVA %</th>
                        @endif
                        <th>Remise</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody class="tbody-2">
                    @foreach($invoice->items->sortBy('position') as $index => $item)
                    <tr class="{{ $index % 2 == 0 ? 'odd' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $item->label }}
                            @if($item->description)
                                <br><small>{{ $item->description }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0">{{ $currency }}</p>
                                <p>{{ number_format($item->unit_price, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                        @if($invoice->enable_tax)
                        <td class="text-center">{{ number_format($item->tax_rate, 2, ',', ' ') }}%</td>
                        @endif
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0">{{ $currency }}</p>
                                <p>{{ number_format($item->discount_amount, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0">{{ $currency }}</p>
                                <p>{{ number_format($item->line_subtotal, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Charges --}}
                    @if($invoice->charges->count())
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <tr>
                            <td></td>
                            <td>{{ $charge->label }}</td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0">{{ $currency }}</p>
                                    <p>{{ number_format($charge->amount, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                            <td class="text-center">1</td>
                            @if($invoice->enable_tax)
                            <td class="text-center">{{ number_format($charge->tax_rate, 2, ',', ' ') }}%</td>
                            @endif
                            <td></td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center">
                                    <p class="mb-0">{{ $currency }}</p>
                                    <p>{{ number_format($charge->amount, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @endif

                    {{-- Discount --}}
                    @if($invoice->discount_total > 0)
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        @if($invoice->enable_tax)<td></td>@endif
                        <td>
                            <p class="fw-medium">Remise totale</p>
                        </td>
                        <td class="bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0 fw-medium">{{ $currency }}</p>
                                <p class="fw-medium">{{ number_format($invoice->discount_total, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Subtotal --}}
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        @if($invoice->enable_tax)<td></td>@endif
                        <td>
                            <p class="fw-medium">Sous-total HT</p>
                        </td>
                        <td class="bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0 fw-medium">{{ $currency }}</p>
                                <p class="fw-medium">{{ number_format($invoice->subtotal, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                    </tr>

                    {{-- Tax --}}
                    @if($invoice->tax_total > 0)
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        @if($invoice->enable_tax)<td></td>@endif
                        <td>
                            <p class="fw-medium">TVA</p>
                        </td>
                        <td class="bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0 fw-medium">{{ $currency }}</p>
                                <p class="fw-medium">{{ number_format($invoice->tax_total, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Total TTC --}}
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        @if($invoice->enable_tax)<td></td>@endif
                        <td>
                            <p class="fw-semibold fs-16">Total TTC</p>
                        </td>
                        <td class="bg-dark">
                            <div class="d-flex justify-content-between align-items-center text-white">
                                <p class="mb-0 fw-semibold">{{ $currency }}</p>
                                <p class="fw-semibold">{{ number_format($invoice->total, 2, ',', ' ') }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Total in words ─────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="mb-3">
        <p>Arrêtée la présente facture à la somme de : <span class="text-dark fw-medium">{{ $invoice->total_in_words }}</span></p>
    </div>
    @endif

    {{-- ─── Bank Details ───────────────────────────────────── --}}
    @if(!empty($bank))
    <div class="mb-3">
        <h6 class="mb-2">Coordonnées bancaires :</h6>
        @if(!empty($bank['bank_name']))
        <p class="mb-0">Banque : <span class="text-dark">{{ $bank['bank_name'] }}</span></p>
        @endif
        @if(!empty($bank['account_name']))
        <p class="mb-0">Titulaire : <span class="text-dark">{{ $bank['account_name'] }}</span></p>
        @endif
        @if(!empty($bank['rib']))
        <p class="mb-0">RIB : <span class="text-dark">{{ $bank['rib'] }}</span></p>
        @endif
        @if(!empty($bank['iban']))
        <p class="mb-0">IBAN : <span class="text-dark">{{ $bank['iban'] }}</span></p>
        @endif
        @if(!empty($bank['swift']))
        <p class="mb-0">SWIFT : <span class="text-dark">{{ $bank['swift'] }}</span></p>
        @endif
    </div>
    @endif

    {{-- ─── Terms ──────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="mb-3">
        <h6 class="mb-2">Conditions générales :</h6>
        <p class="mb-0">{!! nl2br(e($invoice->terms)) !!}</p>
    </div>
    @endif

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer Banner ──────────────────────────────────── --}}
    <div class="border-bottom">
        <div class="bg-light border border-dark border-2 p-3 text-center border-end-0 border-start-0 mb-3">
            <p>Merci pour votre confiance</p>
        </div>
    </div>

</div>
</body>
</html>
