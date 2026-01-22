<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateCategoriesTable
 * * Define a estrutura taxonômica do e-commerce.
 * * Arquitetura: Implementa suporte a categorias hierárquicas (Parent/Child).
 * * Lógica de Negócio: Permite a organização lógica de produtos em níveis infinitos,
 * facilitando a navegação do usuário e a indexação para SEO via slugs únicos.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'categories'.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            // Identificação e SEO
            $table->string('name');
            $table->string('slug')->unique(); // Caminho amigável para URLs (ex: /categorias/eletronicos)
            
            // Visual
            $table->string('image_url')->nullable(); // Ícone ou banner representativo da categoria
            
            // Estrutura Hierárquica
            // 'parent_id': Permite criar subcategorias. 
            // 'nullOnDelete': Se a categoria pai for excluída, as filhas tornam-se categorias raiz.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de categorias.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};