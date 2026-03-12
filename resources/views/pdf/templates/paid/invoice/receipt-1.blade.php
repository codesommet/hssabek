<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $invoice->number }}</title>
    @include('pdf.partials.theme-css')
</head>
<body>
    @php
        $company = $settings?->company_settings ?? [];
        $billTo = $invoice->bill_to_snapshot ?? [];
        $billFrom = $invoice->bill_from_snapshot ?? [];
        $bank = $invoice->bank_details_snapshot ?? [];
        $currency = $invoice->currency ?? 'MAD';
        $statusLabels = [
            'draft' => 'Brouillon',
            'sent' => 'Envoyée',
            'paid' => 'Payée',
            'partially_paid' => 'Partiellement payée',
            'overdue' => 'En retard',
            'cancelled' => 'Annulée',
        ];
        $statusColors = [
            'draft' => 'secondary',
            'sent' => 'info',
            'paid' => 'success',
            'partially_paid' => 'warning',
            'overdue' => 'danger',
            'cancelled' => 'dark',
        ];
    @endphp

    <div class="invoice-wrapper receipt-page">
        <div class="card m-auto shadow-none">
            <div class="card-body">
                <div class="bg-light p-2 text-center mb-2">
                    @if($tenant)
                        @php $logoPath = $tenant->getFirstMediaPath('logo'); @endphp
                        @if($logoPath && file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Logo">
                        @endif
                    @endif
                </div>
                <h6 class="fs-16 fw-semibold text-center mb-2">{{ $company['company_name'] ?? $tenant?->name ?? '' }}</h6>
                <p class=" text-center mb-2">{{ $company['address'] ?? '' }}@if(!empty($company['city'])), {{ $company['city'] }}@endif @if(!empty($company['postal_code'])) {{ $company['postal_code'] }}@endif @if(!empty($company['country'])), {{ $company['country'] }}@endif.
                    {{ !empty($company['email']) ? 'Email: ' . $company['email'] : '' }}</p>
                <span class="retail-receipt fs-12 text-dark text-center fw-semibold mb-2">REÇU</span>
                <div class="mb-2">
                    <!-- start row -->
                    <div class="row mb-2 row-gap-3">
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class=" mb-0">Nom :</p>
                                <p class=" text-dark">{{ $billTo['name'] ?? $invoice->customer?->name ?? '' }}</p>
                            </div>
                        </div><!-- end col -->
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class=" mb-0">N° Facture :</p>
                                <p class=" text-dark">{{ $invoice->number }}</p>
                            </div>
                        </div><!-- end col -->
                    </div>
                    <!-- end row -->

                    <!-- start row -->
                    <div class="row row-gap-3">
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class=" mb-0">IF :</p>
                                <p class=" text-dark">{{ $billTo['tax_id'] ?? '' }}</p>
                            </div>
                        </div><!-- end col -->
                        <div class="col-sm-6 col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <p class=" mb-0">Date :</p>
                                <p class=" text-dark">{{ $invoice->issue_date?->format('d/m/Y') }}</p>
                            </div>
                        </div><!-- end col -->
                    </div>
                    <!-- end row -->
                </div>
                <div class="receipt-header">
                    <table class="table table-nowrap border-dashed mb-2">
                        <thead>
                            <tr class="mb-2">
                                <th class="fs-10 border-0 pe-0">N°</th>
                                <th class="fs-10 border-0 ps-0">Désignation</th>
                                <th class="fs-10 border-0 pe-0 text-end">Prix</th>
                                <th class="fs-10 border-0 pe-0 text-end">Qté</th>
                                <th class="fs-10 border-0 pe-0 text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items->sortBy('position') as $index => $item)
                            <tr>
                                <td class="fs-10 border-0 p-1 pe-0">{{ $index + 1 }}.</td>
                                <td class="fs-10 border-0 p-1 ps-0">{{ $item->label }}</td>
                                <td class="fs-10 border-0 p-1 text-end">{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                                <td class="fs-10 border-0 p-1 text-end">{{ rtrim(rtrim(number_format($item->quantity, 3, ',', ' '), '0'), ',') }}</td>
                                <td class="fs-10 border-0 p-1 text-end">{{ number_format($item->line_subtotal, 2, ',', ' ') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="border-dashed mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Sous-total HT :</p>
                            <p>{{ number_format($invoice->subtotal, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @if($invoice->discount_total > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Remise :</p>
                            <p>-{{ number_format($invoice->discount_total, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="border-dashed mb-2">
                        @if($invoice->charges->count())
                        @foreach($invoice->charges->sortBy('position') as $charge)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">{{ $charge->label }} @if($charge->tax_rate > 0)({{ number_format($charge->tax_rate, 0) }}%)@endif :</p>
                            <p>{{ number_format($charge->amount, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @endforeach
                        @endif
                        @if($invoice->enable_tax)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">TVA :</p>
                            <p>{{ number_format($invoice->tax_total, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="border-dashed mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Total TTC :</p>
                            <p>{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @if($invoice->amount_paid > 0)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Montant payé :</p>
                            <p>{{ number_format($invoice->amount_paid, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Reste à payer :</p>
                            <p>{{ number_format($invoice->amount_due, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0">Statut :</p>
                            <p><span class="badge badge-{{ $statusColors[$invoice->status] ?? 'secondary' }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <p class="mb-0 fw-semibold text-dark">Total à payer :</p>
                            <p class="fw-semibold text-dark">{{ number_format($invoice->total, 2, ',', ' ') }} {{ $currency }}</p>
                        </div>
                    </div>

                    @if($invoice->total_in_words)
                    <p class="text-center border-dashed pb-2 mb-2">Arrêtée la présente facture à la somme de : {{ $invoice->total_in_words }}</p>
                    @endif

                    @if($invoice->terms)
                    <p class="text-center border-dashed pb-2 mb-2">{!! nl2br(e($invoice->terms)) !!}</p>
                    @endif

                    @if(!empty($bank))
                    <div class="border-dashed pb-2 mb-2">
                        <p class="text-center fw-semibold text-dark mb-1">Coordonnées bancaires</p>
                        @if(!empty($bank['bank_name']))<p class="text-center mb-0">Banque : {{ $bank['bank_name'] }}</p>@endif
                        @if(!empty($bank['account_name']))<p class="text-center mb-0">Titulaire : {{ $bank['account_name'] }}</p>@endif
                        @if(!empty($bank['rib']))<p class="text-center mb-0">RIB : {{ $bank['rib'] }}</p>@endif
                        @if(!empty($bank['iban']))<p class="text-center mb-0">IBAN : {{ $bank['iban'] }}</p>@endif
                        @if(!empty($bank['swift']))<p class="text-center mb-0">SWIFT : {{ $bank['swift'] }}</p>@endif
                    </div>
                    @endif

                    <p class="text-center">{{ $invoice->notes ?? 'Merci pour votre confiance' }}</p>

                    @include('pdf.partials.signature')
                </div>
            </div><!-- end card body -->
        </div><!-- end card -->
    </div>
</body>
</html>
