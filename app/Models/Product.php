<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    /**
     * O $fillable define quais campos podem ser salvos no banco.
     * Adicionamos aqui os novos campos de Dimensões e Peso.
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'image_url',
        'images', // Mantido caso você crie esta coluna para galeria
        'is_active',
        // Campos de Oferta
        'sale_price',
        'sale_start_date',
        'sale_end_date',
        // Campos de Dimensões e Peso (O "Resto" que você adicionou)
        'weight',
        'height',
        'width',
        'length',
        // 'stock', // Descomente se você criar a migration de estoque
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        // Casting para garantir que o peso venha como número (float)
        'weight' => 'decimal:3', 
    ];

    // --- RELACIONAMENTOS ---

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // --- LÓGICA DE PROMOÇÃO ---

    public function isOnSale()
    {
        if (!$this->sale_price) return false;
        
        $now = Carbon::now();
        
        if ($this->sale_start_date && $now->lt($this->sale_start_date)) return false;
        if ($this->sale_end_date && $now->gt($this->sale_end_date)) return false;

        return true;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->base_price || !$this->sale_price) return 0;
        
        return round((($this->base_price - $this->sale_price) / $this->base_price) * 100);
    }

    public function scopeOnSaleQuery($query)
    {
        return $query->whereNotNull('sale_price')
                     ->where('sale_price', '<', \Illuminate\Support\Facades\DB::raw('base_price'))
                     ->where(function ($q) {
                         $q->whereNull('sale_start_date')
                           ->orWhere('sale_start_date', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('sale_end_date')
                           ->orWhere('sale_end_date', '>=', now());
                     });
    }

    public function getFinalPriceAttribute()
    {
        if ($this->sale_price && $this->isOnSale()) {
            return $this->sale_price;
        }
        return $this->base_price;
    }
}