<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddAuthFieldsToUsersTable
 * * Expande a entidade de usuário para suportar fluxos avançados de autenticação e perfil.
 * * Arquitetura: Utiliza verificações condicionais (`Schema::hasColumn`) para garantir a 
 * idempotência da migration, evitando falhas em ambientes de sincronização contínua.
 * * Lógica de Negócio: Integra campos para Autenticação de Dois Fatores (2FA), Login Social 
 * (Google) e dados de identificação civil (CPF/Data de Nascimento) necessários para faturamento.
 */
return new class extends Migration
{
    /**
     * Executa as alterações na tabela 'users' para inclusão dos novos campos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
            // Integração OAuth (Google):
            // 'google_id': Identificador único fornecido pelo provedor externo.
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }

            // 'avatar': Armazena a URL da imagem de perfil (local ou externa).
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('google_id');
            }

            // Dados Cadastrais e Fiscais:
            // 'cpf': Documento de identificação (único) necessário para emissão de notas fiscais.
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf')->nullable()->unique()->after('password'); 
            }

            // 'phone': Contato para notificações de entrega ou recuperação de conta.
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('cpf');
            }

            // 'birth_date': Utilizado para validação de maioridade e ações de marketing.
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('phone');
            }

            // Segurança: Autenticação de Dois Fatores (2FA):
            // 'two_factor_code': Código numérico temporário gerado para validação de login.
            if (!Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code')->nullable()->after('birth_date');
            }

            // 'two_factor_expires_at': Define a janela de validade do código 2FA.
            if (!Schema::hasColumn('users', 'two_factor_expires_at')) {
                $table->dateTime('two_factor_expires_at')->nullable()->after('two_factor_code');
            }
        });
    }

    /**
     * Reverte as migrações, removendo os campos de autenticação e dados pessoais.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id', 
                'avatar', 
                'cpf', 
                'phone', 
                'birth_date', 
                'two_factor_code', 
                'two_factor_expires_at'
            ]);
        });
    }
};