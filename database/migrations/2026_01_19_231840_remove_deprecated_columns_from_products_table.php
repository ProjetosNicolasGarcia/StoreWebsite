<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CleanupProductTableRedundancies
 * * Finaliza o processo de normalização do banco de dados (3ª Forma Normal).
 * * Arquitetura: Remove campos de preço e estoque que se tornaram obsoletos na tabela 
 * 'products' após a implementação da tabela 'product_variants'.
 * * Lógica de Negócio: Garante uma "Single Source of Truth" (Fonte Única da Verdade). 
 * Agora, todo cálculo de valor e verificação de disponibilidade deve ser consultado 
 * diretamente nas variantes do produto.
 */
return new class extends Migration
{
    /**
     * Executa a limpeza de colunas redundantes na tabela 'products'.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remoção de Campos Financeiros Redundantes:
            // O preço agora é gerenciado individualmente por SKU na tabela de variantes.
            if (Schema::hasColumn('products', 'base_price')) {
                $table->dropColumn('base_price');
            }

            if (Schema::hasColumn('products', 'sale_price')) {
                $table->dropColumn('sale_price');
            }

            // Remoção de Regras de Promoção Legadas:
            if (Schema::hasColumn('products', 'sale_start_date')) {
                $table->dropColumn(['sale_start_date', 'sale_end_date']);
            }
            
            // Remoção de Controle de Inventário Descentralizado:
            // Evita o erro clássico de ter quantidades diferentes em tabelas distintas.
            if (Schema::hasColumn('products', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }
            if (Schema::hasColumn('products', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }

    /**
     * Reverte as alterações, recriando as colunas como nullable.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Recria os campos como anuláveis para permitir o rollback sem falhas de integridade,
            // caso existam registros que dependam dessas colunas no código legado.
            $table->decimal('base_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
        });
    }
};