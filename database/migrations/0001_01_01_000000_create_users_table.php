<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateUsersTable
 * * Define a estrutura fundamental de identidade do sistema.
 * * Arquitetura: Suporta autenticação híbrida (Local e OAuth) e persistência de sessões em banco.
 * * Segurança: Armazena tokens de lembrança e metadados de acesso (IP/User Agent).
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação das tabelas 'users' e 'sessions'.
     */
    public function up(): void
    {
        // =====================================================================
        // TABELA: USERS
        // =====================================================================
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            
            // Segurança: Password é anulável para permitir contas criadas via Social Login
            $table->string('password')->nullable(); 
            $table->string('phone')->nullable();

            // Integração OAuth: Identifica a origem do cadastro e o ID externo do provedor
            $table->string('oauth_provider')->default('local'); // Ex: 'google', 'facebook', 'local'
            $table->string('oauth_uid')->nullable();
            $table->string('avatar_url')->nullable();

            // Nível de Acesso: Flag simplificada para privilégios administrativos
            $table->boolean('is_admin')->default(false);

            $table->rememberToken();
            $table->timestamps();
        });

        // =====================================================================
        // TABELA: SESSIONS (Persistência de Estado)
        // =====================================================================
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            
            // Relaciona a sessão a um usuário logado ou nulo (para visitantes/carrinho convidado)
            $table->foreignId('user_id')->nullable()->index();
            
            // Metadados de Auditoria e Segurança
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Dados Serializados da Sessão (Carrinho temporário, Flash Messages, etc)
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverte as migrações, removendo as estruturas criadas.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};