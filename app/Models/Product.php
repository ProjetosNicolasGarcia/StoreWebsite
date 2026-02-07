<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Product
 * Entidade principal do catálogo.
 * Otimizada para delegar cálculos de preço e estoque para as Variantes (SKUs).
 */
class Product extends Model
{
    use HasFactory;

    // Chaves usadas para agrupar visualmente as variantes no frontend
    const COLOR_KEYS = ['Cor', 'Color', 'COR', 'cor', 'color', 'Tonalidade', 'Matiz'];

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'image_url', 
        'gallery', 'is_active', 'characteristics', 
        'weight', 'height', 'width', 'length',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'characteristics' => 'array',
        'gallery' => 'array',
        'weight' => 'decimal:3',
        'height' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
    ];

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function variants(): HasMany
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

    // =========================================================================
    // LÓGICA DE VITRINE (Showcase Logic)
    // =========================================================================

    /**
     * Define qual variante será exibida no card do produto (Home/Listagem/Busca).
     * * Hierarquia de Decisão (Escalável):
     * 1. Variante em PROMOÇÃO VÁLIDA (Menor preço).
     * 2. Variante Padrão (is_default).
     * 3. Menor preço base (Fallback).
     */
    public function getShowcaseVariantAttribute()
    {
        // Safety Check: Garante que as variantes estejam carregadas
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        if ($variants->isEmpty()) return null;

        // 1. Prioridade Absoluta: Ofertas Válidas
        // Filtra usando o método isOnSale() da própria variante para validar datas e preços
        $cheapestPromo = $variants->filter(function ($v) {
            return $v->isOnSale(); 
        })->sortBy('sale_price')->first();

        if ($cheapestPromo) return $cheapestPromo;

        // 2. Prioridade Secundária: Variante marcada como Principal no Admin
        $default = $variants->firstWhere('is_default', true);
        if ($default) return $default;

        // 3. Fallback: Menor Preço Base (evita mostrar variantes caras ou com preço errado)
        return $variants->sortBy('price')->first();
    }

    // =========================================================================
    // ACESSORES DE FRONTEND
    // =========================================================================

    // Atalhos para facilitar o uso no Blade: $product->price
    public function getPriceAttribute() { return $this->showcase_variant?->price; }
    public function getBasePriceAttribute() { return $this->showcase_variant?->price; }
    public function getSalePriceAttribute() { return $this->showcase_variant?->sale_price; }

    /**
     * Verifica se o produto está visualmente em oferta na vitrine.
     */
    public function isOnSale(): bool
    {
        return $this->showcase_variant?->isOnSale() ?? false;
    }

    /**
     * Calcula a porcentagem de desconto para a etiqueta ("20% OFF").
     * Protege contra divisão por zero e arredondamentos incorretos (100% off).
     */
    public function getDiscountPercentageAttribute()
    {
        $variant = $this->showcase_variant;

        if (!$variant || !$this->isOnSale()) return 0;
        if ($variant->price <= 0) return 0;

        $percentage = (($variant->price - $variant->sale_price) / $variant->price) * 100;
        
        // Se arredondar para 100% mas ainda custar algo (ex: R$ 0,01), força 99%
        if (round($percentage) >= 100 && $variant->sale_price > 0) return 99;

        return round($percentage);
    }

    // =========================================================================
    // SCOPES (Filtros SQL Otimizados)
    // =========================================================================

    /**
     * Filtra produtos que possuem pelo menos uma variante em oferta VÁLIDA.
     * Verifica Preço Promocional < Preço Base E Datas de Validade (Início/Fim).
     */
    public function scopeOnSaleQuery($query)
    {
        return $query->where('is_active', true)
            ->whereHas('variants', function ($q) {
                $q->whereNotNull('sale_price')
                  ->whereColumn('sale_price', '<', 'price')
                  // Validação Temporal SQL (Muito mais rápido que filtrar no PHP)
                  ->where(function ($d) {
                      $d->whereNull('sale_start_date')->orWhere('sale_start_date', '<=', now());
                  })
                  ->where(function ($d) {
                      $d->whereNull('sale_end_date')->orWhere('sale_end_date', '>=', now());
                  });
            });
    }

    // =========================================================================
    // HELPERS VISUAIS
    // =========================================================================

    /**
     * Retorna variantes únicas visualmente (por cor/imagem) para listagens.
     * Evita repetição de bolinhas de cor se existirem vários tamanhos da mesma cor.
     */
    public function getVisualVariantsAttribute()
    {
        return $this->variants
            ->whereNotNull('image') 
            ->unique(function ($variant) {
                $options = $variant->options ?? [];
                // Normaliza a chave de cor para evitar duplicidade (ex: "Cor", "COR", "color")
                foreach (self::COLOR_KEYS as $key) {
                    if (isset($options[$key])) return mb_strtolower(trim($options[$key]));
                }
                return $variant->image;
            });
    }
}