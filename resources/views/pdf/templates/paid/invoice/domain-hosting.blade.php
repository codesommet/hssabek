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

    {{-- ─── Header Banner ────────────────────────────────────── --}}
    <div class="mb-3 border-bottom">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-3 mb-3 rounded">
            <div>
                <h6 class="mb-2 text-dark">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                <p class="mb-2">Original pour le destinataire</p>
            </div>
            <div class="invoice-logo">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" height="40" alt="Logo">
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Title ────────────────────────────────────────────── --}}
    <div class="mb-3">
        <h5 class="text-center text-primary mb-3">FACTURE</h5>

        {{-- ─── Meta Info ────────────────────────────────────── --}}
        <div class="mb-3">
            <div class="row row-gap-3">
                <div class="col-md-4 col-lg-4">
                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                        <p class="mb-0">Réf. Client :</p>
                        <p class="text-dark fw-medium">{{ $invoice->customer?->reference ?? '' }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-lg-4">
                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                        <p class="mb-0">Date :</p>
                        <p class="text-dark fw-medium">{{ $invoice->issue_date?->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-lg-4">
                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                        <p class="mb-0">N° Facture :</p>
                        <p class="text-dark fw-medium">{{ $invoice->number }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Bill To ──────────────────────────────────────── --}}
        <div class="mb-3">
            <p class="fs-16 fw-semibold mb-1">Facturé à :</p>
            <p class="text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}
                @if(!empty($billTo['address']))
                    <br> {{ $billTo['address'] }}
                @endif
                @if(!empty($billTo['email'] ?? $invoice->customer?->email ?? ''))
                    <br> {{ $billTo['email'] ?? $invoice->customer?->email ?? '' }}
                @endif
                @if(!empty($billTo['phone'] ?? $invoice->customer?->phone ?? ''))
                    <br> {{ $billTo['phone'] ?? $invoice->customer?->phone ?? '' }}
                @endif
            </p>
        </div>

        {{-- ─── Items Section ────────────────────────────────── --}}
        <div class="mb-3">
            <h6 class="mb-3">Détails de la facture :</h6>
            <div class="table-responsive px-0">
                <table class="table table-nowrap invoice-table2">
                    <thead class="thead-3">
                        <tr>
                            <th class="bg-light text-center">Désignation</th>
                            <th class="bg-light text-center">Prix unit.</th>
                            <th class="bg-light text-center">Qté</th>
                            <th class="bg-light text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="tbody-3">
                        @foreach($invoice->items->sortBy('position') as $index => $item)
                        <tr>
                            <td>
                                <div class="bg-light p-2">
                                    <p class="text-dark">{{ $item->label }}@if($item->description) - {{ $item->description }}@endif</p>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                    <p class="mb-0 text-dark">{{ $currency }}</p>
                                    <p class="text-dark">{{ number_format($item->unit_price, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center bg-light p-2">
                                    <p class="text-dark">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</p>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                    <p class="mb-0 text-dark">{{ $currency }}</p>
                                    <p class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforeach

                        {{-- Charges --}}
                        @if($invoice->charges->count())
                            @foreach($invoice->charges->sortBy('position') as $charge)
                            <tr>
                                <td></td>
                                <td></td>
                                <td>
                                    <p class="text-dark fw-medium text-center">{{ $charge->label }}</p>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                        <p class="mb-0 text-dark fw-medium">{{ $currency }}</p>
                                        <p class="text-dark fw-medium">{{ number_format($charge->amount, 2, ',', ' ') }}</p>
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
                            <td>
                                <p class="text-dark fw-medium text-center">Remise</p>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                    <p class="mb-0 text-dark fw-medium">{{ $currency }}</p>
                                    <p class="text-dark fw-medium">-{{ number_format($invoice->discount_total, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                        </tr>
                        @endif

                        {{-- Subtotal --}}
                        <tr>
                            <td></td>
                            <td></td>
                            <td>
                                <p class="text-dark fw-semibold text-center">Sous-total HT</p>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center bg-dark p-2">
                                    <p class="mb-0 text-white fw-semibold">{{ $currency }}</p>
                                    <p class="text-white fw-semibold">{{ number_format($invoice->subtotal, 2, ',', ' ') }}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ─── Tax & Total Area ─────────────────────────────── --}}
        <div class="mb-3 bg-light p-2">
            <div class="row row-gap-3">
                <div class="col-sm-6 col-md-6">
                    @if($invoice->tax_total > 0)
                    <div class="row align-items-center justify-content-between row-gap-3">
                        <div class="col-sm-6 col-md-6">
                            <p class="text-dark">TVA</p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center bg-white p-2">
                                <p class="mb-0 text-dark">{{ $currency }}</p>
                                <p class="text-dark">{{ number_format($invoice->tax_total, 2, ',', ' ') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="row align-items-center justify-content-between row-gap-3">
                        <div class="col-sm-6 col-md-6">
                            <p class="text-dark">TOTAL TTC</p>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center bg-dark p-2">
                                <p class="mb-0 text-white fw-semibold">{{ $currency }}</p>
                                <p class="text-white fw-semibold">{{ number_format($invoice->total, 2, ',', ' ') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Total in Words ───────────────────────────────── --}}
        @if($invoice->total_in_words)
        <div class="mb-3">
            <div class="py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <p class="fs-13 mb-0">Arrêtée la présente facture à la somme de :</p>
                    <p class="text-dark">{{ $invoice->total_in_words }}</p>
                </div>
                <div class="text-md-end">
                    <p class="text-dark fw-semibold">Total TTC <span class="ms-4">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</span></p>
                </div>
            </div>
        </div>
        @endif

        {{-- ─── Bank Details & Signature ─────────────────────── --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 p-3">
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
            <div class="text-center mb-3">
                <p class="mb-1">Pour {{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
                @include('pdf.partials.signature')
            </div>
        </div>
    </div>

    {{-- ─── Terms ────────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="mb-3">
        <h6 class="mb-2">Conditions générales :</h6>
        {!! nl2br(e($invoice->terms)) !!}
    </div>
    @endif

    {{-- ─── Footer ───────────────────────────────────────────── --}}
    <div class="border border-gray-100 p-3 text-center border-end-0 border-start-0">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
