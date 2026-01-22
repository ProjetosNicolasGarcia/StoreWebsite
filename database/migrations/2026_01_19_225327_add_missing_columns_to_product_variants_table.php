<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: EnsureProductVariantsConsistency
 * * Refatoração de segurança para a tabela de variantes (SKUs).
 * * Arquitetura: Implementa verificações defensivas em cada coluna para garantir que a 
 * estrutura necessária para o e-commerce esteja presente sem causar conflitos de migração.
 * * Lógica de Negócio: Centraliza o controle de estoque (quantity), precificação (price/sale_price) 
 * e atributos dinâmicos (options) em uma única entidade vinculada ao produto principal.
 */
return new class extends Migration
{
    /**
     * Executa as verificações e cria as colunas faltantes em 'product_variants'.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            
            // 1. Identificação Logística (SKU)
            if (!Schema::hasColumn('product_variants', 'sku')) {
                $table->string('sku')->nullable();
            }

            // 2. Estrutura Financeira Base
            if (!Schema::hasColumn('product_variants', 'price')) {
                $table->decimal('price', 10, 2)->default(0);
            }

            // 3. Gestão de Inventário (Estoque Físico)
            if (!Schema::hasColumn('product_variants', 'quantity')) {
                $table->integer('quantity')->default(0);
            }

            // 4. Atributos Dinâmicos (JSON) - Ex: {"Cor": "Preto", "Voltagem": "220v"}
            if (!Schema::hasColumn('product_variants', 'options')) {
                $table->json('options')->nullable();
            }

            // 5. Galeria de Mídia Específica da Variante
            if (!Schema::hasColumn('product_variants', 'images')) {
                $table->json('images')->nullable();
            }

            // 6. Controle de Exibição: Define a variante padrão ao carregar o produto
            if (!Schema::hasColumn('product_variants', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            
            // 7. Estrutura Financeira Promocional
            if (!Schema::hasColumn('product_variants', 'sale_price')) {
                $table->decimal('sale_price', 10, 2)->nullable();
            }

            // 8. Visual: Foto de capa exclusiva do SKU
            if (!Schema::hasColumn('product_variants', 'image')) {
                $table->string('image')->nullable();
            }
        });
    }

    /**
     * Reverte as migrações, removendo as colunas de forma segura.
     */
    public function down(): void
    {
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