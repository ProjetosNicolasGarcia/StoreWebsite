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
        // Torna a coluna opcional, já que agora usamos a tabela pivô category_product
        $table->foreignId('category_id')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->foreignId('category_id')->nullable(false)->change();
    });
}
};
