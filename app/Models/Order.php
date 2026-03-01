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
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUNDED = 'refunded';

    // FOI ATUALIZADO AQUI: Uso de $fillable em vez de $guarded para maior segurança
protected $fillable = [
        'user_id',
        'coupon_id',
        'status',
        'total_amount',
        'shipping_cost',
        'shipping_method', 
        'discount',
        'payment_method',
        'payment_id',         
        'pix_qr_code',        
        'pix_qr_code_base64', 
        'address_json',
    ];

    /**
     * Conversão de tipos (Casting).
     * [OTIMIZAÇÃO] 'decimal:2' garante precisão matemática para valores monetários.
     * 'datetime' garante que as datas sejam objetos Carbon prontos para formatação.
     */
    protected $casts = [
        'address_json' => 'array',
        'total_price' => 'decimal:2', 
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime', 
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

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    // =========================================================================
    // SCOPES (Filtros Otimizados)
    // =========================================================================

    public function scopePaid(Builder $query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_SHIPPED]);
    }

    public function scopeRecent(Builder $query)
    {
        return $query->latest();
    }

    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    // HELPERS DE NEGÓCIO
    // =========================================================================

    public function canBeCanceled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID]);
    }

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
}