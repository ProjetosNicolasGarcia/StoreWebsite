<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateReviewsTable
 * * Define a estrutura para o sistema de feedback e prova social da loja.
 * * Arquitetura: Relaciona usuários a produtos através de notas e comentários.
 * * Lógica de Negócio: Essencial para gerar confiança em novos compradores e 
 * fornecer métricas de qualidade para a administração do catálogo.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'reviews'.
     */
    public function up(): void
{
    Schema::create('reviews', function (Blueprint $table) {
        $table->id();

        // Relacionamento com o Autor
        // 'cascade': Se o usuário for removido, suas avaliações são excluídas para manter a integridade dos dados.
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        // Relacionamento com o Produto
        // 'cascade': Se o produto sair do catálogo permanentemente, suas avaliações deixam de existir.
        $table->foreignId('product_id')->constrained()->onDelete('cascade');

        // Métricas de Satisfação
        // 'rating': Valor numérico (geralmente de 1 a 5) que representa a nota dada ao item.
        $table->integer('rating');

        // Conteúdo Descritivo
        // 'comment': Depoimento textual opcional sobre a experiência de compra ou qualidade do produto.
        $table->text('comment')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverte as migrações, removendo a estrutura de avaliações.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};