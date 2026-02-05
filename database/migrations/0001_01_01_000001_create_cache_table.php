<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateCacheTable
 * * Define a estrutura para o driver de cache baseado em Banco de Dados.
 * * Arquitetura: Utilizada para persistir dados temporários e gerenciar travas de concorrência.
 * * Utilidade: Melhora o tempo de resposta da aplicação ao evitar reprocessamento de queries pesadas 
 * ou chamadas externas frequentes.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação das tabelas 'cache' e 'cache_locks'.
     */
    public function up(): void
    {
        // =====================================================================
        // TABELA: CACHE
        // =====================================================================
        Schema::create('cache', function (Blueprint $table) {
            // Chave única de identificação do item cacheado
            $table->string('key')->primary();
            
            // Conteúdo serializado do dado armazenado
            $table->mediumText('value');
            
            // Timestamp de expiração (Unix) para limpeza automática
            $table->integer('expiration');
        });

        // =====================================================================
        // TABELA: CACHE_LOCKS (Gerenciamento de Atomicidade)
        // =====================================================================
        Schema::create('cache_locks', function (Blueprint $table) {
            // Chave da trava (ex: utilizada em Jobs ou processos críticos)
            $table->string('key')->primary();
            
            // Identificador do processo ou usuário que detém a trava no momento
            $table->string('owner');
            
            // Tempo limite para a trava ser liberada automaticamente em caso de falha
            $table->integer('expiration');
        });
    }

    /**
     * Reverte as migrações, removendo as estruturas de cache.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};