<?php
// app/Models/Review.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id', 
        'product_id', 
        'rating', 
        'comment', 
        'images' // ✏️ alterado
    ];

    protected $casts = [
        'images' => 'array', // ✅ novo
    ];

    /**
     * Relacionamento com o Autor da avaliação.
     */
    public function user(): BelongsTo // ✅ novo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com o Produto avaliado.
     */
    public function product(): BelongsTo // ✅ novo
    {
        return $this->belongsTo(Product::class);
    }
}