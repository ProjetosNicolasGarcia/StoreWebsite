<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateCollectionsTable
 * * Define a estrutura para agrupamentos promocionais e sazonais de produtos.
 * * Arquitetura: Diferente das categorias, as coleções são entidades de marketing 
 * transversais, permitindo que um produto pertença a múltiplos grupos temáticos.
 * * Lógica de Negócio: Centraliza as configurações de destaque para a Landing Page (Home),
 * suportando elementos visuais e descritivos para campanhas específicas.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'collections'.
     */
    public function up(): void
{
    Schema::create('collections', function (Blueprint $table) {
        $table->id();

        // Identificação e SEO
        // 'title': Nome comercial da coleção (ex: "Seleção de Verão").
        // 'slug': Identificador único para a URL da listagem da coleção.
        $table->string('title');
        $table->string('slug')->unique();

        // Identidade Visual e Conteúdo
        $table->string('image_url')->nullable(); // Banner ou miniatura da coleção
        $table->text('description')->nullable(); // Texto de apoio para a página da coleção

        // Regras de Exibição e Marketing
        // 'is_active': Controla a visibilidade pública da coleção.
        $table->boolean('is_active')->default(true);

        // 'featured_on_home': Flag que sinaliza para o controller/componente da Home 
        // se esta coleção deve ocupar um slot de destaque na página inicial.
        $table->boolean('featured_on_home')->default(false);

        $table->timestamps();
    });
}

    /**
     * Reverte as migrações, removendo a estrutura de coleções.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};