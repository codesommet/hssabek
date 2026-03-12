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

    {{-- ─── Header Banner ──────────────────────────────────── --}}
    <div class="mb-3">
        <div class="d-flex align-items-center justify-content-between bg-primary flex-wrap p-3 rounded">
            <div>
                <p class="text-white mb-2">Original pour le destinataire</p>
                <h6 class="mb-0 text-white">Facture</h6>
            </div>
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo" style="max-height: 50px;">
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Company Info ───────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h6 class="mb-1 fs-16">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
            <p>{{ $company['address'] ?? '' }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</p>
        </div>
        <div class="text-end">
            @if($invoice->notes)
            <h6 class="mb-1 fs-18 fw-medium">Info :</h6>
            <p class="invoice-info">{{ $invoice->notes }}</p>
            @endif
        </div>
    </div>

    {{-- ─── Date Row ───────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="bg-light">
                <div class="d-flex justify-content-center align-items-center p-2">
                    <span class="me-3">Date :</span>
                    <span class="text-dark fw-medium">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-light">
                <div class="d-flex justify-content-center align-items-center p-2">
                    <span class="me-3">Échéance :</span>
                    <span class="text-dark fw-medium">{{ $invoice->due_date?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-light">
                <div class="d-flex justify-content-center align-items-center p-2">
                    <span class="me-3">N° Facture :</span>
                    <span class="text-dark fw-medium">{{ $invoice->number }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Client Info Ribbon ─────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="ribbon-tittle">
                <div class="ribbon-text">
                    <span class="text-white">Informations client</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Client Info ────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center justify-content-between bg-light p-3">
                <div class="d-flex flex-column">
                    <span class="mb-1">Nom :</span>
                    <span class="mb-1">Email :</span>
                    <span class="mb-1">Téléphone :</span>
                    <span>Adresse :</span>
                </div>
                <div class="d-flex flex-column text-end">
                    <span class="text-dark mb-1">{{ $billTo['name'] ?? '' }}</span>
                    <span class="text-dark mb-1">{{ $billTo['email'] ?? '-' }}</span>
                    <span class="text-dark mb-1">{{ $billTo['phone'] ?? '-' }}</span>
                    <span class="text-dark">{{ $billTo['address'] ?? '' }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center justify-content-between bg-light p-3">
                <div class="d-flex flex-column">
                    <span class="mb-1">Réf. Client :</span>
                    <span class="mb-1">N° Facture :</span>
                    <span class="mb-1">Référence :</span>
                    <span>Date d'émission :</span>
                </div>
                <div class="d-flex flex-column text-end">
                    <span class="text-dark mb-1">{{ $invoice->customer?->reference ?? '-' }}</span>
                    <span class="text-dark mb-1">{{ $invoice->number }}</span>
                    <span class="text-dark mb-1">{{ $invoice->reference_number ?? '-' }}</span>
                    <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <h6 class="mb-3 fs-16">Détails de la facture</h6>
    <div class="row mb-3">
        <div class="col-md-12">
            <table class="table table-nowrap invoice-tables">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Désignation</th>
                        <th>Prix unitaire</th>
                        <th>Qté</th>
                        @if($invoice->enable_tax)
                        <th>TVA %</th>
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
                        <td><span class="text-dark">{{ number_format($item->tax_rate, 2, ',', ' ') }}%</span></td>
                        @endif
                        <td class="text-end"><span class="text-dark">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</span></td>
                    </tr>
                    @endforeach

                    {{-- Charges --}}
                    @if($invoice->charges->count())
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <tr>
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
                            <td class="text-end"><span class="text-dark">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</span></td>
                        </tr>
                        @endforeach
                    @endif

                    {{-- Subtotal --}}
                    <tr>
                        <td colspan="2" class="border-0"></td>
                        <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark text-end fw-medium border-0">Sous-total HT</td>
                        <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @if($invoice->discount_total > 0)
                    <tr>
                        <td colspan="2" class="border-bottom-transparent"></td>
                        <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark text-end fw-medium border-bottom-transparent">Remise</td>
                        <td class="text-dark text-end fw-medium border-bottom-transparent">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif
                    @if($invoice->tax_total > 0)
                    <tr>
                        <td colspan="2" class="border-bottom-transparent"></td>
                        <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark text-end fw-medium border-bottom-transparent">TVA</td>
                        <td class="text-dark text-end fw-medium border-bottom-transparent">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endif
                    {{-- Total --}}
                    <tr>
                        <td colspan="2" class="text-dark border-0 bg-light">Total articles / Qté : {{ $invoice->items->count() }} / {{ rtrim(rtrim(number_format($invoice->items->sum('quantity'), 2, ',', ' '), '0'), ',') }}</td>
                        <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark bg-light border-0 text-end fw-medium">
                            <h6>Total TTC</h6></td>
                        <td class="text-dark bg-light text-end border-0 fw-medium">
                            <h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                    {{-- Total in words + Amount payable --}}
                    @if($invoice->total_in_words)
                    <tr>
                        <td colspan="2" class="border-bottom-transparent">
                            <div class="d-flex flex-column">
                                <span>Arrêtée la présente facture à la somme de :</span>
                                <span class="text-dark mb-1">{{ $invoice->total_in_words }}</span>
                            </div>
                        </td>
                        <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark text-end border-bottom-transparent fw-medium">
                            <h6>Montant à payer</h6></td>
                        <td class="text-dark border-bottom-transparent text-end fw-medium">
                            <h6>{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</h6></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Bank Details ───────────────────────────────────── --}}
    @if(!empty($bank))
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 p-3">
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

    {{-- ─── Terms ──────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="border-bottom mb-3 p-3">
        <h6 class="mb-2">Conditions générales :</h6>
        <p class="mb-0">{!! nl2br(e($invoice->terms)) !!}</p>
    </div>
    @endif

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="border-bottom text-center pb-3">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
