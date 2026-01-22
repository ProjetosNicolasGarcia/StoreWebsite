<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateFavoritesTable
 * * Define a estrutura para a lista de desejos (Wishlist) dos usuários.
 * * Arquitetura: Tabela de ligação Muitos-para-Muitos entre Usuários e Produtos.
 * * Lógica de Negócio: Permite que o cliente salve itens de interesse para compra futura. 
 * Implementa uma restrição de unicidade para evitar duplicidade de um mesmo produto 
 * na lista do usuário.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'favorites'.
     */
    public function up(): void
{
    Schema::create('favorites', function (Blueprint $table) {
        $table->id();

        // Relacionamento com o Usuário
        // 'cascade': Se a conta do usuário for excluída, sua lista de favoritos é removida.
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Relacionamento com o Produto
        // 'cascade': Se o produto for removido do catálogo, ele sai automaticamente da lista do usuário.
        $table->foreignId('product_id')->constrained()->onDelete('cascade');

        $table->timestamps();

        // Regra de Integridade: Unicidade
        // Impede que o banco de dados registre o mesmo par (usuário + produto) mais de uma vez.
        $table->unique(['user_id', 'product_id']);
    });
}

    /**
     * Reverte as migrações, removendo a estrutura de favoritos.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};