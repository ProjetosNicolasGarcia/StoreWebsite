<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens; // Caso use Sanctum

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Campos que podem ser preenchidos em massa.
     * Mantém os originais e os novos do checkout/login social.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // --- Novos Campos ---
        'google_id',
        'avatar',
        'cpf',
        'phone',
        'birth_date',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    /**
     * Campos escondidos (segurança).
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code', // Importante esconder o código 2FA
    ];

    /**
     * Conversão de tipos de dados.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    /* |--------------------------------------------------------------------------
    | Lógica de Segurança do Filament (Painel Admin)
    |--------------------------------------------------------------------------
    | AQUI ESTÁ A MUDANÇA CRÍTICA.
    | Só retorna true se o email estiver nesta lista.
    */
    public function canAccessPanel(Panel $panel): bool
    {
        // Coloque aqui APENAS os emails que podem acessar o admin
        return in_array($this->email, [
            'teste@gmail.com', // Seu email (vi nos logs anteriores)
            // 'outro.admin@loja.com',
        ]);
    }

    /* |--------------------------------------------------------------------------
    | Funções para Autenticação de 2 Fatores (2FA)
    |--------------------------------------------------------------------------
    */
    public function generateTwoFactorCode()
    {
        $this->timestamps = false; // Evita alterar o updated_at apenas pelo código
        $this->two_factor_code = rand(100000, 999999);
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();
    }

    public function resetTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }

    /* |--------------------------------------------------------------------------
    | Relacionamentos com o E-commerce
    |--------------------------------------------------------------------------
    */
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}