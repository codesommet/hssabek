<?php

namespace Database\Seeders;

use App\Models\Tenancy\Tenant;
use App\Models\User;
use App\Models\Catalog\ProductCategory;
use App\Models\Catalog\Unit;
use App\Models\Catalog\TaxCategory;
use App\Models\Catalog\TaxGroup;
use App\Models\Catalog\TaxGroupRate;
use App\Models\Catalog\Product;
use App\Models\CRM\Customer;
use App\Models\CRM\CustomerAddress;
use App\Models\CRM\CustomerContact;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockMovement;
use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Sales\Quote;
use App\Models\Sales\QuoteItem;
use App\Models\Sales\QuoteCharge;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\InvoiceCharge;
use App\Models\Sales\PaymentMethod;
use App\Models\Sales\Payment;
use App\Models\Sales\PaymentAllocation;
use App\Models\Sales\CreditNote;
use App\Models\Sales\CreditNoteItem;
use App\Models\Sales\CreditNoteApplication;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryChallanItem;
use App\Models\Sales\DeliveryChallanCharge;
use App\Models\Sales\Refund;
use App\Models\Purchases\Supplier;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\PurchaseOrderItem;
use App\Models\Purchases\GoodsReceipt;
use App\Models\Purchases\GoodsReceiptItem;
use App\Models\Purchases\VendorBill;
use App\Models\Purchases\DebitNote;
use App\Models\Purchases\DebitNoteItem;
use App\Models\Purchases\DebitNoteApplication;
use App\Models\Purchases\SupplierPaymentMethod;
use App\Models\Purchases\SupplierPayment;
use App\Models\Purchases\SupplierPaymentAllocation;
use App\Models\Finance\BankAccount;
use App\Models\Finance\Currency;
use App\Models\Finance\ExchangeRate;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\Expense;
use App\Models\Finance\Income;
use App\Models\Finance\Loan;
use App\Models\Finance\LoanInstallment;
use App\Models\Finance\MoneyTransfer;
use App\Models\Pro\Branch;
use App\Models\Pro\RecurringInvoice;
use App\Models\Pro\InvoiceReminder;
use App\Models\System\DocumentNumberSequence;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

/**
 * FakeDataSeeder
 * 
 * Generates comprehensive fake data for all CRUD operations
 * Does NOT touch existing protected seeders:
 * - DemoTenantSeeder.php (login credentials)
 * - PermissionSeeder.php
 * - RoleSeeder.php
 */
class FakeDataSeeder extends Seeder
{
    private $faker;
    private $tenant;

    public function run(): void
    {
        $this->faker = Faker::create('fr_FR');

        // Get or create a demo tenant
        $this->tenant = Tenant::where('slug', 'localhost')->first();

        if (!$this->tenant) {
            $this->tenant = Tenant::create([
                'name' => 'Demo Tenant',
                'slug' => 'demo',
                'status' => 'active',
                'timezone' => 'Africa/Casablanca',
                'default_currency' => 'MAD',
            ]);
        }

        // Seed data in proper order (respecting foreign keys)
        $this->seedUnits();
        $this->seedTaxes();
        $this->seedCurrencies();
        $this->seedProductCategories();
        $this->seedProducts();
        $this->seedCustomers();
        $this->seedSuppliers();
        $this->seedWarehouses();
        $this->seedProductStocks();
        $this->seedPaymentMethods();
        $this->seedBankAccounts();
        $this->seedBranches();
        $this->seedFinanceCategories();
        $this->seedDocumentNumberSequences();

        // Sales documents
        $this->seedQuotes();
        $this->seedInvoices();
        $this->seedDeliveryChallan();
        $this->seedCreditNotes();
        $this->seedPayments();
        $this->seedRefunds();

        // Purchase documents
        $this->seedPurchaseOrders();
        $this->seedGoodsReceipts();
        $this->seedVendorBills();
        $this->seedDebitNotes();
        $this->seedSupplierPaymentMethods();
        $this->seedSupplierPayments();

        // Inventory
        $this->seedStockMovements();
        $this->seedStockTransfers();

        // Finance
        $this->seedExpenses();
        $this->seedIncomes();
        $this->seedLoans();
        $this->seedMoneyTransfers();

        // Pro features
        $this->seedRecurringInvoices();
        $this->seedInvoiceReminders();
    }

    // ─── CATALOG ─────────────────────────────────────────────────────────────
    private function seedUnits(): void
    {
        $units = [
            ['name' => 'Pièce', 'short_name' => 'pc'],
            ['name' => 'Kilogramme', 'short_name' => 'kg'],
            ['name' => 'Litre', 'short_name' => 'l'],
            ['name' => 'Mètre', 'short_name' => 'm'],
            ['name' => 'Boîte', 'short_name' => 'bte'],
            ['name' => 'Carton', 'short_name' => 'ctn'],
            ['name' => 'Paquet', 'short_name' => 'pqt'],
            ['name' => 'Rouleau', 'short_name' => 'rl'],
            ['name' => 'Service', 'short_name' => 'svc'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $unit['name']],
                ['short_name' => $unit['short_name']]
            );
        }
    }

    private function seedTaxes(): void
    {
        $taxRates = [
            ['name' => 'TVA Standard (20%)', 'rate' => 20.00],
            ['name' => 'TVA Réduite (10%)', 'rate' => 10.00],
            ['name' => 'TVA Super Réduite (5%)', 'rate' => 5.00],
            ['name' => 'Exonéré de TVA', 'rate' => 0.00],
        ];

        foreach ($taxRates as $tax) {
            TaxCategory::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $tax['name']],
                [
                    'rate' => $tax['rate'],
                    'type' => 'percentage',
                    'is_default' => $tax['rate'] === 20.00,
                    'is_active' => true,
                ]
            );
        }

        // Create tax groups
        $taxGroup = TaxGroup::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'name' => 'Groupe TVA Standard'],
            ['is_active' => true]
        );

        $taxCategories = TaxCategory::where('tenant_id', $this->tenant->id)->get();
        foreach ($taxCategories as $index => $taxCat) {
            TaxGroupRate::firstOrCreate(
                ['tax_group_id' => $taxGroup->id, 'name' => $taxCat->name],
                [
                    'tenant_id' => $this->tenant->id,
                    'rate' => $taxCat->rate,
                    'position' => $index + 1,
                ]
            );
        }
    }

    private function seedCurrencies(): void
    {
        $currencyData = [
            ['code' => 'MAD', 'name' => 'Dirham Marocain', 'symbol' => 'د.م.', 'precision' => 2],
            ['code' => 'USD', 'name' => 'Dollar Américain', 'symbol' => '$', 'precision' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'precision' => 2],
        ];

        foreach ($currencyData as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                [
                    'name' => $currency['name'],
                    'symbol' => $currency['symbol'],
                    'precision' => $currency['precision'],
                ]
            );
        }

        // Create exchange rates
        $codes = array_column($currencyData, 'code');
        foreach ($codes as $base) {
            foreach ($codes as $quote) {
                if ($base !== $quote) {
                    ExchangeRate::firstOrCreate(
                        ['tenant_id' => $this->tenant->id, 'base_currency' => $base, 'quote_currency' => $quote, 'date' => now()->toDateString()],
                        ['rate' => $this->faker->randomFloat(4, 0.8, 1.2)]
                    );
                }
            }
        }
    }

    private function seedProductCategories(): void
    {
        $categories = [
            'Électronique',
            'Vêtements',
            'Alimentation & Boissons',
            'Fournitures de Bureau',
            'Équipement',
            'Matières Premières',
            'Services',
            'Logiciels',
            'Matériel Informatique',
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'slug' => str()->slug($category)],
                [
                    'name' => $category,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedProducts(): void
    {
        $categories = ProductCategory::where('tenant_id', $this->tenant->id)->get();
        $units = Unit::where('tenant_id', $this->tenant->id)->get();
        $taxCategories = TaxCategory::where('tenant_id', $this->tenant->id)->get();

        $productNames = [
            'Ordinateur Portable',
            'PC de Bureau',
            'Écran 27 pouces',
            'Clavier Mécanique',
            'Souris Sans Fil',
            'Câble USB',
            'Disque Dur Externe',
            'Stockage SSD',
            'T-Shirt Coton',
            'Jean Bleu',
            'Chaussures Cuir',
            'Casquette',
            'Café en Grains',
            'Tablette Chocolat',
            'Brique de Lait',
            'Pain de Mie',
            'Cahier A4',
            'Stylo Bille',
            'Agrafeuse',
            'Papier Imprimante',
            'Support Bureau',
            'Chaise de Bureau',
            'Armoire de Classement',
            'Tableau Blanc',
        ];

        foreach ($productNames as $name) {
            Product::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'code' => str()->slug($name), 'name' => $name],
                [
                    'item_type' => $this->faker->randomElement(['product', 'service']),
                    'sku' => strtoupper(str()->random(8)),
                    'category_id' => $categories->random()->id,
                    'unit_id' => $units->random()->id,
                    'description' => $this->faker->sentence(),
                    'selling_price' => $this->faker->randomFloat(2, 50, 5000),
                    'purchase_price' => $this->faker->randomFloat(2, 20, 3000),
                    'track_inventory' => $this->faker->boolean(),
                    'quantity' => $this->faker->numberBetween(0, 1000),
                    'alert_quantity' => $this->faker->numberBetween(10, 50),
                    'barcode' => strtoupper($this->faker->ean13()),
                    'discount_type' => $this->faker->randomElement(['none', 'percentage', 'fixed']),
                    'discount_value' => $this->faker->randomFloat(2, 0, 500),
                    'tax_category_id' => $taxCategories->random()->id,
                    'is_active' => true,
                ]
            );
        }
    }

    // ─── CRM ──────────────────────────────────────────────────────────────────
    private function seedCustomers(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $customer = Customer::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'email' => $this->faker->email()],
                [
                    'type' => $this->faker->randomElement(['individual', 'company']),
                    'name' => $this->faker->company(),
                    'phone' => $this->faker->phoneNumber(),
                    'tax_id' => strtoupper($this->faker->bothify('??########')),
                    'payment_terms_days' => $this->faker->randomElement([0, 15, 30, 45, 60]),
                    'notes' => $this->faker->sentence(),
                    'status' => 'active',
                ]
            );

            // Add addresses
            for ($j = 0; $j < $this->faker->numberBetween(1, 3); $j++) {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'tenant_id' => $this->tenant->id,
                    'type' => $j === 0 ? 'billing' : 'shipping',
                    'line1' => $this->faker->streetAddress(),
                    'line2' => $this->faker->optional()->secondaryAddress(),
                    'city' => $this->faker->city(),
                    'region' => $this->faker->region(),
                    'postal_code' => $this->faker->postcode(),
                    'country' => $this->faker->countryCode(),
                ]);
            }

            // Add contacts
            for ($j = 0; $j < $this->faker->numberBetween(1, 2); $j++) {
                CustomerContact::create([
                    'customer_id' => $customer->id,
                    'tenant_id' => $this->tenant->id,
                    'name' => $this->faker->name(),
                    'position' => $this->faker->jobTitle(),
                    'email' => $this->faker->email(),
                    'phone' => $this->faker->phoneNumber(),
                    'is_primary' => $j === 0,
                ]);
            }
        }
    }

    private function seedSuppliers(): void
    {
        for ($i = 0; $i < 15; $i++) {
            Supplier::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'email' => $this->faker->email()],
                [
                    'name' => $this->faker->company(),
                    'phone' => $this->faker->phoneNumber(),
                    'tax_id' => strtoupper($this->faker->bothify('??########')),
                    'payment_terms_days' => $this->faker->randomElement([0, 15, 30, 45, 60]),
                    'notes' => $this->faker->sentence(),
                    'status' => 'active',
                ]
            );
        }
    }

    // ─── INVENTORY ───────────────────────────────────────────────────────────
    private function seedWarehouses(): void
    {
        $warehouseNames = [
            'Entrepôt Principal',
            'Stockage Secondaire',
            'Centre de Distribution',
            'Chambre Froide',
            'Dépôt Régional',
        ];

        foreach ($warehouseNames as $index => $name) {
            Warehouse::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'code' => 'WH-' . ($index + 1)],
                [
                    'name' => $name,
                    'address' => $this->faker->address(),
                    'is_default' => $index === 0,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedProductStocks(): void
    {
        $warehouses = Warehouse::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        foreach ($warehouses as $warehouse) {
            foreach ($products as $product) {
                ProductStock::firstOrCreate(
                    ['tenant_id' => $this->tenant->id, 'warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                    ['quantity_on_hand' => $this->faker->numberBetween(0, 1000)]
                );
            }
        }
    }

    private function seedStockMovements(): void
    {
        $warehouses = Warehouse::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 50; $i++) {
            StockMovement::create([
                'tenant_id' => $this->tenant->id,
                'warehouse_id' => $warehouses->random()->id,
                'product_id' => $products->random()->id,
                'movement_type' => $this->faker->randomElement([
                    'stock_in',
                    'stock_out',
                    'adjustment_in',
                    'adjustment_out',
                    'purchase_in',
                    'sale_out',
                    'return_in',
                    'return_out'
                ]),
                'quantity' => $this->faker->randomFloat(3, 1, 100),
                'unit_cost' => $this->faker->randomFloat(2, 10, 1000),
                'note' => $this->faker->sentence(),
                'moved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            ]);
        }
    }

    private function seedStockTransfers(): void
    {
        $warehouses = Warehouse::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        if ($warehouses->count() < 2) return;

        for ($i = 0; $i < 10; $i++) {
            $from = $warehouses->random();
            $to = $warehouses->where('id', '!=', $from->id)->random();

            $transfer = StockTransfer::create([
                'tenant_id' => $this->tenant->id,
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'number' => 'ST-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'in_transit', 'received']),
                'shipped_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'received_at' => $this->faker->optional()->dateTimeThisMonth(),
                'notes' => $this->faker->sentence(),
            ]);

            // Add items
            for ($j = 0; $j < $this->faker->numberBetween(2, 5); $j++) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $products->random()->id,
                    'quantity' => $this->faker->numberBetween(1, 100),
                ]);
            }
        }
    }

    // ─── SALES ───────────────────────────────────────────────────────────────
    private function seedPaymentMethods(): void
    {
        $methods = [
            'Espèces',
            'Virement Bancaire',
            'Carte Bancaire',
            'Chèque',
            'Paiement en Ligne',
            'Mobile Money',
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $method],
                ['is_active' => true]
            );
        }
    }

    private function seedQuotes(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 15; $i++) {
            $quote = Quote::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'number' => 'QT-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'sent', 'accepted', 'rejected']),
                'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+3 months'),
                'enable_tax' => true,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'sent_at' => $this->faker->optional()->dateTimeThisMonth(),
                'accepted_at' => $this->faker->optional()->dateTimeThisMonth(),
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(1, 5); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(1, 10);
                $lineTotal = $product->selling_price * $quantity;
                $subtotal += $lineTotal;

                $lineTax = $lineTotal * 0.20;
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $product->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'tax_rate' => 20,
                    'line_subtotal' => $lineTotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal + $lineTax,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            $quote->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }
    }

    private function seedInvoices(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 20; $i++) {
            $status = $this->faker->randomElement(['draft', 'sent', 'partial', 'paid', 'paid', 'paid', 'overdue']);

            $invoice = Invoice::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'number' => 'INV-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'status' => $status,
                'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'due_date' => $this->faker->dateTimeBetween('+15 days', '+60 days'),
                'enable_tax' => true,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'amount_due' => 0,
                'sent_at' => in_array($status, ['sent', 'partial', 'paid', 'overdue']) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
                'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(1, 8); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(1, 20);
                $lineTotal = $product->selling_price * $quantity;
                $subtotal += $lineTotal;

                $lineTax = $lineTotal * 0.20;
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $product->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'tax_rate' => 20,
                    'line_subtotal' => $lineTotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal + $lineTax,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            // Set amount_paid/amount_due based on status
            $amountPaid = 0;
            $amountDue = $total;

            if ($status === 'paid') {
                $amountPaid = $total;
                $amountDue = 0;
            } elseif ($status === 'partial') {
                $amountPaid = round($total * $this->faker->randomFloat(2, 0.2, 0.8), 2);
                $amountDue = $total - $amountPaid;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'amount_paid' => $amountPaid,
                'amount_due' => $amountDue,
            ]);
        }
    }

    private function seedDeliveryChallan(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 10; $i++) {
            $challan = DeliveryChallan::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'number' => 'DC-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'issued', 'delivered']),
                'challan_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'enable_tax' => true,
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(1, 5); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(1, 15);
                $lineTotal = $product->selling_price * $quantity;
                $subtotal += $lineTotal;

                $lineTax = $lineTotal * 0.20;
                DeliveryChallanItem::create([
                    'delivery_challan_id' => $challan->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $product->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'tax_rate' => 20,
                    'line_subtotal' => $lineTotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal + $lineTax,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            $challan->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }
    }

    private function seedCreditNotes(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $invoices = Invoice::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 8; $i++) {
            $creditNote = CreditNote::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'invoice_id' => $invoices->random()?->id,
                'number' => 'CN-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'issued', 'applied']),
                'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(1, 3); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(1, 5);
                $lineTotal = $product->selling_price * $quantity;
                $subtotal += $lineTotal;

                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'tenant_id' => $this->tenant->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'tax_rate' => 20,
                    'line_total' => $lineTotal,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            $creditNote->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }
    }

    private function seedPayments(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $invoices = Invoice::where('tenant_id', $this->tenant->id)->get();
        $paymentMethods = PaymentMethod::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 15; $i++) {
            Payment::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'reference_number' => strtoupper($this->faker->bothify('REF-########')),
                'amount' => $this->faker->randomFloat(2, 500, 50000),
                'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'status' => $this->faker->randomElement(['pending', 'succeeded']),
                'payment_method_id' => $paymentMethods->random()?->id,
                'notes' => $this->faker->sentence(),
            ]);
        }
    }

    private function seedRefunds(): void
    {
        $payments = Payment::where('tenant_id', $this->tenant->id)->get();

        if ($payments->isEmpty()) return;

        foreach ($payments->random(min(5, $payments->count())) as $payment) {
            Refund::create([
                'tenant_id' => $this->tenant->id,
                'payment_id' => $payment->id,
                'amount' => $this->faker->randomFloat(2, 100, min(10000, $payment->amount)),
                'status' => $this->faker->randomElement(['pending', 'succeeded', 'failed']),
                'refunded_at' => $this->faker->optional()->dateTimeThisMonth(),
            ]);
        }
    }

    // ─── PURCHASES ───────────────────────────────────────────────────────────
    private function seedPurchaseOrders(): void
    {
        $suppliers = Supplier::where('tenant_id', $this->tenant->id)->get();
        $warehouses = Warehouse::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 15; $i++) {
            $po = PurchaseOrder::create([
                'tenant_id' => $this->tenant->id,
                'supplier_id' => $suppliers->random()->id,
                'warehouse_id' => $warehouses->random()->id,
                'number' => 'PO-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'sent', 'confirmed', 'received']),
                'order_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'expected_date' => $this->faker->dateTimeBetween('+7 days', '+30 days'),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(2, 8); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(5, 100);
                $lineTotal = $product->purchase_price * $quantity;
                $subtotal += $lineTotal;

                $lineTax = $lineTotal * 0.20;
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $product->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_cost' => $product->purchase_price,
                    'tax_rate' => 20,
                    'line_subtotal' => $lineTotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal + $lineTax,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            $po->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }
    }

    private function seedGoodsReceipts(): void
    {
        $warehouses = Warehouse::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 10; $i++) {
            $gr = GoodsReceipt::create([
                'tenant_id' => $this->tenant->id,
                'warehouse_id' => $warehouses->random()->id,
                'number' => 'GR-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'received_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'status' => $this->faker->randomElement(['draft', 'received']),
                'notes' => $this->faker->sentence(),
            ]);

            // Add items
            for ($j = 0; $j < $this->faker->numberBetween(2, 5); $j++) {
                $qty = $this->faker->numberBetween(10, 500);
                $unitCost = $this->faker->randomFloat(2, 100, 1000);
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $gr->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $products->random()->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $qty * $unitCost,
                    'position' => $j + 1,
                ]);
            }
        }
    }

    private function seedVendorBills(): void
    {
        $suppliers = Supplier::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 12; $i++) {
            $bill = VendorBill::create([
                'tenant_id' => $this->tenant->id,
                'supplier_id' => $suppliers->random()->id,
                'number' => 'VB-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'posted', 'paid', 'void']),
                'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'due_date' => $this->faker->dateTimeBetween('+15 days', '+60 days'),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'amount_due' => 0,
            ]);

            // Note: VendorBill items would be added similarly to invoices
            // This depends on your actual VendorBill item model structure
        }
    }

    private function seedDebitNotes(): void
    {
        $suppliers = Supplier::where('tenant_id', $this->tenant->id)->get();
        $products = Product::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 8; $i++) {
            $debitNote = DebitNote::create([
                'tenant_id' => $this->tenant->id,
                'supplier_id' => $suppliers->random()->id,
                'number' => 'DN-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'status' => $this->faker->randomElement(['draft', 'issued', 'applied']),
                'debit_note_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'subtotal' => 0,
                'tax_total' => 0,
                'total' => 0,
            ]);

            // Add items
            $subtotal = 0;
            for ($j = 0; $j < $this->faker->numberBetween(1, 3); $j++) {
                $product = $products->random();
                $quantity = $this->faker->numberBetween(1, 10);
                $lineTotal = $product->purchase_price * $quantity;
                $subtotal += $lineTotal;

                DebitNoteItem::create([
                    'debit_note_id' => $debitNote->id,
                    'tenant_id' => $this->tenant->id,
                    'product_id' => $product->id,
                    'label' => $product->name,
                    'quantity' => $quantity,
                    'unit_cost' => $product->purchase_price,
                    'tax_rate' => 20,
                    'line_total' => $lineTotal,
                    'position' => $j + 1,
                ]);
            }

            $taxTotal = $subtotal * 0.20;
            $total = $subtotal + $taxTotal;

            $debitNote->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total,
            ]);
        }
    }

    private function seedSupplierPaymentMethods(): void
    {
        $methods = ['Espèces', 'Virement Bancaire', 'Chèque'];

        foreach ($methods as $method) {
            SupplierPaymentMethod::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $method],
                ['is_active' => true]
            );
        }
    }

    private function seedSupplierPayments(): void
    {
        $suppliers = Supplier::where('tenant_id', $this->tenant->id)->get();
        $supplierPaymentMethods = SupplierPaymentMethod::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 10; $i++) {
            SupplierPayment::create([
                'tenant_id' => $this->tenant->id,
                'supplier_id' => $suppliers->random()->id,
                'reference_number' => strtoupper($this->faker->bothify('REF-########')),
                'amount' => $this->faker->randomFloat(2, 1000, 100000),
                'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'status' => $this->faker->randomElement(['pending', 'succeeded']),
                'payment_method_id' => $supplierPaymentMethods->isNotEmpty() ? $supplierPaymentMethods->random()->id : null,
                'notes' => $this->faker->sentence(),
            ]);
        }
    }

    // ─── FINANCE ──────────────────────────────────────────────────────────────
    private function seedBankAccounts(): void
    {
        $currencyCodes = ['MAD', 'USD', 'EUR'];

        $accountNames = [
            'Compte Courant Principal',
            'Compte Épargne',
            'Compte Salaires',
            'Caisse',
        ];

        foreach ($accountNames as $name) {
            BankAccount::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'account_number' => $this->faker->bankAccountNumber()],
                [
                    'account_holder_name' => $this->faker->name(),
                    'account_type' => $this->faker->randomElement(['current', 'savings', 'business', 'other']),
                    'bank_name' => $this->faker->company(),
                    'currency' => $this->faker->randomElement($currencyCodes),
                    'current_balance' => $this->faker->randomFloat(2, 10000, 1000000),
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedFinanceCategories(): void
    {
        $expenseCategories = [
            'Salaires & Charges',
            'Loyer & Charges',
            'Fournitures de Bureau',
            'Déplacements',
            'Marketing',
            'Réparations & Entretien',
            'Services Professionnels',
            'Assurances',
        ];

        $incomeCategories = [
            'Chiffre d\'Affaires',
            'Revenus de Services',
            'Revenus d\'Intérêts',
            'Revenus Locatifs',
            'Retours sur Investissement',
        ];

        foreach ($expenseCategories as $category) {
            FinanceCategory::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $category, 'type' => 'expense'],
                ['is_active' => true]
            );
        }

        foreach ($incomeCategories as $category) {
            FinanceCategory::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'name' => $category, 'type' => 'income'],
                ['is_active' => true]
            );
        }
    }

    private function seedExpenses(): void
    {
        $bankAccounts = BankAccount::where('tenant_id', $this->tenant->id)->get();
        $categories = FinanceCategory::where('tenant_id', $this->tenant->id)->where('type', 'expense')->get();

        for ($i = 0; $i < 30; $i++) {
            Expense::create([
                'tenant_id' => $this->tenant->id,
                'expense_number' => 'EXP-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'reference_number' => strtoupper($this->faker->bothify('REF-########')),
                'amount' => $this->faker->randomFloat(2, 100, 10000),
                'expense_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'payment_mode' => $this->faker->randomElement(['cash', 'bank_transfer', 'card']),
                'payment_status' => $this->faker->randomElement(['unpaid', 'paid', 'partial']),
                'bank_account_id' => $bankAccounts->random()?->id,
                'category_id' => $categories->random()?->id,
                'description' => $this->faker->sentence(),
            ]);
        }
    }

    private function seedIncomes(): void
    {
        $bankAccounts = BankAccount::where('tenant_id', $this->tenant->id)->get();
        $categories = FinanceCategory::where('tenant_id', $this->tenant->id)->where('type', 'income')->get();
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 25; $i++) {
            Income::create([
                'tenant_id' => $this->tenant->id,
                'income_number' => 'INC-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'reference_number' => strtoupper($this->faker->bothify('REF-########')),
                'amount' => $this->faker->randomFloat(2, 500, 50000),
                'income_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'payment_mode' => $this->faker->randomElement(['cash', 'bank_transfer', 'card']),
                'bank_account_id' => $bankAccounts->random()?->id,
                'customer_id' => $customers->random()?->id,
                'category_id' => $categories->random()?->id,
                'description' => $this->faker->sentence(),
            ]);
        }
    }

    private function seedLoans(): void
    {
        $users = User::where('tenant_id', $this->tenant->id)->get();

        for ($i = 0; $i < 5; $i++) {
            $principalAmount = $this->faker->randomFloat(2, 50000, 500000);
            $interestRate = $this->faker->randomFloat(3, 2, 15);
            $months = $this->faker->numberBetween(12, 60);
            $monthlyRate = $interestRate / 100 / 12;
            $totalAmount = $principalAmount * (($monthlyRate * (1 + $monthlyRate) ** $months) / ((1 + $monthlyRate) ** $months - 1)) * $months;

            $loan = Loan::create([
                'tenant_id' => $this->tenant->id,
                'lender_type' => $this->faker->randomElement(['bank', 'personal']),
                'lender_name' => $this->faker->company(),
                'reference_number' => strtoupper($this->faker->bothify('LOAN-########')),
                'principal_amount' => $principalAmount,
                'interest_rate' => $interestRate,
                'interest_type' => $this->faker->randomElement(['fixed', 'reducing']),
                'total_amount' => $totalAmount,
                'remaining_balance' => $totalAmount,
                'start_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'end_date' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
                'payment_frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
                'status' => $this->faker->randomElement(['active', 'closed']),
                'notes' => $this->faker->sentence(),
                'created_by' => $users->random()?->id,
            ]);

            // Add installments (limit to 12 for seeder performance)
            $installmentCount = min($months, 12);
            $monthlyTotal = $totalAmount / $months;
            $monthlyPrincipal = $principalAmount / $months;
            $monthlyInterest = $monthlyTotal - $monthlyPrincipal;

            for ($j = 0; $j < $installmentCount; $j++) {
                $isPaid = $this->faker->boolean();
                LoanInstallment::create([
                    'loan_id' => $loan->id,
                    'tenant_id' => $this->tenant->id,
                    'installment_number' => $j + 1,
                    'due_date' => $loan->start_date->addMonths($j + 1),
                    'principal_amount' => round($monthlyPrincipal, 2),
                    'interest_amount' => round($monthlyInterest, 2),
                    'total_amount' => round($monthlyTotal, 2),
                    'paid_amount' => $isPaid ? round($monthlyTotal, 2) : 0,
                    'remaining_amount' => $isPaid ? 0 : round($monthlyTotal, 2),
                    'status' => $isPaid ? 'paid' : 'pending',
                    'paid_at' => $isPaid ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
                ]);
            }
        }
    }

    private function seedMoneyTransfers(): void
    {
        $bankAccounts = BankAccount::where('tenant_id', $this->tenant->id)->get();

        if ($bankAccounts->count() < 2) return;

        for ($i = 0; $i < 10; $i++) {
            $from = $bankAccounts->random();
            $to = $bankAccounts->where('id', '!=', $from->id)->random();

            MoneyTransfer::create([
                'tenant_id' => $this->tenant->id,
                'from_bank_account_id' => $from->id,
                'to_bank_account_id' => $to->id,
                'reference_number' => strtoupper($this->faker->bothify('MT-########')),
                'transfer_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'amount' => $this->faker->randomFloat(2, 5000, 100000),
                'notes' => $this->faker->sentence(),
                'status' => $this->faker->randomElement(['pending', 'completed']),
            ]);
        }
    }

    // ─── PRO FEATURES ─────────────────────────────────────────────────────────
    private function seedBranches(): void
    {
        $branchNames = [
            'Siège Social',
            'Agence Centre-Ville',
            'Agence Nord',
            'Agence Sud',
            'Agence Est',
        ];

        foreach ($branchNames as $index => $name) {
            Branch::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'code' => 'BR-' . ($index + 1)],
                [
                    'name' => $name,
                    'email' => $this->faker->email(),
                    'phone' => $this->faker->phoneNumber(),
                    'tax_id' => strtoupper($this->faker->bothify('??########')),
                    'is_default' => $index === 0,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedRecurringInvoices(): void
    {
        $customers = Customer::where('tenant_id', $this->tenant->id)->get();
        $invoices = Invoice::where('tenant_id', $this->tenant->id)->get();

        if ($invoices->isEmpty()) return;

        for ($i = 0; $i < 5; $i++) {
            RecurringInvoice::create([
                'tenant_id' => $this->tenant->id,
                'customer_id' => $customers->random()->id,
                'template_invoice_id' => $invoices->random()->id,
                'interval' => $this->faker->randomElement(['week', 'month', 'year']),
                'every' => $this->faker->numberBetween(1, 3),
                'next_run_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
                'end_at' => $this->faker->optional()->dateTimeBetween('+6 months', '+2 years'),
                'status' => $this->faker->randomElement(['active', 'paused']),
            ]);
        }
    }

    private function seedInvoiceReminders(): void
    {
        $invoices = Invoice::where('tenant_id', $this->tenant->id)->get();

        if ($invoices->count() < 5) return;

        foreach ($invoices->random(5) as $invoice) {
            InvoiceReminder::create([
                'tenant_id' => $this->tenant->id,
                'invoice_id' => $invoice->id,
                'type' => $this->faker->randomElement(['before_due', 'on_due', 'after_due']),
                'scheduled_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
                'channel' => $this->faker->randomElement(['email', 'sms']),
                'status' => $this->faker->randomElement(['queued', 'sent']),
                'sent_at' => $this->faker->optional()->dateTimeThisMonth(),
                'created_at' => now(),
            ]);
        }
    }

    private function seedDocumentNumberSequences(): void
    {
        $sequences = [
            ['key' => 'invoices', 'prefix' => 'INV'],
            ['key' => 'quotes', 'prefix' => 'QT'],
            ['key' => 'purchase_orders', 'prefix' => 'PO'],
            ['key' => 'delivery_challans', 'prefix' => 'DC'],
            ['key' => 'credit_notes', 'prefix' => 'CN'],
            ['key' => 'debit_notes', 'prefix' => 'DN'],
            ['key' => 'vendor_bills', 'prefix' => 'VB'],
        ];

        foreach ($sequences as $seq) {
            DocumentNumberSequence::firstOrCreate(
                ['tenant_id' => $this->tenant->id, 'key' => $seq['key']],
                [
                    'prefix' => $seq['prefix'],
                    'next_number' => 1,
                    'reset_policy' => 'yearly',
                ]
            );
        }
    }
}
