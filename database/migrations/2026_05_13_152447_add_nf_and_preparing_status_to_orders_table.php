// database/migrations/2026_05_13_000000_add_nf_and_preparing_status_to_orders_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionando as colunas da Nota Fiscal
        Schema::table('orders', function (Blueprint $table) {
            $table->string('nf_code')->nullable()->after('tracking_code');
            $table->text('nf_url')->nullable()->after('nf_code');
        });

        // 2. Modificando o ENUM de status nativamente via DB statement 
        // (O Laravel/Doctrine dbal tem limitações com ENUM, o statement SQL é a forma mais segura)
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'preparing', 'shipped', 'delivered', 'canceled', 'refunded') DEFAULT 'pending' NOT NULL");
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['nf_code', 'nf_url']);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'paid', 'shipped', 'delivered', 'canceled', 'refunded') DEFAULT 'pending' NOT NULL");
    }
};