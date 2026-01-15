<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verifica se a coluna já existe antes de adicionar para evitar erros
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('google_id'); // Ajuste o 'after' se necessário
            }
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf')->nullable()->unique()->after('password'); 
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('cpf');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'two_factor_expires_at')) {
                $table->dateTime('two_factor_expires_at')->nullable()->after('two_factor_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id', 
                'avatar', 
                'cpf', 
                'phone', 
                'birth_date', 
                'two_factor_code', 
                'two_factor_expires_at'
            ]);
        });
    }
};