<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateCollectionProductTable
 * * Define a tabela intermediária (Pivot) para o relacionamento Muitos-para-Muitos.
 * * Arquitetura: Conecta as entidades 'Collections' e 'Products'.
 * * Lógica de Negócio: Permite a flexibilidade total do catálogo, onde um produto 
 * pode ser associado a diversas campanhas de marketing (Coleções) simultaneamente, 
 * sem a rigidez da árvore de categorias principal.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela pivot 'collection_product'.
     */
    public function up(): void
{
    Schema::create('collection_product', function (Blueprint $table) {
        $table->id();

        // Chaves Estrangeiras e Integridade
        // 'collection_id': Vínculo com a coleção de marketing.
        // 'cascadeOnDelete': Se a coleção for excluída, as associações com os produtos são removidas.
        $table->foreignId('collection_id')->constrained()->cascadeOnDelete();

        // 'product_id': Vínculo com o produto do catálogo.
        // 'cascadeOnDelete': Se o produto for removido, ele é automaticamente retirado de todas as coleções.
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    });
}

    /**
     * Reverte as migrações, removendo a tabela de associação.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_product');
    }
};