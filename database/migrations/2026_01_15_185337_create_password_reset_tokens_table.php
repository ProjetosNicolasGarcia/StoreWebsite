<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreatePasswordResetTokensTable
 * * Define a estrutura para armazenamento de tokens temporários de recuperação de senha.
 * * Arquitetura: Utiliza o e-mail como chave primária para garantir que cada usuário 
 * possua apenas um token de redefinição ativo por vez.
 * * Segurança: Parte integrante do subsistema de autenticação do Laravel, permitindo 
 * a validação de identidade via e-mail antes da troca de credenciais.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'password_reset_tokens'.
     */
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            // Identificação do Solicitante:
            // O e-mail é definido como chave primária para evitar duplicidade de registros 
            // e otimizar a busca durante o processo de validação.
            $table->string('email')->primary();

            // Segurança da Transação:
            // 'token': Armazena o hash único enviado ao usuário. 
            // Essencial para validar a legitimidade da solicitação de troca de senha.
            $table->string('token');

            // Gestão de Validade:
            // 'created_at': Timestamp de geração do token. 
            // Utilizado pelo sistema para invalidar solicitações expiradas (TTL).
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de tokens de redefinição.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};