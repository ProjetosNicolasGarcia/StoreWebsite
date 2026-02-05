<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddDimensionsToProductsTable
 * * Estende a entidade de produtos com atributos físicos necessários para cálculos logísticos.
 * * Arquitetura: Adiciona campos de peso e dimensões (altura, largura, comprimento) à tabela 'products'.
 * * Lógica de Negócio: Estes dados são fundamentais para a integração com APIs de frete (como Correios ou transportadoras privadas), 
 * permitindo o cálculo preciso do custo de envio com base na cubagem e peso bruto do item.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar campos logísticos à tabela 'products'.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Gestão de Peso:
            // 'weight': Armazena o peso em kg. Utiliza precisão de 3 casas decimais para suportar gramaturas precisas (ex: 0.500kg).
            $table->decimal('weight', 10, 3)->nullable()->after('base_price'); 
            
            // Atributos de Dimensão (Cubagem):
            // 'height': Altura do produto ou da embalagem.
            $table->integer('height')->nullable()->after('weight');
            
            // 'width': Largura do produto ou da embalagem.
            $table->integer('width')->nullable()->after('height');
            
            // 'length': Comprimento do produto ou da embalagem.
            $table->integer('length')->nullable()->after('width');
        });
    }

    /**
     * Reverte as migrações, removendo as colunas de dimensões e peso.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight', 'height', 'width', 'length']);
        });
    }
};