<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // ESTA LINHA É A SOLUÇÃO: Libera todos os campos para serem salvos
    protected $guarded = [];

    // Relacionamento: Produto pertence a uma Categoria
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relacionamento: Produto tem muitas Variantes
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}