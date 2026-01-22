<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddDescriptionToBannersTable
 * * Amplia a capacidade informativa dos banners no sistema.
 * * Arquitetura: Adiciona um campo de texto longo para suporte a legendas ou chamadas (Copywriting).
 * * Lógica de Negócio: Permite que os banners exibam informações complementares ao título, 
 * melhorando o SEO e fornecendo contexto adicional para o usuário final sobre a campanha ativa.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar o campo 'description'.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Conteúdo textual secundário:
            // 'description': Armazena textos mais longos que o título, permitindo 
            // descrições de ofertas ou detalhes de validade diretamente na mídia.
            $table->text('description')->nullable()->after('title');
        });
    }

    /**
     * Reverte as migrações, removendo a coluna 'description'.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};