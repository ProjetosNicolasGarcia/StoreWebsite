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
    Schema::create('cart_items', function (Blueprint $table) {
        $table->id();
        
        // 1. MUDANÇA: Adiciona nullable() para permitir salvar sem estar logado
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        
        // 2. NOVO: Adiciona o ID da sessão para rastrear o visitante
        $table->string('session_id')->nullable()->index(); 

        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
        $table->integer('quantity')->default(1);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
