<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderItem
 * Representa a linha do pedido (Produto + Quantidade + Preço Congelado).
 * Otimizado para leitura rápida e precisão financeira.
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Desativa timestamps automáticos, pois esta tabela geralmente não possui
     * created_at/updated_at (verifique sua migration, mas é o padrão para itens pivô/detalhe).
     * Isso economiza performance no banco de dados.
     */
    public $timestamps = false;

    protected $guarded = [];

    /**
     * Conversão de tipos (Casting).
     * [OTIMIZAÇÃO] 'decimal:2' é vital para cálculos financeiros sem erros de arredondamento.
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relacionamento com a Variante (SKU).
     * Essencial para saber se o cliente comprou o "P" ou "M", "Azul" ou "Vermelho".
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // =========================================================================
    // ACESSORES (Helpers Calculados)
    // =========================================================================

    /**
     * Retorna o subtotal desta linha (Preço Unitário x Quantidade).
     * Uso: $item->total
     */
    public function getTotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }
}