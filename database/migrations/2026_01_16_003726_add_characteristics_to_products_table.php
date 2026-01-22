<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddCharacteristicsToProductsTable
 * * Implementa suporte a metadados dinâmicos para especificações técnicas do produto.
 * * Arquitetura: Utiliza o tipo de dado JSON para permitir um esquema flexível (schemaless).
 * * Lógica de Negócio: Ideal para armazenar atributos que variam drasticamente entre categorias 
 * (ex: 'Material' e 'Voltagem' para eletrodomésticos vs 'Autor' e 'Páginas' para livros), 
 * evitando a criação de colunas esparsas e otimizando a manutenção do banco de dados.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar o campo 'characteristics' à tabela 'products'.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Especificações Dinâmicas:
            // 'characteristics': Campo JSON que armazena pares de chave-valor.
            // Exemplo de payload: {"Material": "Aço Escovado", "Garantia": "12 meses", "Cor": "Prata"}
            $table->json('characteristics')->nullable()->after('description');
        });
    }

    /**
     * Reverte as migrações, removendo a coluna de características.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('characteristics');
        });
    }
};