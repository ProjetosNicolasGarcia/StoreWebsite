<?php

namespace App\Models;

// Mantenha seus imports originais
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// Se você usa API Tokens (Sanctum), mantenha essa linha:
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable implements FilamentUser
{
    // Adicione os Traits que faltarem (ex: HasApiTokens se usar API)
    use HasFactory, Notifiable; 

    /**
     * ATENÇÃO AO $fillable:
     * Mantenha os campos que já existiam (name, email, password)
     * e ADICIONE os novos campos do sistema de login/cadastro.
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
     * ATENÇÃO AO $hidden:
     * Adicione 'two_factor_code' para segurança.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code', // Novo
    ];

    /**
     * ATENÇÃO AO casts():
     * Se seu Laravel for versão 10 ou anterior, isso pode ser uma propriedade protected $casts = [].
     * Se for Laravel 11, é o método casts().
     * Apenas garanta que as novas chaves estejam presentes.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // --- Novos Casts ---
            'birth_date' => 'date',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    /* |--------------------------------------------------------------------------
    | Lógica do Filament (Painel Admin)
    |--------------------------------------------------------------------------
    | MANTENHA A SUA LÓGICA ORIGINAL AQUI.
    | O código anterior usou "return true", o que daria acesso admin para
    | TODOS os clientes da loja. Isso seria perigoso!
    */
    public function canAccessPanel(Panel $panel): bool
    {
        // Exemplo seguro: Apenas quem tem email @suaempresa.com ou flag is_admin
        // return str_ends_with($this->email, '@sualoja.com.br');
        
        // Se você está em ambiente de desenvolvimento local, pode deixar true,
        // mas lembre-se de restringir em produção.
        return true; 
    }

    /* |--------------------------------------------------------------------------
    | Novas Funções para 2FA (Adicione estas funções)
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
    | Relacionamentos (NÃO APAGUE OS SEUS)
    |--------------------------------------------------------------------------
    | Como vi que você tem tabelas de Orders e Addresses, provavelmente
    | você tem (ou precisará) destes relacionamentos:
    */
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    // Se tiver reviews, favorites, etc, mantenha-os aqui.
}