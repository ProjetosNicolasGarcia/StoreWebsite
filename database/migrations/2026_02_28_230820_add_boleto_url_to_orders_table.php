<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Adiciona a coluna para armazenar o link do boleto do Mercado Pago
            $table->string('boleto_url')->nullable()->after('pix_qr_code_base64')->comment('URL externa do Boleto no gateway');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('boleto_url');
        });
    }
};