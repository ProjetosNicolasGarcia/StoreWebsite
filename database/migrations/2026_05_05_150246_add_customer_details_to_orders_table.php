<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('customer_first_name')->nullable()->after('address_json');
        $table->string('customer_last_name')->nullable()->after('customer_first_name');
        $table->string('customer_cpf')->nullable()->after('customer_last_name');
        $table->string('customer_phone')->nullable()->after('customer_cpf');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
