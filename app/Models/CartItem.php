<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $guarded = [];

    // --- ADICIONE ISTO AQUI ---
    
    // Relacionamento: Um item do carrinho pertence a um Produto
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // (Opcional) Relacionamento: Pode pertencer a um Usuário
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}