<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class Product
 * * Representa a entidade central do catálogo de produtos.
 * * Arquitetura: Atua como um agregador para múltiplas variantes (SKUs).
 * * Lógica de Negócio: Centraliza a decisão de precificação e exibição dinâmica 
 * através do conceito de "Showcase Variant" (Variante de Vitrine).
 *
 * @package App\Models
 */
class Product extends Model
{
    use HasFactory;

    /**
     * Lista de chaves usadas para identificar variações de cor em diferentes idiomas/formatos.
     * Centralizado aqui para facilitar a normalização em métodos de agrupamento visual.
     * @see getVisualVariantsAttribute
     */
    const COLOR_KEYS = ['Cor', 'Color', 'COR', 'cor', 'color', 'Tonalidade', 'Matiz'];

    /**
     * Atributos atribuíveis em massa.
     * * 'image_url': Capa principal do produto usada como fallback.
     * * 'characteristics': Armazena especificações técnicas em formato JSON.
     * * 'gallery': Armazena caminhos de imagens adicionais do produto pai.
     */
    protected $fillable = [
        'category_id',
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

    /**
     * Conversão de tipos (Casting).
     * Garante que campos decimais mantenham a precisão necessária para logística (frete).
     */
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
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relacionamento com as variações específicas do produto (SKUs).
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Helper para identificar a variante definida como principal pelo administrador.
     */
    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
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
    // LÓGICA DE VITRINE INTELIGENTE
    // =========================================================================

    /**
     * Accessor: Define qual variante representa o produto nas listagens (Home/Shop).
     * * Algoritmo de Prioridade:
     * 1. Promoção: Prioriza variantes em oferta para maximizar conversão.
     * 2. Padrão: Usa a variante marcada como 'is_default'.
     * 3. Preço: Fallback para a variante de menor custo ("A partir de").
     * * @return ProductVariant|null
     */
   public function getShowcaseVariantAttribute()
    {
        $variants = $this->variants;

        if ($variants->isEmpty()) {
            return null;
        }

        // 1. PRIORIDADE MÁXIMA: Menor preço promocional ativo.
        $cheapestPromo = $variants->filter(function ($v) {
            return $v->isOnSale();
        })->sortBy('sale_price')->first();

        if ($cheapestPromo) {
            return $cheapestPromo;
        }

        // 2. PRIORIDADE MÉDIA: Curadoria do Admin.
        $default = $variants->firstWhere('is_default', true);
        if ($default) {
            return $default;
        }

        // 3. FALLBACK: Menor preço base geral.
        return $variants->sortBy('price')->first();
    }

    // =========================================================================
    // ACCESSORS DE COMPATIBILIDADE (Interface para Views)
    // =========================================================================

    /**
     * Retorna o preço de venda da variante de vitrine.
     * Previne inconsistências visuais em produtos com múltiplas faixas de preço.
     */
    public function getSalePriceAttribute()
    {
        return $this->showcase_variant?->sale_price;
    }

    /**
     * Retorna o preço base original da variante de vitrine.
     */
    public function getBasePriceAttribute()
    {
        return $this->showcase_variant?->price;
    }

    /**
     * Verifica se o produto (via sua variante de vitrine) possui oferta ativa.
     */
    public function isOnSale()
    {
        return $this->showcase_variant?->isOnSale() ?? false;
    }

    /**
     * Calcula a porcentagem de desconto para badges de vitrine.
     * @return float|int
     */
    public function getDiscountPercentageAttribute()
    {
        $variant = $this->showcase_variant;

        if (!$variant || !$this->isOnSale()) {
            return 0;
        }

        if ($variant->price <= 0) return 0;

        return round((($variant->price - $variant->sale_price) / $variant->price) * 100);
    }

    /**
     * Alias de compatibilidade para chamadas diretas ao preço base.
     */
    public function getPriceAttribute()
    {
        return $this->base_price;
    }

    // =========================================================================
    // SCOPES (Filtros de Banco de Dados)
    // =========================================================================

    /**
     * Filtra produtos que possuam ao menos uma variante com preço promocional válido.
     */
    public function scopeOnSaleQuery($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->whereNotNull('sale_price')
              ->whereColumn('sale_price', '<', 'price');
        });
    }

    // =========================================================================
    // MÉTODOS DE AGRUPAMENTO VISUAL
    // =========================================================================

    /**
     * Agrupa variantes por atributo visual (Cor) ou imagem.
     * * Objetivo: Evitar duplicidade de fotos em tamanhos diferentes (ex: P, M, G).
     * * Retorna apenas uma instância de cada "cor" para seleção na vitrine.
     * * @return \Illuminate\Support\Collection
     */
    public function getVisualVariantsAttribute()
    {
        return $this->variants
            ->whereNotNull('image') 
            ->unique(function ($variant) {
                $options = $variant->options ?? [];
                
                // Tenta normalizar o agrupamento usando as chaves de cor definidas na constante
                foreach (self::COLOR_KEYS as $key) {
                    if (isset($options[$key])) {
                        return mb_strtolower(trim($options[$key]));
                    }
                }

                // Fallback: Agrupa pelo caminho da imagem caso não haja metadados de cor
                return $variant->image;
            });
    }
}