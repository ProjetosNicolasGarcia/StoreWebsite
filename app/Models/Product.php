<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    // IMPORTANTE: Garante que o campo images seja tratado como array pelo Laravel
    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Nome correto no plural (Muitos-para-Muitos)
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product');
    }

    // ADICIONE ISTO: Relacionamento com Avaliações
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}