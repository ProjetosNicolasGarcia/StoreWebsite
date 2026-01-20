<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $guarded = [];

    // --- ESSA PARTE É CRUCIAL ---
    protected $casts = [
        'options' => 'array', // <--- Se faltar isso, nada aparece
        'images' => 'array',
        'is_default' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
    ];
    // ----------------------------

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFinalPriceAttribute()
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    public function isOnSale(): bool
    {
        if (!$this->sale_price || $this->sale_price >= $this->price) return false;
        $now = now();
        if ($this->sale_start_date && $now->lt($this->sale_start_date)) return false;
        if ($this->sale_end_date && $now->gt($this->sale_end_date)) return false;
        return true;
    }
}