<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens; 

/**
 * Class User
 * * Entidade central de identidade do sistema.
 * * Arquitetura: Implementa 'FilamentUser' para gerenciar permissões administrativas
 * e utiliza 'Notifiable' para comunicações de transações e segurança.
 * * Lógica de Negócio: Unifica perfis de clientes (compras/endereços) e operadores do painel.
 *
 * @package App\Models
 */
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Campos atribuíveis em massa.
     * * Inclui campos de login social (google_id) e dados cadastrais 
     * necessários para faturamento e logística (cpf, birth_date).
     * * Campos de 2FA são preenchidos via métodos internos de segurança.
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
     * Atributos ocultos para serialização.
     * * 'two_factor_code': Essencial manter oculto para prevenir exposição via APIs ou logs.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code', 
    ];

    /**
     * Conversão de tipos (Casting).
     * * 'birth_date': Facilita validações de idade mínima para compras.
     * * 'password': Usa o driver nativo do Laravel para hashing automático.
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

    // =========================================================================
    // SEGURANÇA: CONTROLE DE ACESSO (Filament Admin)
    // =========================================================================

    /**
     * Define quem pode acessar o Painel Administrativo.
     * * Regra Atual: Acesso restrito via Whitelist (lista de emails permitidos).
     * * Manutenção: Para sistemas maiores, recomenda-se substituir por Roles/Permissions (Spatie).
     *
     * @param Panel $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->email, [
            'teste@gmail.com', 
        ]);
    }

    // =========================================================================
    // SEGURANÇA: AUTENTICAÇÃO DE DOIS FATORES (2FA)
    // =========================================================================

    /**
     * Gera e persiste um novo código numérico para validação de login.
     * * Define validade curta (10 min) para mitigar ataques de força bruta.
     * * Desabilita timestamps para não mascarar a data de 'última atividade' do usuário.
     */
    public function generateTwoFactorCode()
    {
        $this->timestamps = false; 
        $this->two_factor_code = rand(100000, 999999);
        $this->two_factor_expires_at = now()->addMinutes(10);
        $this->save();
    }

    /**
     * Invalida o código de 2FA após o uso bem-sucedido ou expiração.
     */
    public function resetTwoFactorCode()
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
    }

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Histórico de Pedidos.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Livro de Endereços (Cobrança e Entrega).
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}