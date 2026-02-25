<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_cart_value', 
        'max_uses', 'used_count', 'valid_from', 'valid_until', 'is_active'
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Valida o cupom e retorna o status detalhado para o frontend.
     */
    public function validateCoupon($cartSubtotal): array
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'Este cupom está pausado ou inativo no momento.'];
        }

        $now = now(); // Agora usará o fuso America/Sao_Paulo corretamente

        if ($this->valid_from && $this->valid_from > $now) {
            return ['valid' => false, 'message' => 'Este cupom só será válido a partir de ' . $this->valid_from->format('d/m/Y \à\s H:i')];
        }

        if ($this->valid_until && $this->valid_until < $now) {
            return ['valid' => false, 'message' => 'Este cupom expirou em ' . $this->valid_until->format('d/m/Y \à\s H:i')];
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return ['valid' => false, 'message' => 'Este cupom já atingiu o limite máximo de usos e esgotou.'];
        }

        if ($this->min_cart_value !== null && $cartSubtotal < $this->min_cart_value) {
            return ['valid' => false, 'message' => 'Adicione mais R$ ' . number_format($this->min_cart_value - $cartSubtotal, 2, ',', '.') . ' no carrinho para usar este cupom.'];
        }

        return ['valid' => true, 'message' => 'Cupom válido.'];
    }

    public function calculateDiscount($cartSubtotal): float
    {
        if ($this->type === 'percentage') {
            return $cartSubtotal * ($this->value / 100);
        }
        return min($this->value, $cartSubtotal);
    }
}