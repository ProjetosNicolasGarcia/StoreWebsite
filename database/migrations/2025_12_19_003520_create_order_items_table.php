<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateOrderItemsTable
 * * Define a estrutura para os produtos e variantes vinculados a um pedido.
 * * Arquitetura: Atua como o registro detalhado (Linhas do Pedido). 
 * * Lógica de Negócio: Armazena o 'unit_price' de forma estática. 
 * MOTIVO: Garante que o valor pago pelo cliente seja preservado, mesmo que o preço 
 * do produto ou da variante sofra alterações no catálogo futuramente.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'order_items'.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com o Pedido Pai
            // 'cascade': Se o pedido for excluído, os itens vinculados são removidos automaticamente.
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Relacionamentos com o Catálogo
            // 'product_id': Vínculo com a entidade genérica do produto.
            $table->foreignId('product_id')->constrained();

            // 'product_variant_id': Vínculo com o SKU específico (Cor, Tamanho, etc).
            // Definido como nullable para suportar produtos que eventualmente não possuam variantes.
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants');

            // Dados Transacionais (Snapshot)
            // 'quantity': Volume de itens adquiridos nesta linha.
            $table->integer('quantity');

            // 'unit_price': Preço unitário aplicado no momento exato do fechamento da compra.
            // Fundamental para auditoria financeira e cálculos de estorno/reembolso.
            $table->decimal('unit_price', 10, 2);
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de itens do pedido.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};