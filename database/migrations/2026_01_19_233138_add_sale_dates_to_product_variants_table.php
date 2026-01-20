<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'sale_start_date')) {
                $table->dateTime('sale_start_date')->nullable()->after('sale_price');
            }
            if (!Schema::hasColumn('product_variants', 'sale_end_date')) {
                $table->dateTime('sale_end_date')->nullable()->after('sale_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['sale_start_date', 'sale_end_date']);
        });
    }
};