<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Order
 * Representa a entidade de fechamento de venda (Pedido).
 * Otimizado para integridade financeira e consultas eficientes.
 */
class Order extends Model
{
    use HasFactory;

    // Constantes para evitar "Magic Strings" e garantir consistência no código
    const STATUS_PENDING = 'pending_payment';
    const STATUS_PAID = 'paid';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    protected $guarded = [];

    /**
     * Conversão de tipos (Casting).
     * [OTIMIZAÇÃO] 'decimal:2' garante precisão matemática para valores monetários.
     * 'datetime' garante que as datas sejam objetos Carbon prontos para formatação.
     */
    protected $casts = [
        'address_json' => 'array',
        'total_price' => 'decimal:2', // Garante precisão financeira (evita float errors)
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime', // Recomendado ter esse campo no banco
    ];

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Atalho para acessar os produtos diretamente, útil para relatórios simples.
     * (Requer pacote staudenmeir/eloquent-has-many-deep ou uso via items.product no controller)
     * Por padrão, mantemos items() que é o padrão do Laravel.
     */

    // =========================================================================
    // SCOPES (Filtros Otimizados)
    // =========================================================================

    /**
     * Filtra apenas pedidos concluídos/pagos.
     * Útil para relatórios de receita.
     */
    public function scopePaid(Builder $query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_SHIPPED]);
    }

    /**
     * Filtra pedidos recentes primeiro.
     */
    public function scopeRecent(Builder $query)
    {
        return $query->latest();
    }

    /**
     * Filtra pedidos de um usuário específico.
     */
    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    // HELPERS DE NEGÓCIO
    // =========================================================================

    /**
     * Verifica se o pedido pode ser cancelado pelo usuário.
     * Regra de Negócio: Só pode cancelar se ainda não foi enviado.
     */
    public function canBeCanceled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID]);
    }

    /**
     * Retorna o status formatado para exibição (Label).
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Aguardando Pagamento',
            self::STATUS_PAID => 'Pago',
            self::STATUS_SHIPPED => 'Enviado',
            self::STATUS_CANCELED => 'Cancelado',
            self::STATUS_REFUNDED => 'Reembolsado',
            default => 'Desconhecido',
        };
    }
    
    /**
     * Define a cor do badge de status (para uso no Filament ou Blade).
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_SHIPPED => 'info',
            self::STATUS_CANCELED => 'danger',
            default => 'secondary',
        };
    }

    // Adicione junto aos outros relacionamentos (user() e items())
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}