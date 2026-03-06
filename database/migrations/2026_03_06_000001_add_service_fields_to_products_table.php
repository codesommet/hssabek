<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add service-specific fields to the products table.
     * When item_type = 'service', these fields become relevant.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ─── Service-specific fields ─────────────────────────
            $table->enum('billing_type', ['one_time', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'per_project'])
                ->default('one_time')
                ->after('description');
            $table->decimal('hourly_rate', 12, 2)->nullable()->after('billing_type');
            $table->integer('estimated_hours')->nullable()->after('hourly_rate');
            $table->string('sac_code')->nullable()->after('estimated_hours'); // Service Accounting Code
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'billing_type',
                'hourly_rate',
                'estimated_hours',
                'sac_code',
            ]);
        });
    }
};
