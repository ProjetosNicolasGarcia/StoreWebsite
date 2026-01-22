<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateTransactionsTable
 * * Define a estrutura para o registro de movimentações financeiras.
 * * Arquitetura: Atua como a camada de persistência entre a loja e os Gateways de Pagamento (ex: Stripe, Mercado Pago, Pagar.me).
 * * Lógica de Negócio: Permite o rastreio de múltiplas tentativas de pagamento para um mesmo pedido,
 * armazenando metadados críticos para conciliação bancária e suporte ao cliente.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'transactions'.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com o Pedido
            // 'cascade': Se o pedido for removido, o histórico de transações é limpo automaticamente.
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            // Identificação do Provedor
            $table->string('gateway');         // Nome do serviço (ex: 'stripe', 'mercadopago')
            $table->string('gateway_id')->nullable(); // ID da transação no sistema do provedor (Transaction ID)

            // Dados do Pagamento
            $table->string('payment_method'); // Método utilizado (ex: 'credit_card', 'pix', 'boleto')
            $table->decimal('amount', 10, 2); // Valor exato processado nesta transação
            $table->string('status');         // Estado da transação (ex: 'approved', 'rejected', 'refunded')

            // Metadados para Pagamentos Assíncronos
            // Utilizados para exibir instruções de pagamento ao cliente após o checkout.
            $table->text('pix_qr_code')->nullable(); // Payload ou Link do QR Code para cópia e cola
            $table->text('boleto_url')->nullable();  // Link direto para o PDF do boleto bancário

            // Log de Resposta e Depuração
            // 'gateway_response': Snapshot JSON de toda a resposta do provedor.
            // MOTIVO: Fundamental para auditoria técnica e resolução de disputas (Chargebacks).
            $table->json('gateway_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de transações.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};