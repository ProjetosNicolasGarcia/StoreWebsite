<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateProductVariantsTable
 * * Define a estrutura para os SKUs (Stock Keeping Units) do e-commerce.
 * * Arquitetura: Enquanto a tabela 'products' guarda a essência do item, esta tabela
 * gerencia as variações físicas e comerciais (combinações de Cor, Tamanho, etc).
 * * Lógica de Negócio: Centraliza o controle real de inventário e a precificação dinâmica,
 * permitindo que diferentes variações tenham preços, fotos e níveis de estoque distintos.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'product_variants'.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com o Produto Pai
            // 'cascade': Se o produto principal for excluído, todas as suas variantes são removidas.
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Identificadores e Atributos
            // 'sku': Código único de identificação logística.
            // 'options': Armazena os atributos em JSON (ex: {"Cor": "Azul", "Tamanho": "P"}).
            $table->string('sku')->unique()->nullable();
            $table->json('options')->nullable(); 

            // Estrutura Financeira
            // 'price': Preço base de venda da variante.
            // 'sale_price': Valor promocional (anulável quando não há oferta ativa).
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();

            // Gestão de Inventário
            $table->integer('quantity')->default(0);

            // Mídia Específica
            // Permite que a interface troque a foto do produto conforme o usuário seleciona a variante.
            $table->string('image')->nullable(); // Foto principal da variante
            $table->json('images')->nullable(); // Galeria exclusiva desta variação

            // Regra de Exibição
            // Define qual SKU deve ser carregado por padrão ao abrir a página do produto.
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de variantes.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};