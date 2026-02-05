<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddLocationToBannersTable
 * * Estende a funcionalidade da tabela de banners para suportar múltiplas áreas de exibição.
 * * Arquitetura: Adiciona uma camada de segmentação lógica ('location').
 * * Lógica de Negócio: Permite que o mesmo Model gerencie diferentes componentes visuais, 
 * separando banners do carrossel principal (Hero) de banners promocionais entre seções (Section).
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar o campo 'location'.
     */
    public function up(): void
{
    Schema::table('banners', function (Blueprint $table) {
        // Segmentação de Posicionamento:
        // 'hero'    = Exibido no carrossel de destaque no topo da página.
        // 'section' = Exibido em espaços publicitários entre blocos de conteúdo.
        $table->string('location')->default('hero')->after('title');
    });
}

    /**
     * Reverte as migrações, removendo a coluna 'location'.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};