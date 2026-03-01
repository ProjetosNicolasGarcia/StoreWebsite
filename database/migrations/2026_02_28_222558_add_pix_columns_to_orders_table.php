<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_id')->nullable()->after('payment_method')->comment('ID da transação no gateway');
            $table->text('pix_qr_code')->nullable()->after('payment_id')->comment('Código Copia e Cola do PIX');
            $table->text('pix_qr_code_base64')->nullable()->after('pix_qr_code')->comment('Imagem Base64 do QR Code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_id', 'pix_qr_code', 'pix_qr_code_base64']);
        });
    }
};