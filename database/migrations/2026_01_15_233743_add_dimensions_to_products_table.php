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
        Schema::table('products', function (Blueprint $table) {
            // MUDANÇA: Usei 'base_price' como referência, pois 'stock' não existe na sua tabela
            $table->decimal('weight', 10, 3)->nullable()->after('base_price'); 
            
            $table->integer('height')->nullable()->after('weight'); // Altura
            $table->integer('width')->nullable()->after('height');  // Largura
            $table->integer('length')->nullable()->after('width');  // Comprimento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight', 'height', 'width', 'length']);
        });
    }
};