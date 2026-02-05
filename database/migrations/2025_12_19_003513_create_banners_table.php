<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateBannersTable
 * * Define a estrutura para gestão de elementos visuais promocionais (Banners/Sliders).
 * * Arquitetura: Centraliza as mídias de destaque da página inicial e outras seções da loja.
 * * Lógica de Negócio: Permite o controle dinâmico de campanhas de marketing, 
 * suportando links de redirecionamento e ordenação personalizada para priorizar ofertas.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'banners'.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            
            // Mídia e Conteúdo
            $table->string('image_url');      // Caminho do arquivo de imagem no storage
            $table->string('title')->nullable(); // Texto de apoio ou Alt Text para acessibilidade/SEO
            
            // Interatividade
            $table->string('link_url')->nullable(); // URL de destino ao clicar no banner (ex: /ofertas)
            
            // Regras de Exibição e Ordenação
            // 'position': Define a sequência de exibição (ex: Banner 1, Banner 2).
            $table->integer('position')->default(0);
            
            // 'is_active': Permite agendar ou desativar banners sem excluí-los do banco.
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de banners.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};