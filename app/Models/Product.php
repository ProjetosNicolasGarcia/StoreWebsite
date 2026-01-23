<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Class Product
 * Representa a entidade pai (recipiente) do catálogo.
 * O preço e o estoque reais vivem nas Variantes (ProductVariant).
 */
class Product extends Model
{
    use HasFactory;

    /**
     * Chaves usadas para agrupar visualmente as variantes no frontend (ex: bolinhas de cor).
     */
    const COLOR_KEYS = ['Cor', 'Color', 'COR', 'cor', 'color', 'Tonalidade', 'Matiz'];

    protected $fillable = [
        'category_id', // Mantido para compatibilidade, mas o real é a tabela pivô
        'name',
        'slug',
        'description',
        'image_url', 
        'gallery',   
        'is_active',
        'characteristics',
        'weight',
        'height',
        'width',
        'length',
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

    /**
     * Relacionamento Muitos-para-Muitos com Categorias.
     */
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
     * Define qual variante será exibida no card do produto (Home/Listagem).
     * Lógica:
     * 1. Menor preço que esteja em PROMOÇÃO VÁLIDA.
     * 2. Variante marcada como "Principal" (is_default).
     * 3. Menor preço base.
     */
    public function getShowcaseVariantAttribute()
    {
        // Carrega variantes se ainda não foram carregadas
        $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        // 1. Prioridade: Ofertas Válidas (Menor Preço)
        $cheapestPromo = $variants->filter(function ($v) {
            return $this->variantIsOnSale($v); // Usa a função auxiliar de verificação de data
        })->sortBy('sale_price')->first();

        if ($cheapestPromo) {
            return $cheapestPromo;
        }

        // 2. Prioridade: Padrão do Admin
        $default = $variants->firstWhere('is_default', true);
        if ($default) {
            return $default;
        }

        // 3. Fallback: Menor Preço Base
        return $variants->sortBy('price')->first();
    }

    // =========================================================================
    // ACESSORES PARA O BLADE (Frontend)
    // =========================================================================

    /**
     * Retorna o preço da variante de vitrine.
     */
    public function getPriceAttribute()
    {
        return $this->showcase_variant?->price;
    }

    public function getBasePriceAttribute()
    {
        return $this->showcase_variant?->price;
    }

    public function getSalePriceAttribute()
    {
        return $this->showcase_variant?->sale_price;
    }

    /**
     * Verifica se o produto está visualmente em oferta.
     * Baseia-se na variante escolhida para a vitrine.
     */
    public function isOnSale(): bool
    {
        $variant = $this->showcase_variant;
        
        if (!$variant) return false;

        return $this->variantIsOnSale($variant);
    }

    public function getDiscountPercentageAttribute()
    {
        $variant = $this->showcase_variant;

        if (!$variant || !$this->isOnSale()) {
            return 0;
        }

        if ($variant->price <= 0) return 0;

        return round((($variant->price - $variant->sale_price) / $variant->price) * 100);
    }

    // =========================================================================
    // SCOPES (Filtros de Banco de Dados)
    // =========================================================================

    /**
     * [CORREÇÃO CRÍTICA]
     * Filtra produtos que tenham PELO MENOS UMA variante com oferta válida.
     * Verifica Preço < Original E Datas de Validade.
     */
    public function scopeOnSaleQuery($query)
    {
        return $query->where('is_active', true)
            ->whereHas('variants', function ($q) {
                $q->whereNotNull('sale_price')
                  ->whereColumn('sale_price', '<', 'price')
                  // Verifica validade da Data de Início
                  ->where(function ($d) {
                      $d->whereNull('sale_start_date')
                        ->orWhere('sale_start_date', '<=', now());
                  })
                  // Verifica validade da Data de Fim
                  ->where(function ($d) {
                      $d->whereNull('sale_end_date')
                        ->orWhere('sale_end_date', '>=', now());
                  });
            });
    }

    // =========================================================================
    // HELPERS & AGRUPAMENTO VISUAL
    // =========================================================================

    /**
     * Agrupa variantes por cor/imagem para mostrar bolinhas na listagem.
     */
    public function getVisualVariantsAttribute()
    {
        return $this->variants
            ->whereNotNull('image') 
            ->unique(function ($variant) {
                $options = $variant->options ?? [];
                
                foreach (self::COLOR_KEYS as $key) {
                    if (isset($options[$key])) {
                        return mb_strtolower(trim($options[$key]));
                    }
                }

                return $variant->image;
            });
    }

    /**
     * Validação centralizada de regra de negócio para oferta.
     * @param $variant
     * @return bool
     */
    protected function variantIsOnSale($variant): bool
    {
        // 1. Regra de Preço
        if (!$variant->sale_price || $variant->sale_price >= $variant->price) {
            return false;
        }

        $now = now();

        // 2. Regra de Data de Início
        if ($variant->sale_start_date && $now->lt($variant->sale_start_date)) {
            return false;
        }

        // 3. Regra de Data de Fim (Expiração)
        if ($variant->sale_end_date && $now->gt($variant->sale_end_date)) {
            return false;
        }

        return true;
    }
}