<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateProductsTable
 * * Define a estrutura base para o catálogo de produtos.
 * * Arquitetura: Atua como a entidade "Pai", concentrando metadados genéricos. 
 * Detalhes específicos de estoque e variações de preço por atributo são delegados à tabela de variantes.
 * * SEO: Utiliza slugs únicos para garantir URLs amigáveis e otimizadas para motores de busca.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'products'.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Relacionamento Estrutural
            // Vincula o produto a uma categoria obrigatória para organização do catálogo.
            $table->foreignId('category_id')->constrained();

            // Identificação e SEO
            $table->string('name');
            $table->string('slug')->unique(); // Ex: /produtos/tenis-esportivo-azul
            
            // Conteúdo Informativo
            $table->text('description')->nullable();

            // Precificação Base
            // Nota: Este valor serve como referência inicial ou fallback. 
            // O preço final de venda pode ser sobrescrito na tabela de variantes (ProductVariant).
            $table->decimal('base_price', 10, 2);

            // Mídia e Visual
            $table->string('image_url')->nullable(); // Imagem principal (Capa)

            // Controle de Exibição
            // Permite desativar produtos sem excluí-los, preservando o histórico de pedidos.
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de produtos.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};