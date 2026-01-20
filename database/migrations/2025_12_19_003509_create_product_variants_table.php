<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('product_variants', function (Blueprint $table) {
        $table->id();
        // Vínculo com o produto pai
        $table->foreignId('product_id')->constrained()->onDelete('cascade');

        // Identificadores e Opções
        $table->string('sku')->unique()->nullable();
        $table->json('options')->nullable(); // Onde salvamos Cor: Azul, Tamanho: P

        // Financeiro
        $table->decimal('price', 10, 2);
        $table->decimal('sale_price', 10, 2)->nullable();

        // Estoque
        $table->integer('quantity')->default(0);

        // Mídia específica da variante
        $table->string('image')->nullable();
        $table->json('images')->nullable();

        // Configuração
        $table->boolean('is_default')->default(false);

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
