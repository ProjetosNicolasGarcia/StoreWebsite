<?php
// app/Models/Faq.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    // ✅ 'faq_category_id' adicionado ao array para permitir a atribuição
    protected $fillable = ['question', 'answer', 'is_active', 'faq_category_id'];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}