<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Prunable; // [ESCALABILIDADE]
use Illuminate\Database\Eloquent\Builder;

/**
 * Class CartItem
 * Representa um item individual no carrinho de compras.
 * Otimizado com limpeza automática (Pruning) para não inflar o banco de dados.
 */
class CartItem extends Model
{
    use Prunable; // Habilita a limpeza automática de registros obsoletos

    /**
     * Configuração de Mass Assignment.
     */
    protected $guarded = [];

    /**
     * Conversão de tipos (Casting).
     * [OTIMIZAÇÃO] Garante tipos nativos para cálculos matemáticos mais rápidos.
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // ACESSORES (Helpers Calculados)
    // =========================================================================

    /**
     * Retorna o subtotal deste item (Preço Atual x Quantidade).
     * Inteligente: Detecta se deve usar o preço da variante ou do produto.
     * Uso no Blade: $item->total
     */
    public function getTotalAttribute()
    {
        // 1. Tenta pegar o preço da variante (prioridade)
        if ($this->relationLoaded('variant') && $this->variant) {
            return $this->variant->final_price * $this->quantity;
        }

        // 2. Fallback para o produto pai (se carregado)
        if ($this->relationLoaded('product') && $this->product) {
            $price = $this->product->isOnSale() ? $this->product->sale_price : $this->product->base_price;
            return $price * $this->quantity;
        }

        return 0;
    }

    // =========================================================================
    // ESCALABILIDADE (Limpeza Automática)
    // =========================================================================

    /**
     * Define a query para excluir carrinhos abandonados antigos.
     * Executado via comando: php artisan model:prune
     */
    public function prunable(): Builder
    {
        // Regra: Apagar itens de carrinho não modificados há mais de 30 dias.
        // Isso impede que a tabela cresça infinitamente com sessões de visitantes (bots/crawlers).
        return static::where('updated_at', '<=', now()->subDays(30));
    }
}