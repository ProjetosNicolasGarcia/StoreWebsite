<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove 'base_price' se existir (O causador do erro)
            if (Schema::hasColumn('products', 'base_price')) {
                $table->dropColumn('base_price');
            }

            // Remove 'sale_price' se existir
            if (Schema::hasColumn('products', 'sale_price')) {
                $table->dropColumn('sale_price');
            }

            // Remove datas de promoção antigas se existirem
            if (Schema::hasColumn('products', 'sale_start_date')) {
                $table->dropColumn(['sale_start_date', 'sale_end_date']);
            }
            
            // Remove estoque antigo se existir
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
            if (Schema::hasColumn('products', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }

    public function down(): void
    {
        // Caso precise reverter, recria os campos como nullable para não dar erro
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
        });
    }
};