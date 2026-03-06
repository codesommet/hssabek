<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Aperçu — {{ $templateName }}</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 40px;
            color: #333;
            background: #fff;
        }
        .preview-container {
            max-width: 700px;
            margin: 80px auto;
            text-align: center;
        }
        .template-name {
            font-size: 28px;
            font-weight: 300;
            color: {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 14px;
            color: #999;
            margin-bottom: 40px;
        }
        .sample-header {
            border-top: 3px solid {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
            padding-top: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .company-info {
            font-size: 11px;
            color: #777;
            margin-top: 5px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
            margin: 30px 0 20px;
            text-align: left;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
            color: #fff;
            padding: 8px 12px;
            font-size: 11px;
            text-align: left;
        }
        td {
            padding: 8px 12px;
            font-size: 11px;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) td { background: #f9f9f9; }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .totals table {
            width: 250px;
            margin-left: auto;
        }
        .totals td {
            border: none;
            padding: 4px 8px;
        }
        .totals .total-row td {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
            color: {{ $settings?->company_settings['brand_color'] ?? '#2563eb' }};
        }
        .notice {
            margin-top: 60px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="template-name">Modèle « {{ $templateName }} »</div>
        <p class="subtitle">Aperçu du modèle PDF — Créez votre première facture pour voir un aperçu réel.</p>
    </div>

    <div class="sample-header">
        <div class="company-name">{{ $tenant->name ?? 'Votre Entreprise' }}</div>
        <div class="company-info">
            {{ $settings?->company_settings['address'] ?? '123 Rue Exemple, Casablanca' }}
            @if($settings?->company_settings['phone'] ?? false) | {{ $settings->company_settings['phone'] }} @endif
        </div>
    </div>

    <div class="doc-title">FACTURE #FAC-0001</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>Qté</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Exemple de produit A</td>
                <td>2</td>
                <td>500,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
                <td>1 000,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Exemple de service B</td>
                <td>1</td>
                <td>750,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
                <td>750,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Exemple de produit C</td>
                <td>5</td>
                <td>100,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
                <td>500,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Sous-total</td>
                <td>2 250,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
            <tr>
                <td>TVA (20%)</td>
                <td>450,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
            <tr class="total-row">
                <td>Total TTC</td>
                <td>2 700,00 {{ $tenant->default_currency ?? 'MAD' }}</td>
            </tr>
        </table>
    </div>

    <div class="notice">
        Ceci est un aperçu fictif. Créez une facture pour voir le rendu réel avec le modèle « {{ $templateName }} ».
    </div>
</body>
</html>
