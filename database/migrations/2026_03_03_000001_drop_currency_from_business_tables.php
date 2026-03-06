<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'products',
        'customers',
        'suppliers',
        'invoices',
        'quotes',
        'payments',
        'credit_notes',
        'delivery_challans',
        'purchase_orders',
        'vendor_bills',
        'debit_notes',
        'supplier_payments',
        'expenses',
        'incomes',
        'loans',
        'stock_movements',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'currency')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('currency');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasColumn($table, 'currency')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->char('currency', 3)->nullable()->after('tenant_id');
                });
            }
        }
    }
};
