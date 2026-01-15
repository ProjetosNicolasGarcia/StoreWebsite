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
        Schema::table('cart_items', function (Blueprint $table) {
            // 1. Altera o user_id para aceitar nulo (sempre seguro rodar o change)
            $table->foreignId('user_id')->nullable()->change();
            
            // 2. Verifica se a coluna session_id JÁ EXISTE antes de tentar criar
            if (!Schema::hasColumn('cart_items', 'session_id')) {
                $table->string('session_id')->nullable()->after('id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Verifica se a coluna existe antes de tentar apagar
            if (Schema::hasColumn('cart_items', 'session_id')) {
                $table->dropColumn('session_id');
            }
            
            // Reverte o user_id para não nulo (cuidado: pode dar erro se já houver dados nulos)
            // $table->foreignId('user_id')->nullable(false)->change(); 
        });
    }
};