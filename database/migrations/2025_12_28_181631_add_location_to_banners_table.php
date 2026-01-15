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
    Schema::table('banners', function (Blueprint $table) {
        // 'hero' = Carrossel do Topo, 'section' = Banner no meio da página
        $table->string('location')->default('hero')->after('title');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            //
        });
    }
};
