<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddPromotionDatesToVariants
 * * Implementa o controle de agendamento promocional a nível de SKU.
 * * Arquitetura: Adiciona gatilhos temporais à tabela 'product_variants' para automação de ofertas.
 * * Lógica de Negócio: Permite que o sistema alterne automaticamente entre 'price' e 'sale_price' 
 * com base na data atual, possibilitando campanhas de "oferta relâmpago" para variantes específicas 
 * sem necessidade de intervenção manual no banco de dados no momento da virada.
 */
return new class extends Migration
{
    /**
     * Executa a inclusão das colunas de agendamento de oferta.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Gatilho de Início da Promoção:
            // Define o momento exato em que o 'sale_price' passa a ser o valor vigente.
            if (!Schema::hasColumn('product_variants', 'sale_start_date')) {
                $table->dateTime('sale_start_date')->nullable()->after('sale_price');
            }

            // Gatilho de Expiração da Promoção:
            // Define o momento em que a variante retorna ao seu preço base original.
            if (!Schema::hasColumn('product_variants', 'sale_end_date')) {
                $table->dateTime('sale_end_date')->nullable()->after('sale_start_date');
            }
        });
    }

    /**
     * Reverte as migrações, removendo os campos de data.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['sale_start_date', 'sale_end_date']);
        });
    }
};