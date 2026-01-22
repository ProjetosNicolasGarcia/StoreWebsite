<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateOrdersTable
 * * Define a estrutura para o fechamento de vendas (Cabeçalho do Pedido).
 * * Arquitetura: Centraliza os dados financeiros, logísticos e o estado atual da transação.
 * * Lógica de Negócio: Utiliza snapshots (como o endereço em JSON) para garantir que
 * o registro do pedido permaneça fiel ao momento da compra, independente de alterações 
 * posteriores no perfil do usuário.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'orders'.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com o Cliente
            // Vincula o pedido ao usuário que realizou a compra.
            $table->foreignId('user_id')->constrained();

            // Snapshot de Logística
            // Armazena o endereço completo em formato JSON.
            // MOTIVO: Preservar o local de entrega exato contratado, protegendo o histórico
            // contra edições no cadastro de endereços do usuário.
            $table->json('address_json'); 

            // Estrutura Financeira
            // 'total_amount': Valor total final da venda (Produtos + Frete - Descontos).
            // 'shipping_cost': Custo de envio calculado no momento do checkout.
            $table->decimal('total_amount', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);

            // Ciclo de Vida do Pedido
            // 'status': Gerencia o fluxo da venda desde a intenção até a conclusão ou cancelamento.
            $table->enum('status', [
                'pending',   // Aguardando confirmação de pagamento
                'paid',      // Pagamento confirmado, aguardando envio
                'shipped',   // Mercadoria despachada
                'delivered', // Confirmada a entrega ao destino
                'canceled'   // Venda interrompida
            ])->default('pending');

            // Rastreabilidade
            // Código fornecido pela transportadora para acompanhamento logístico.
            $table->string('tracking_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de pedidos.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};