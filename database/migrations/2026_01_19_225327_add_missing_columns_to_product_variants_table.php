<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            
            // 1. Verifica e cria 'sku'
            if (!Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku')->nullable();
            }

            // 2. Verifica e cria 'price'
            if (!Schema::hasColumn('product_variants', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }

            // 3. Verifica e cria 'quantity' (O que causou seu erro)
            if (!Schema::hasColumn('product_variants', 'quantity')) {
                $table->integer('quantity')->default(0);
            }

            // 4. Verifica e cria 'options'
            if (!Schema::hasColumn('product_variants', 'options')) {
                $table->json('options')->nullable();
            }

            // 5. Verifica e cria 'images'
            if (!Schema::hasColumn('product_variants', 'images')) {
                $table->json('images')->nullable();
            }

            // 6. Verifica e cria 'is_default'
            if (!Schema::hasColumn('product_variants', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            
            // 7. Verifica e cria 'sale_price'
            if (!Schema::hasColumn('product_variants', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable();
            }

            // 8. Verifica e cria 'image' (capa)
            if (!Schema::hasColumn('product_variants', 'image')) {
                $table->string('image')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Remove as colunas caso precise reverter
        Schema::table('product_variants', function (Blueprint $table) {
            $columns = ['sku', 'price', 'quantity', 'options', 'images', 'is_default', 'sale_price', 'image'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('product_variants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};