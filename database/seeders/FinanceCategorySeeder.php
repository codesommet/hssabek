<?php

namespace Database\Seeders;

use App\Models\Finance\FinanceCategory;
use App\Models\Tenancy\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds default finance categories for all tenants.
 * Includes auto-generated categories for sales and purchases integration.
 */
class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $incomeCategories = [
            ['name' => 'Ventes - Paiements Clients', 'type' => 'income', 'is_system' => true],
            ['name' => 'Ventes - Produits', 'type' => 'income', 'is_system' => false],
            ['name' => 'Ventes - Services', 'type' => 'income', 'is_system' => false],
            ['name' => 'Revenus - Intérêts', 'type' => 'income', 'is_system' => false],
            ['name' => 'Revenus - Autres', 'type' => 'income', 'is_system' => false],
        ];

        $expenseCategories = [
            ['name' => 'Achats - Paiements Fournisseurs', 'type' => 'expense', 'is_system' => true],
            ['name' => 'Achats - Matières premières', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Achats - Marchandises', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Loyer', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Électricité', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Internet & Téléphone', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Salaires', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Transport', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Fournitures de bureau', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Marketing & Publicité', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Assurances', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Bancaires', 'type' => 'expense', 'is_system' => false],
            ['name' => 'Frais - Autres', 'type' => 'expense', 'is_system' => false],
        ];

        $allCategories = array_merge($incomeCategories, $expenseCategories);

        // Seed categories for all existing tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            foreach ($allCategories as $category) {
                FinanceCategory::firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $category['name'],
                        'type' => $category['type'],
                    ],
                    [
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
