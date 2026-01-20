<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $guarded = [];

    // Relacionamento com o Produto Pai (Mantém para pegar o Nome, Slug, etc)
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // --- NOVO: Relacionamento com a Variante (Onde está o Preço e Estoque real) ---
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Relacionamento com Usuário
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}