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
    <div class="d-flex align-items-center justify-content-between border-bottom flex-wrap row-gap-3 mb-3 pb-3">
        <div>
            <h5 class="mb-2">FACTURE</h5>
            <div>
                <h6 class="mb-1">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                <div>
                    @if(!empty($company['address']))
                    <p class="mb-1">Adresse : <span class="text-dark">{{ $company['address'] }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</span></p>
                    @endif
                </div>
            </div>
        </div>
        <div>
            @if($tenant)
                @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                @if($logoPath && file_exists($logoPath))
                    <div class="mb-1"><img src="{{ $logoPath }}" alt="Logo" style="max-height: 50px;"></div>
                @endif
            @endif
            <p class="mb-1 text-end">Original pour le destinataire</p>
            <p class="mb-1 text-end">N° Facture : <span class="text-dark">{{ $invoice->number }}</span></p>
            <p class="mb-1 text-end">Date : <span class="text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</span></p>
        </div>
    </div>

    {{-- ─── Bill To ────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <h6 class="fs-16 text-gray mb-2">Facturé à :</h6>
                        <div>
                            <p class="mb-0 fs-13 text-dark">
                                {{ $billTo['name'] ?? '' }}
                                @if(!empty($billTo['address']))<br>{{ $billTo['address'] }}{{ !empty($billTo['city']) ? ', ' . $billTo['city'] : '' }}{{ !empty($billTo['postal_code']) ? ', ' . $billTo['postal_code'] : '' }}{{ !empty($billTo['country']) ? ', ' . $billTo['country'] : '' }}@endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <div class="table-responsive">
        <table class="table">
            <thead class="thead-primary">
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
                    <td class="text-dark">{{ $index + 1 }}</td>
                    <td>
                        <div>
                            <p class="text-dark mb-0">{{ $item->label }}</p>
                            @if($item->description)
                            <span class="d-block">{{ $item->description }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-dark">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                    @if($invoice->enable_tax)
                    <td>{{ number_format($item->tax_rate, 2, ',', ' ') }}%</td>
                    @endif
                    <td class="text-dark text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endforeach

                {{-- Charges --}}
                @if($invoice->charges->count())
                    @foreach($invoice->charges->sortBy('position') as $charge)
                    <tr>
                        <td></td>
                        <td>
                            <div>
                                <p class="text-dark mb-0">{{ $charge->label }}</p>
                            </div>
                        </td>
                        <td class="text-dark">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                        <td>1</td>
                        @if($invoice->enable_tax)
                        <td>{{ number_format($charge->tax_rate, 2, ',', ' ') }}%</td>
                        @endif
                        <td class="text-dark text-end">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endforeach
                @endif

                {{-- Subtotal --}}
                <tr>
                    <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="border-0"></td>
                    <td class="text-dark fw-medium border-0">Sous-total HT</td>
                    <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @if($invoice->discount_total > 0)
                <tr>
                    <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="border-0"></td>
                    <td class="text-dark fw-medium border-0">Remise</td>
                    <td class="text-dark text-end fw-medium border-0">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endif
                @if($invoice->tax_total > 0)
                <tr>
                    <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="border-0"></td>
                    <td class="text-dark fw-medium border-0">TVA</td>
                    <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endif
                {{-- Total --}}
                <tr>
                    <td colspan="{{ $invoice->enable_tax ? 4 : 3 }}" class="text-dark border-0">Total articles / Qté : {{ $invoice->items->count() }} / {{ rtrim(rtrim(number_format($invoice->items->sum('quantity'), 2, ',', ' '), '0'), ',') }}</td>
                    <td class="text-dark fw-medium border-0"><h6>Total TTC</h6></td>
                    <td class="text-dark text-end fw-medium border-0"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ─── Total in words ─────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="py-3 border-top border-bottom mb-3 d-flex align-items-center justify-content-center">
        <p class="text-dark">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</p>
    </div>
    @endif

    {{-- ─── Bank Details & Terms ───────────────────────────── --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap border-bottom mb-3">
        @if(!empty($bank))
        <div class="mb-3">
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
        </div>
        @endif
        @if($invoice->terms)
        <div class="mb-3">
            <h6 class="mb-2">Conditions générales :</h6>
            <p class="mb-0">{!! nl2br(e($invoice->terms)) !!}</p>
        </div>
        @endif
    </div>

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="border-bottom text-center text-white bg-primary p-2">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
