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
    <div class="pb-3 mb-3 border-bottom">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-3 rounded">
            <div>
                <h6 class="mb-2">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                <p>Original pour le destinataire</p>
            </div>
            <div class="text-end">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" height="40" alt="Logo">
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Centered Title ───────────────────────────────────── --}}
    <h5 class="text-primary text-center mb-3">FACTURE</h5>

    {{-- ─── Meta Info ────────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between bg-light p-2">
                <span>Réf. Client :</span>
                <span class="text-dark fw-medium">{{ $invoice->customer?->reference ?? '' }}</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between bg-light p-2">
                <span>Date :</span>
                <span class="text-dark fw-medium">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-center justify-content-between bg-light p-2">
                <span>N° Facture :</span>
                <span class="text-dark fw-medium">{{ $invoice->number }}</span>
            </div>
        </div>
    </div>

    {{-- ─── Bill To / Pay To ─────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h6 class="mb-8 text-gray-5 fs-16">Facturé à :</h6>
            <p class="text-dark mb-0">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
            @if(!empty($billTo['address']))
                <p class="text-dark mb-0">{{ $billTo['address'] }}</p>
            @endif
            @if(!empty($billTo['email'] ?? $invoice->customer?->email ?? ''))
                <p class="text-dark mb-0">{{ $billTo['email'] ?? $invoice->customer?->email ?? '' }}</p>
            @endif
            @if(!empty($billTo['phone'] ?? $invoice->customer?->phone ?? ''))
                <p class="text-dark">{{ $billTo['phone'] ?? $invoice->customer?->phone ?? '' }}</p>
            @endif
        </div>
        <div>
            <h6 class="mb-8 text-gray-5 fs-16">Payé à :</h6>
            <p class="text-dark mb-0">{{ $billFrom['company_name'] ?? $company['company_name'] ?? $tenant?->name ?? '' }}</p>
            @if(!empty($billFrom['address'] ?? $company['address'] ?? ''))
                <p class="text-dark mb-0">{{ $billFrom['address'] ?? $company['address'] ?? '' }}</p>
            @endif
            @if(!empty($billFrom['email'] ?? $company['email'] ?? ''))
                <p class="text-dark mb-0">{{ $billFrom['email'] ?? $company['email'] ?? '' }}</p>
            @endif
            @if(!empty($billFrom['phone'] ?? $company['phone'] ?? ''))
                <p class="text-dark mb-0">{{ $billFrom['phone'] ?? $company['phone'] ?? '' }}</p>
            @endif
        </div>
    </div>

    {{-- ─── Info Boxes (Client / Payment) ────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="bg-light p-3">
                <h6 class="mb-3">Informations client</h6>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        <span class="mb-1">Nom du client</span>
                        <span class="mb-1">N° Facture</span>
                        <span class="mb-1">Date</span>
                        <span>Référence</span>
                    </div>
                    <div class="d-flex flex-column text-end">
                        <span class="text-dark mb-1">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</span>
                        <span class="text-dark mb-1">{{ $invoice->number }}</span>
                        <span class="text-dark mb-1">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
                        <span class="text-dark">{{ $invoice->reference_number ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="bg-light p-3">
                <h6 class="mb-3">Informations de paiement</h6>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex flex-column">
                        @if(!empty($bank['bank_name']))
                            <span class="mb-1">Banque</span>
                        @endif
                        <span class="mb-1">Date</span>
                        @if(!empty($bank['rib']))
                            <span class="mb-1">RIB</span>
                        @endif
                        <span>Montant total</span>
                    </div>
                    <div class="d-flex flex-column text-end">
                        @if(!empty($bank['bank_name']))
                            <span class="text-dark mb-1">{{ $bank['bank_name'] }}</span>
                        @endif
                        <span class="text-dark mb-1">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
                        @if(!empty($bank['rib']))
                            <span class="text-dark mb-1">{{ $bank['rib'] }}</span>
                        @endif
                        <span class="text-dark">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Items Table ──────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <table class="table table-nowrap invoice-tables">
                <thead class="thead-primary">
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th>Prix unit.</th>
                        <th>Qté</th>
                        @if($invoice->enable_tax)
                            <th>TVA</th>
                        @endif
                        <th class="text-end">Total</th>
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
                        @if($invoice->enable_tax)
                            <td><span class="text-dark">{{ number_format($item->tax_rate, 2) }}%</span></td>
                        @endif
                        <td class="text-end"><span class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</span></td>
                    </tr>
                    @endforeach

                    {{-- Charges --}}
                    @if($invoice->charges->count())
                        @php $colsBefore = $invoice->enable_tax ? 3 : 2; @endphp
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <tr>
                            <td colspan="{{ $colsBefore }}" class="border-0"></td>
                            <td colspan="2" class="text-dark text-end fw-medium border-0">{{ $charge->label }}</td>
                            <td class="text-dark text-end fw-medium border-0">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                        @endforeach
                    @endif

                    {{-- Subtotal --}}
                    @php $colsBefore = $invoice->enable_tax ? 3 : 2; @endphp
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-0"></td>
                        <td colspan="2" class="text-dark text-end fw-medium border-0">Sous-total HT</td>
                        <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @if($invoice->tax_total > 0)
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-bottom-transparent"></td>
                        <td colspan="2" class="text-dark text-end fw-medium border-bottom-transparent">TVA</td>
                        <td class="text-dark text-end fw-medium border-bottom-transparent">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif
                    @if($invoice->discount_total > 0)
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-bottom-transparent"></td>
                        <td colspan="2" class="text-dark text-end fw-medium border-bottom-transparent">Remise</td>
                        <td class="text-dark text-end fw-medium border-bottom-transparent">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif

                    {{-- Total TTC --}}
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="text-dark border-0 bg-light">{{ $invoice->items->count() }} article(s)</td>
                        <td colspan="2" class="text-dark bg-light border-0 text-end fw-medium"><h6 class="fs-16">Total TTC</h6></td>
                        <td class="text-dark bg-light text-end border-0 fw-medium"><h6 class="fs-16">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>

                    {{-- Total in words --}}
                    @if($invoice->total_in_words)
                    <tr>
                        <td colspan="{{ $colsBefore }}" class="border-bottom-transparent">
                            <div class="d-flex flex-column">
                                <span>Arrêtée la présente facture à la somme de :</span>
                                <span class="text-dark mb-1">{{ $invoice->total_in_words }}</span>
                            </div>
                        </td>
                        <td colspan="2" class="text-dark text-end border-bottom-transparent fw-medium"><h6>Total TTC</h6></td>
                        <td class="text-dark border-bottom-transparent text-end fw-medium"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Bank Details & Signature ─────────────────────────── --}}
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

    {{-- ─── Terms ────────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="border-bottom mb-3 p-3">
        <h6 class="mb-2">Conditions générales :</h6>
        {!! nl2br(e($invoice->terms)) !!}
    </div>
    @endif

    {{-- ─── Footer ───────────────────────────────────────────── --}}
    <div class="border-bottom text-center pb-3">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
