<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateJobsTable
 * * Define a infraestrutura para processamento assíncrono e tarefas em segundo plano.
 * * Arquitetura: Utiliza o driver de banco de dados para gerenciar filas (Queues) e monitorar 
 * o progresso de execuções em lote (Batching).
 * * Utilidade: Essencial para tarefas pesadas que não devem bloquear o ciclo de resposta 
 * do usuário (ex: envio de e-mails de confirmação, processamento de imagens ou relatórios).
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação das tabelas 'jobs', 'job_batches' e 'failed_jobs'.
     */
    public function up(): void
    {
        // =====================================================================
        // TABELA: JOBS (Fila Ativa)
        // =====================================================================
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index(); // Nome da fila (ex: default, high, low)
            $table->longText('payload');      // Dados serializados da tarefa
            $table->unsignedTinyInteger('attempts'); // Contador de tentativas de execução
            $table->unsignedInteger('reserved_at')->nullable(); // Timestamp de bloqueio por um worker
            $table->unsignedInteger('available_at'); // Quando a tarefa estará pronta para execução
            $table->unsignedInteger('created_at');
        });

        // =====================================================================
        // TABELA: JOB_BATCHES (Processamento em Lote)
        // =====================================================================
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');           // Nome identificador do lote
            $table->integer('total_jobs');    // Total de tarefas no lote
            $table->integer('pending_jobs');  // Tarefas aguardando execução
            $table->integer('failed_jobs');   // Tarefas que retornaram erro
            $table->longText('failed_job_ids'); // IDs das tarefas falhas para debug/retry
            $table->mediumText('options')->nullable(); // Configurações de callback (then, catch, finally)
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // =====================================================================
        // TABELA: FAILED_JOBS (Histórico de Erros)
        // =====================================================================
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // Identificador único da falha
            $table->text('connection');       // Driver utilizado (ex: database, redis)
            $table->text('queue');            // Fila onde ocorreu o erro
            $table->longText('payload');      // Dados da tarefa que falhou
            $table->longText('exception');    // Stack trace completo do erro/exceção
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverte as migrações, removendo as estruturas de gerenciamento de tarefas.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};