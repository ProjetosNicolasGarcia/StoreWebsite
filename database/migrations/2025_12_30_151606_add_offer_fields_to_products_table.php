<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Só cria sale_price se ele NÃO existir
        if (!Schema::hasColumn('products', 'sale_price')) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('base_price');
        }

        // Só cria sale_start_date se ele NÃO existir
        if (!Schema::hasColumn('products', 'sale_start_date')) {
            // Tenta colocar após sale_price, se não der, põe após base_price
            $after = Schema::hasColumn('products', 'sale_price') ? 'sale_price' : 'base_price';
            $table->dateTime('sale_start_date')->nullable()->after($after);
        }

        // Só cria sale_end_date se ele NÃO existir
        if (!Schema::hasColumn('products', 'sale_end_date')) {
            $after = Schema::hasColumn('products', 'sale_start_date') ? 'sale_start_date' : 'base_price';
            $table->dateTime('sale_end_date')->nullable()->after($after);
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
