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
    <div class="pb-3 mb-3 border-bottom border-3 border-light">
        <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-3 rounded">
            <div>
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <img src="{{ $logoPath }}" class="mb-2" alt="Logo" style="max-height: 50px;">
                    @endif
                @endif
            </div>
            <div class="text-end">
                <h6 class="text-primary fw-bold mb-2">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                <span class="text-gray mb-2">Original pour le destinataire</span>
            </div>
        </div>
    </div>

    {{-- ─── Title ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-center mb-3">
        <div>
            <h6 class="mb-1 fs-16">Facture</h6>
        </div>
    </div>

    {{-- ─── Info Boxes ─────────────────────────────────────── --}}
    <div class="row">
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">N° Facture :</div>
                    <span class="text-dark fw-medium">{{ $invoice->number }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">Réf. Client :</div>
                    <span class="text-dark fw-medium">{{ $invoice->customer?->reference ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">Référence :</div>
                    <span class="text-dark fw-medium">{{ $invoice->reference_number ?? '-' }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">Montant dû :</div>
                    <span class="text-dark fw-medium">{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">Date d'échéance :</div>
                    <span class="text-dark fw-medium">{{ $invoice->due_date?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mb-3 border-light">
                <div class="d-flex align-items-center justify-content-between bg-light flex-wrap p-2 rounded">
                    <div class="text-gray fw-normal">Date d'émission :</div>
                    <span class="text-dark fw-medium">{{ $invoice->issue_date?->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Bill To ────────────────────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <p class="mb-1 fw-bold d-block">Facturé à :</p>
            <span class="mb-1 fw-noraml text-dark d-block">{{ $billTo['name'] ?? '' }}</span>
            @if(!empty($billTo['address']))
            <span class="mb-1 fw-noraml text-dark d-block">{{ $billTo['address'] }}{{ !empty($billTo['city']) ? ', ' . $billTo['city'] : '' }}{{ !empty($billTo['postal_code']) ? ', ' . $billTo['postal_code'] : '' }}{{ !empty($billTo['country']) ? ', ' . $billTo['country'] : '' }}</span>
            @endif
            @if(!empty($billTo['email']))
            <span class="mb-1 fw-noraml text-dark d-block">{{ $billTo['email'] }}</span>
            @endif
            @if(!empty($billTo['phone']))
            <span class="mb-1 fw-noraml text-dark d-block">{{ $billTo['phone'] }}</span>
            @endif
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <table class="table">
                <thead class="thead-light border border-gray border-start-0 border-end-0">
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
                    <tr class="border-gray">
                        <td class="text-dark">{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-dark">{{ $item->label }}</span>
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
                        <tr class="border-gray">
                            <td></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-dark">{{ $charge->label }}</span>
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
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── Notes & Totals ─────────────────────────────────── --}}
    <div class="row">
        <div class="col-md-9">
            @if($invoice->notes)
            <p class="mb-1 fw-normal">Note :</p>
            <span class="mb-1 fw-noraml text-dark">{!! nl2br(e($invoice->notes)) !!}</span>
            @endif
        </div>
        <div class="col-md-3">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <p class="fs-14 fw-medium mb-0 text-dark">Sous-total HT</p>
                    <p class="fs-14 fw-medium mb-0 text-dark">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</p>
                </div>
                @if($invoice->discount_total > 0)
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <p class="fs-14 fw-medium mb-0 text-dark">Remise</p>
                    <p class="fs-14 fw-medium mb-0 text-dark">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</p>
                </div>
                @endif
                @if($invoice->tax_total > 0)
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <p class="fs-14 fw-medium mb-0 text-dark">TVA</p>
                    <p class="fs-14 fw-medium mb-0 text-dark">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── Total Bar + Words ──────────────────────────────── --}}
    <div class="row mb-2 border-top border-bottom border-3 border-light">
        <div class="col-md-9 pb-3">
            @if($invoice->total_in_words)
            <p class="mb-1 fw-normal pt-3">Arrêtée la présente facture à la somme de :</p>
            <span class="mb-1 fw-noraml text-dark">{{ $invoice->total_in_words }}</span>
            @endif
        </div>
        <div class="col-md-3">
            <div class="mb-3 pt-4 align-items-center">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fs-18 fw-bold">Total TTC</h6>
                    <h6 class="fs-18 fw-bold">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Bank Details & Signature ───────────────────────── --}}
    <div class="row d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
        <div class="col-md-9">
            @if(!empty($bank))
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
            @endif
        </div>
        <div class="col-md-3">
            <div class="text-end mb-3">
                @include('pdf.partials.signature')
            </div>
        </div>
        @if($invoice->terms)
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div>
                <h6 class="mb-2 fs-16 text-center">Conditions générales :</h6>
                <p class="mb-0 fs-13">{!! nl2br(e($invoice->terms)) !!}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="row border-bottom pb-3 text-center">
        <div class="col-md-12">
            <p class="text-gray">Merci pour votre confiance</p>
        </div>
    </div>

</div>
</body>
</html>
