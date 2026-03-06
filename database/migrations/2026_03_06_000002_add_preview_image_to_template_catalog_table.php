<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('template_catalog', function (Blueprint $table) {
            $table->string('preview_image')->nullable()->after('css_path');
        });
    }

    public function down(): void
    {
        Schema::table('template_catalog', function (Blueprint $table) {
            $table->dropColumn('preview_image');
        });
    }
};
