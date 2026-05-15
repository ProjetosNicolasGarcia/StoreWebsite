<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Adicionamos as colunas permitindo null, caso o desconto não exista.
            // Posicionadas logo após a coluna genérica 'discount' para organização.
            $table->decimal('promotional_discount', 10, 2)->nullable()->default(0)->after('discount');
            $table->decimal('coupon_discount', 10, 2)->nullable()->default(0)->after('promotional_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['promotional_discount', 'coupon_discount']);
        });
    }
};