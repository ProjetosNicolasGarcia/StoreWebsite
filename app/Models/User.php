<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes; // Mantivemos o SoftDeletes (Essencial para sua feature)
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Laravel\Sanctum\HasApiTokens; // <--- REMOVIDO: Causa do erro

/**
 * Class User
 * Entidade central de identidade do sistema.
 */
class User extends Authenticatable implements FilamentUser
{
    // <--- REMOVIDO: HasApiTokens
    use HasFactory, Notifiable, SoftDeletes; 

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
        'two_factor_code',
    ];

    /**
     * Casting de atributos.
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
        $this->timestamps = false;
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
    
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}