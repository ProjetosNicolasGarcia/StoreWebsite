<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    // 1. Libera todos os campos para serem salvos (evita erro de mass assignment)
    protected $guarded = [];

    // 2. Define o relacionamento: Uma variante pertence a um Produto
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}