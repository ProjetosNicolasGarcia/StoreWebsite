<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: UpdateCartItemsForGuests
 * * Refatoração da tabela 'cart_items' para suportar fluxos de compra sem login (Guest Checkout).
 * * Arquitetura: Transforma a obrigatoriedade da posse do carrinho, permitindo o rastreio via sessão.
 * * Lógica de Negócio: Essencial para reduzir a fricção no funil de vendas, permitindo que o usuário 
 * adicione produtos ao carrinho antes de se identificar ou criar uma conta.
 */
return new class extends Migration
{
    /**
     * Executa as alterações estruturais na tabela 'cart_items'.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Flexibilização de Identidade:
            // 1. Altera 'user_id' para tornar-se opcional (nullable).
            // MOTIVO: Permitir que o registro do item exista sem estar vinculado a um ID de usuário.
            $table->foreignId('user_id')->nullable()->change();
            
            // Rastreabilidade de Visitantes:
            // 2. Implementa 'session_id' como identificador temporário.
            // A verificação 'hasColumn' previne erros de execução caso a coluna tenha sido adicionada em migrations prévias.
            if (!Schema::hasColumn('cart_items', 'session_id')) {
                // 'index': Otimiza a performance de busca dos itens do carrinho durante a navegação do visitante.
                $table->string('session_id')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverte as alterações, removendo o suporte a sessões de visitantes.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Limpeza de Estrutura:
            if (Schema::hasColumn('cart_items', 'session_id')) {
                $table->dropColumn('session_id');
            }
            
            // Nota de Manutenção: A reversão do 'user_id' para 'NOT NULL' é propositalmente omitida 
            // ou comentada para evitar falhas críticas de banco caso existam registros de visitantes 
            // órfãos de ID de usuário no momento do rollback.
            // $table->foreignId('user_id')->nullable(false)->change(); 
        });
    }
};