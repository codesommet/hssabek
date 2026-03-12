<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .page { padding: 30px 40px; }

        /* Header */
        .header-row { width: 100%; margin-bottom: 15px; }
        .header-row td { vertical-align: top; }
        .doc-title { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .company-name { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
        .company-detail { font-size: 10px; color: #555; line-height: 1.6; }
        .doc-meta { font-size: 10px; color: #555; text-align: right; line-height: 1.8; }

        /* Bill to + Total box */
        .billto-row { width: 100%; margin-bottom: 12px; }
        .billto-row td { vertical-align: top; }
        .billto-label { font-size: 10px; color: #555; margin-bottom: 2px; }
        .billto-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .total-box { background: #f5f5f5; padding: 12px; text-align: center; }
        .total-box .label { font-size: 10px; color: #888; margin-bottom: 3px; }
        .total-box .amount { font-size: 14px; font-weight: bold; color: #333; }

        /* Customer details grid */
        .details-grid { width: 100%; margin-bottom: 12px; }
        .details-grid td { padding: 3px 8px; font-size: 10px; }
        .details-grid .label { color: #555; }
        .details-grid .value { text-align: right; color: #333; }

        /* Items table — dark header with thick borders */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .items-table th { background: #333; color: #fff; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; text-align: left; border-top: 3px solid #333; border-bottom: 3px solid #333; }
        .items-table td { padding: 7px 10px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .items-table tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-end { text-align: right; }

        /* Subtotals */
        .subtotals-table { width: 250px; margin-left: auto; margin-bottom: 5px; }
        .subtotals-table td { padding: 3px 8px; font-size: 11px; font-weight: bold; }

        /* Total bar */
        .total-bar { width: 100%; border-top: 3px solid #888; border-bottom: 3px solid #888; padding: 8px 0; margin-bottom: 10px; }
        .total-bar table { width: 100%; }
        .total-bar .count { font-size: 11px; color: #333; }
        .total-bar .total-label { font-size: 14px; font-weight: bold; text-align: right; }
        .total-bar .total-value { font-size: 14px; font-weight: bold; text-align: right; }

        /* Words */
        .words-block { border-bottom: 3px solid #888; padding: 8px 0; margin-bottom: 12px; font-size: 10px; text-align: center; }

        /* Bank & Signature */
        .bank-block { font-size: 10px; color: #555; }
        .bank-block h6 { color: #333; font-size: 11px; font-weight: bold; margin-bottom: 4px; }
        .bank-block p { margin-bottom: 2px; }
        .signature-block { text-align: center; }
        .signature-block p { font-size: 10px; margin-bottom: 4px; }

        /* Terms */
        .terms-block { border-bottom: 1px solid #dee2e6; padding: 10px 0; margin-bottom: 10px; font-size: 10px; }
        .terms-block h6 { font-size: 11px; font-weight: bold; margin-bottom: 4px; }

        /* Footer */
        .footer-block { text-align: center; padding-bottom: 10px; font-size: 10px; color: #555; border-bottom: 1px solid #dee2e6; }
    </style>
</head>
<body>
<div class="page">

    @php
        $company = $settings?->company_settings ?? [];
        $billTo = $invoice->bill_to_snapshot ?? [];
        $billFrom = $invoice->bill_from_snapshot ?? [];
        $bank = $invoice->bank_details_snapshot ?? [];
        $currency = $company['currency'] ?? 'MAD';
    @endphp

    {{-- ─── Header ───────────────────────────────────────────── --}}
    <table class="header-row">
        <tr>
            <td style="width: 55%;">
                <div class="doc-title">FACTURE</div>
                <div class="company-name">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</div>
                <div class="company-detail">
                    @if(!empty($company['tax_id'])) IF : {{ $company['tax_id'] }}<br> @endif
                    @if(!empty($company['address'])) Adresse : {{ $company['address'] }}<br> @endif
                    @if(!empty($company['phone'])) Tél : {{ $company['phone'] }} @endif
                </div>
            </td>
            <td style="width: 45%;">
                @if($tenant)
                    @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                    @if($logoPath && file_exists($logoPath))
                        <div style="text-align: right; margin-bottom: 5px;">
                            <img src="{{ $logoPath }}" height="40" alt="Logo">
                        </div>
                    @endif
                @endif
                <div class="doc-meta">
                    Original pour le destinataire<br>
                    Date : <strong>{{ $invoice->issue_date?->format('d/m/Y') }}</strong><br>
                    N° Facture : <strong>{{ $invoice->number }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── Bill To + Total Box ──────────────────────────────── --}}
    <table class="billto-row">
        <tr>
            <td style="width: 70%;">
                <div class="billto-label">Facturé à :</div>
                <div class="billto-name">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</div>
            </td>
            <td style="width: 30%;">
                <div class="total-box">
                    <div class="label">Total TTC</div>
                    <div class="amount">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── Customer Details Grid ────────────────────────────── --}}
    <table class="details-grid">
        <tr>
            <td class="label" style="width: 15%;">Réf. Client :</td>
            <td class="value" style="width: 35%;">{{ $invoice->customer?->reference ?? '' }}</td>
            <td class="label" style="width: 15%;">Email :</td>
            <td class="value" style="width: 35%;">{{ $billTo['email'] ?? $invoice->customer?->email ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Date :</td>
            <td class="value">{{ $invoice->issue_date?->format('d/m/Y') }}</td>
            <td class="label">Échéance :</td>
            <td class="value">{{ $invoice->due_date?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Tél :</td>
            <td class="value">{{ $billTo['phone'] ?? $invoice->customer?->phone ?? '' }}</td>
            <td class="label">Adresse :</td>
            <td class="value">{{ $billTo['address'] ?? '' }}</td>
        </tr>
    </table>

    {{-- ─── Items Table ──────────────────────────────────────── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Désignation</th>
                <th style="width: 10%;">Qté</th>
                <th style="width: 15%;">Prix unit.</th>
                <th class="text-end" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items->sortBy('position') as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->label }}</strong>
                    @if($item->description)
                        <br><span style="color: #888; font-size: 9px;">{{ $item->description }}</span>
                    @endif
                </td>
                <td>{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                <td>{{ number_format($item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                <td class="text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }} {{ $currency }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Subtotals ────────────────────────────────────────── --}}
    <table class="subtotals-table">
        <tr>
            <td>Sous-total HT</td>
            <td class="text-right">{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</td>
        </tr>
        @if($invoice->tax_total > 0)
        <tr>
            <td>TVA</td>
            <td class="text-right">{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</td>
        </tr>
        @endif
        @if($invoice->discount_total > 0)
        <tr>
            <td>Remise</td>
            <td class="text-right">-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</td>
        </tr>
        @endif
    </table>

    {{-- ─── Total TTC Bar ────────────────────────────────────── --}}
    <div class="total-bar">
        <table>
            <tr>
                <td style="width: 50%;" class="count">{{ $invoice->items->count() }} article(s)</td>
                <td class="total-label" style="width: 25%;">Total TTC</td>
                <td class="total-value" style="width: 25%;">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</td>
            </tr>
        </table>
    </div>

    {{-- ─── Total in Words ───────────────────────────────────── --}}
    @if($invoice->total_in_words)
    <div class="words-block">
        Arrêtée la présente facture à la somme de : <strong>{{ $invoice->total_in_words }}</strong>
    </div>
    @endif

    {{-- ─── Bank Details & Signature ─────────────────────────── --}}
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                @if(!empty($bank))
                <div class="bank-block">
                    <h6>Coordonnées bancaires :</h6>
                    @if(!empty($bank['bank_name']))
                        <p>Banque : <span style="color:#333;">{{ $bank['bank_name'] }}</span></p>
                    @endif
                    @if(!empty($bank['account_name']))
                        <p>Titulaire : <span style="color:#333;">{{ $bank['account_name'] }}</span></p>
                    @endif
                    @if(!empty($bank['rib']))
                        <p>RIB : <span style="color:#333;">{{ $bank['rib'] }}</span></p>
                    @endif
                    @if(!empty($bank['iban']))
                        <p>IBAN : <span style="color:#333;">{{ $bank['iban'] }}</span></p>
                    @endif
                </div>
                @endif
            </td>
            <td style="width: 40%; vertical-align: top; text-align: center;">
                <div class="signature-block">
                    <p>Pour {{ $company['company_name'] ?? $tenant?->name ?? '' }}</p>
                    @include('pdf.partials.signature')
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── Terms ────────────────────────────────────────────── --}}
    @if($invoice->terms)
    <div class="terms-block">
        <h6>Conditions générales :</h6>
        {!! nl2br(e($invoice->terms)) !!}
    </div>
    @endif

    {{-- ─── Footer ───────────────────────────────────────────── --}}
    <div class="footer-block">
        <p>Merci pour votre confiance</p>
    </div>

</div>
</body>
</html>
