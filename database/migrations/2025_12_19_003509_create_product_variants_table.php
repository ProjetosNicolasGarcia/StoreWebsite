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
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->string('sku')->unique();
        
        // MUDANÇA AQUI:
        $table->string('variation_type_1')->nullable(); // Ex: Cor, Voltagem, Material
        $table->string('variation_type_2')->nullable(); // Ex: Tamanho, Capacidade, Peso
        
        $table->decimal('price', 10, 2);
        $table->integer('stock_quantity')->default(0);
        $table->string('image_url')->nullable();
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
