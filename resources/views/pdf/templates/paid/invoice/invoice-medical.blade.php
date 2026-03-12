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

    {{-- ─── Gradient Top Bar ───────────────────────────────── --}}
    <div class="bg-primary-gradient p-1 mb-3 w-100"></div>

    {{-- ─── Header ─────────────────────────────────────────── --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap pb-3 overflow-hidden">
        <div>
            <h1 class="mb-1 text-primary fs-48">FACTURE</h1>
            <div class="d-inline-block inv-medical position-relative">
                <p class="mb-0 text-white">Adresse : {{ $company['address'] ?? '' }}{{ !empty($company['city']) ? ', ' . $company['city'] : '' }}{{ !empty($company['country']) ? ', ' . $company['country'] : '' }}</p>
            </div>
        </div>
        <div>
            @if($tenant)
                @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                @if($logoPath && file_exists($logoPath))
                    <div class="mb-1"><img src="{{ $logoPath }}" alt="Logo" style="max-height: 50px;"></div>
                @endif
            @endif
            <p class="mb-1 fs-13 text-end">Original pour le destinataire</p>
            <h5 class="fs-20 font-bold text-end">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h5>
        </div>
    </div>

    {{-- ─── Gradient Divider ───────────────────────────────── --}}
    <div class="bg-primary-gradient p-1 mb-3 w-100"></div>

    {{-- ─── Client Information ─────────────────────────────── --}}
    <div class="mb-3">
        <div class="d-block bg-light p-2 ps-3">
            <h5 class="fs-18 fw-bold">Informations client</h5>
        </div>
        <div class="row justify-content-between align-items-start">
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between p-3">
                    <div>
                        <p class="mb-1 text-dark fw-medium">Nom du client :</p>
                        <p class="mb-1 text-dark fw-medium">Email :</p>
                        <p class="mb-1 text-dark fw-medium">Téléphone :</p>
                        <p class="mb-0 text-dark fw-medium">N° Facture :</p>
                    </div>
                    <div class="text-end">
                        <p class="mb-1 text-dark fw-bold">{{ $billTo['name'] ?? '' }}</p>
                        <p class="mb-1 text-dark fw-bold">{{ $billTo['email'] ?? '-' }}</p>
                        <p class="mb-1 text-dark fw-bold">{{ $billTo['phone'] ?? '-' }}</p>
                        <p class="mb-0 text-dark fw-bold">{{ $invoice->number }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-between p-3">
                    <div>
                        <p class="mb-1 text-dark fw-medium">Référence :</p>
                        <p class="mb-1 text-dark fw-medium">Date d'échéance :</p>
                        <p class="mb-0 text-dark fw-medium">Adresse :</p>
                    </div>
                    <div class="text-end">
                        <p class="mb-1 text-dark fw-bold">{{ $invoice->reference_number ?? '-' }}</p>
                        <p class="mb-1 text-dark fw-bold">{{ $invoice->due_date?->format('d/m/Y') }}</p>
                        <p class="mb-0 text-dark fw-bold">{{ $billTo['address'] ?? '' }}{{ !empty($billTo['city']) ? ', ' . $billTo['city'] : '' }}{{ !empty($billTo['country']) ? ', ' . $billTo['country'] : '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Items Table ────────────────────────────────────── --}}
    <div class="table-responsive w-100">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Désignation</th>
                    <th class="text-end">Prix unitaire</th>
                    <th class="text-end">Qté</th>
                    @if($invoice->enable_tax)
                    <th class="text-end">TVA %</th>
                    @endif
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items->sortBy('position') as $index => $item)
                <tr>
                    <td class="text-dark">{{ $index + 1 }}</td>
                    <td class="text-dark">
                        {{ $item->label }}
                        @if($item->description)
                            <br><small>{{ $item->description }}</small>
                        @endif
                    </td>
                    <td class="text-dark text-end">{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-dark text-end">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                    @if($invoice->enable_tax)
                    <td class="text-dark text-end">{{ number_format($item->tax_rate, 2, ',', ' ') }}%</td>
                    @endif
                    <td class="text-dark text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endforeach

                {{-- Charges --}}
                @if($invoice->charges->count())
                    @foreach($invoice->charges->sortBy('position') as $charge)
                    <tr>
                        <td></td>
                        <td class="text-dark">{{ $charge->label }}</td>
                        <td class="text-dark text-end">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                        <td class="text-dark text-end">1</td>
                        @if($invoice->enable_tax)
                        <td class="text-dark text-end">{{ number_format($charge->tax_rate, 2, ',', ' ') }}%</td>
                        @endif
                        <td class="text-dark text-end">{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                    @endforeach
                @endif

                {{-- Subtotal --}}
                <tr>
                    <td colspan="2" class="border-0"></td>
                    <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark fw-medium border-0 text-end">Sous-total HT</td>
                    <td class="text-dark text-end fw-medium border-0">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @if($invoice->discount_total > 0)
                <tr>
                    <td colspan="2"></td>
                    <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark fw-medium text-end">Remise</td>
                    <td class="text-dark text-end fw-medium">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endif
                @if($invoice->tax_total > 0)
                <tr>
                    <td colspan="2"></td>
                    <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark fw-medium text-end">TVA</td>
                    <td class="text-dark text-end fw-medium">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
                @endif
                {{-- Total --}}
                <tr>
                    <td colspan="2" class="text-dark">Total articles / Qté : {{ $invoice->items->count() }} / {{ rtrim(rtrim(number_format($invoice->items->sum('quantity'), 2, ',', ' '), '0'), ',') }}</td>
                    <td colspan="{{ $invoice->enable_tax ? 3 : 2 }}" class="text-dark fw-medium text-end"><h6>Total TTC</h6></td>
                    <td class="text-dark text-end fw-medium"><h6>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</h6></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ─── Total in words ─────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="py-3 border-bottom border-dark">
        <p class="text-dark text-center">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</p>
    </div>
    @endif

    {{-- ─── Bank Details & Terms ───────────────────────────── --}}
    <div class="border-bottom border-dark p-3">
        <div class="row">
            @if(!empty($bank))
            <div class="col-3">
                <h6 class="mb-2">Coordonnées bancaires :</h6>
                @if(!empty($bank['bank_name']))
                <p class="mb-1">Banque : {{ $bank['bank_name'] }}</p>
                @endif
                @if(!empty($bank['account_name']))
                <p class="mb-1">Titulaire : {{ $bank['account_name'] }}</p>
                @endif
                @if(!empty($bank['rib']))
                <p class="mb-1">RIB : {{ $bank['rib'] }}</p>
                @endif
                @if(!empty($bank['iban']))
                <p class="mb-1">IBAN : {{ $bank['iban'] }}</p>
                @endif
                @if(!empty($bank['swift']))
                <p>SWIFT : {{ $bank['swift'] }}</p>
                @endif
            </div>
            @endif
            @if($invoice->terms)
            <div class="{{ !empty($bank) ? 'col-9' : 'col-12' }}">
                <h6 class="mb-2">Conditions générales :</h6>
                <p class="mb-0">{!! nl2br(e($invoice->terms)) !!}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ─── Signature ──────────────────────────────────────── --}}
    @include('pdf.partials.signature')

    {{-- ─── Footer ─────────────────────────────────────────── --}}
    <div class="border-bottom border-dark bg-light py-3">
        <p class="text-center">Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
