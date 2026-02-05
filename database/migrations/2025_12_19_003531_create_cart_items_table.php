<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateCartItemsTable
 * * Define a estrutura para persistência do carrinho de compras.
 * * Arquitetura: Suporta carrinhos híbridos (Usuários Autenticados e Visitantes/Guests).
 * * Lógica de Negócio: Utiliza a combinação de 'user_id' e 'session_id' para garantir que 
 * os itens não sejam perdidos durante o fluxo de navegação, permitindo a posterior 
 * conversão do carrinho de visitante em carrinho de cliente após o login.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'cart_items'.
     */
    public function up(): void
{
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        
        // Identificação do Proprietário
        // 'user_id': Anulável para suportar a adição de produtos ao carrinho antes do login.
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        
        // 'session_id': Identificador único da sessão do navegador. 
        // Indexado para garantir performance na recuperação de itens de visitantes.
        $table->string('session_id')->nullable()->index(); 

        // Relacionamentos com o Catálogo
        // Vincula o item ao produto pai e à variante específica (SKU) selecionada.
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_variant_id')
            ->nullable()
            ->constrained('product_variants')
            ->onDelete('cascade');

        // Dados do Item
        $table->integer('quantity')->default(1);
        
        $table->timestamps();
    });
}

    /**
     * Reverte as migrações, removendo a estrutura de itens do carrinho.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};