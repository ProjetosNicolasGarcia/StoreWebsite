<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail; // Descomentar se usar verificação de email padrão
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 * * Entidade central de identidade do sistema.
 * Unifica perfis de clientes e administradores.
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Campos preenchíveis em massa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'cpf',
        'phone',
        'birth_date',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    /**
     * Atributos ocultos.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code', // Segurança: nunca expor o código
    ];

    /**
     * Casting de atributos (Sintaxe Laravel 10).
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'two_factor_expires_at' => 'datetime',
    ];

    // =========================================================================
    // SEGURANÇA: CONTROLE DE ACESSO (Filament Admin)
    // =========================================================================

    public function canAccessPanel(Panel $panel): bool
    {
        // Regra simples: Apenas este email ou domínios específicos
        // Idealmente, evoluir para: return $this->hasRole('admin');
        return in_array($this->email, [
            'tester@gmail.com',
            // 'admin@sualoja.com',
        ]);
    }

    // =========================================================================
    // SEGURANÇA: AUTENTICAÇÃO DE DOIS FATORES (2FA)
    // =========================================================================

    public function generateTwoFactorCode()
    {
        $this->timestamps = false; // Não altera 'updated_at'
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

    /**
     * Verifica se o código fornecido é válido e não expirou.
     */
    public function hasValidTwoFactorCode($code)
    {
        return $this->two_factor_code == $code && 
               $this->two_factor_expires_at && 
               $this->two_factor_expires_at->gt(now());
    }

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    // Se existir carrinho persistente na BD (opcional, dependendo da sua lógica de Cart)
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}