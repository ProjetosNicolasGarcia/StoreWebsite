<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: AddOfferFieldsToProductsTable
 * * Implementa suporte nativo para campanhas de desconto diretamente no produto pai.
 * * Arquitetura: Utiliza verificações defensivas (`Schema::hasColumn`) para prevenir erros 
 * de execução em ambientes onde a estrutura possa ter sido alterada manualmente.
 * * Lógica de Negócio: Introduz a precificação baseada em tempo, permitindo que o sistema 
 * ative e desative promoções automaticamente sem intervenção manual contínua.
 */
return new class extends Migration
{
    /**
     * Executa as migrações para adicionar os campos de oferta à tabela 'products'.
     */
    public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        // Gestão de Preço Promocional:
        // 'sale_price': Define o valor reduzido do item durante a campanha.
        if (!Schema::hasColumn('products', 'sale_price')) {
            $table->decimal('sale_price', 10, 2)->nullable()->after('base_price');
        }

        // Agendamento de Oferta (Início):
        // 'sale_start_date': Timestamp que marca o gatilho para o início do preço promocional.
        if (!Schema::hasColumn('products', 'sale_start_date')) {
            // Lógica de Posicionamento Dinâmico: Garante a organização visual das colunas no banco.
            $after = Schema::hasColumn('products', 'sale_price') ? 'sale_price' : 'base_price';
            $table->dateTime('sale_start_date')->nullable()->after($after);
        }

        // Agendamento de Oferta (Fim):
        // 'sale_end_date': Timestamp de expiração da oferta. 
        // Após esta data, o sistema volta a considerar apenas o 'base_price'.
        if (!Schema::hasColumn('products', 'sale_end_date')) {
            $after = Schema::hasColumn('products', 'sale_start_date') ? 'sale_start_date' : 'base_price';
            $table->dateTime('sale_end_date')->nullable()->after($after);
        }
    });
}

    /**
     * Reverte as migrações, removendo as colunas de oferta.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'sale_start_date', 'sale_end_date']);
        });
    }
};