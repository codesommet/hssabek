<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('max_users')->nullable()->after('features');
            $table->unsignedInteger('max_customers')->nullable()->after('max_users');
            $table->unsignedInteger('max_products')->nullable()->after('max_customers');
            $table->unsignedInteger('max_invoices_per_month')->nullable()->after('max_products');
            $table->unsignedInteger('max_quotes_per_month')->nullable()->after('max_invoices_per_month');
            $table->unsignedInteger('max_exports_per_month')->nullable()->after('max_quotes_per_month');
            $table->unsignedInteger('max_warehouses')->nullable()->after('max_exports_per_month');
            $table->unsignedInteger('max_bank_accounts')->nullable()->after('max_warehouses');
            $table->unsignedInteger('max_storage_mb')->nullable()->after('max_bank_accounts');
            $table->boolean('is_popular')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'max_users',
                'max_customers',
                'max_products',
                'max_invoices_per_month',
                'max_quotes_per_month',
                'max_exports_per_month',
                'max_warehouses',
                'max_bank_accounts',
                'max_storage_mb',
                'is_popular',
            ]);
        });
    }
};
