<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    // Permite que todos os campos sejam preenchidos (igual estava antes)
    protected $guarded = [];

    /**
     * Define a relação: Uma Categoria TEM MUITOS Produtos.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}