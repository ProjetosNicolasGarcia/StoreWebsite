<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddGalleryToProductsTable
 * * Expande a capacidade visual do produto para suportar múltiplas mídias.
 * * Arquitetura: Utiliza um campo JSON para armazenar uma coleção de URLs.
 * * Lógica de Negócio: Enquanto 'image_url' define a foto principal de exibição (thumbnail/capa),
 * o campo 'gallery' armazena o conjunto de fotos complementares para o carrossel de detalhes 
 * na página do produto (PDP), otimizando a entrega de múltiplos arquivos em uma única consulta.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar o campo 'gallery' à tabela 'products'.
     */
    public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Galeria de Mídia:
        // 'gallery': Lista de caminhos de arquivos ou URLs em formato JSON.
        // Exemplo de uso: ["path/to/img1.jpg", "path/to/img2.jpg"]
        $table->json('gallery')->nullable()->after('image_url');
    });
}

    /**
     * Reverte as migrações, removendo a coluna 'gallery'.
     */
    public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('gallery');
    });
}
};