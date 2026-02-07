<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductVariant
 * Representa o SKU (unidade de estoque).
 * Otimizado para integração com Cache e validação robusta de ofertas.
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * [ESCALABILIDADE] Cache Invalidation
     * Sempre que uma variante for criada, atualizada ou deletada,
     * atualiza o timestamp 'updated_at' do produto pai.
     * Isso garante que caches de vitrine sejam limpos automaticamente.
     */
    protected $touches = ['product'];

    protected $casts = [
        'options' => 'array', 
        'images' => 'array',
        'is_default' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'quantity' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Preço final para o consumidor (Calculado).
     * Centraliza a regra de qual preço cobrar.
     */
    public function getFinalPriceAttribute()
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    /**
     * Validação Robusta de Oferta.
     * Verifica Preço, Data de Início e Data de Fim.
     */
    public function isOnSale(): bool
    {
        // 1. Validação de Preço (Segurança)
        // Garante que o preço promocional existe e é menor que o original.
        if (!$this->sale_price || $this->sale_price <= 0 || $this->sale_price >= $this->price) {
            return false;
        }

        $now = now();

        // 2. Validação de Data de Início
        if ($this->sale_start_date && $now->lt($this->sale_start_date)) {
            return false;
        }

        // 3. Validação de Data de Fim
        if ($this->sale_end_date && $now->gt($this->sale_end_date)) {
            return false;
        }

        return true;
    }
}