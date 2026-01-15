<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // [ADICIONADO] Importante para manipular as datas

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    // IMPORTANTE: Garante que o campo images seja tratado como array pelo Laravel
    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        // [ADICIONADO] Garante que o Laravel entenda esses campos como datas reais
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
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

    // ==========================================================
    // NOVAS FUNÇÕES PARA A LÓGICA DE PROMOÇÃO
    // ==========================================================

    /**
     * Verifica se o produto está em promoção no momento atual.
     */
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
        // Alterado de $this->price para $this->base_price
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
        // Se tiver oferta ativa e válida
        if ($this->sale_price && $this->sale_price < $this->base_price) {
            // Aqui você pode adicionar verificação de data (sale_start_date / sale_end_date) se quiser
            return $this->sale_price;
        }
        return $this->base_price;
    }

}