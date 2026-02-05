<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: CreateAddressesTable
 * * Define a estrutura para armazenamento de endereços dos usuários.
 * * Arquitetura: Suporta múltiplos endereços por usuário (Cobrança, Entrega, Trabalho).
 * * Lógica de Negócio: Essencial para o cálculo de frete e integridade da entrega. 
 * Utiliza o conceito de 'is_default' para agilizar o fluxo de checkout.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para criação da tabela 'addresses'.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com o usuário: Remove os endereços automaticamente se o usuário for excluído
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Metadados do Endereço
            $table->string('title')->nullable(); // Ex: "Casa", "Trabalho", "Apartamento Praia"
            
            // Dados Geográficos e Logísticos
            $table->string('zip_code');      // CEP (Base para cálculo de frete via API)
            $table->string('street');        // Logradouro
            $table->string('number');        // Número da residência
            $table->string('complement')->nullable(); // Apto, Bloco, Fundos
            $table->string('neighborhood');  // Bairro
            $table->string('city');          // Cidade
            $table->string('state', 2);      // UF (Limitado a 2 caracteres, ex: SP, RJ)
            
            // Preferência de Entrega
            $table->boolean('is_default')->default(false); // Define o endereço sugerido no checkout
            
            $table->timestamps();
        });
    }

    /**
     * Reverte as migrações, removendo a estrutura de endereços.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};