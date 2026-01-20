<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    /**
     * O $fillable foi limpo.
     * Removemos: base_price, sale_price, sale_start_date, sale_end_date.
     * Mantivemos: Dimensões (assumindo que são gerais, mas podem ser movidas para variantes no futuro se necessário).
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_url', // Capa principal do produto (vitrine)
        'gallery',   // Galeria geral (opcional, já que variantes terão as suas)
        'is_active',
        'characteristics',
        // Dimensões e Peso (Mantidos no pai por enquanto, mas idealmente iriam para a variante se mudarem por tamanho)
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

    // --- RELACIONAMENTOS ---

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relacionamento principal com as variações
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Helper para pegar a variante padrão (Útil para listar preço na Home)
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

    // --- ACCESSORS (CAMADA DE COMPATIBILIDADE) ---
    // Estes métodos permitem que você chame $product->price mesmo sem ter a coluna na tabela.

    /**
     * Retorna o menor preço entre as variantes para exibir "A partir de R$..."
     */
    public function getPriceAttribute()
    {
        // Se tiver variante padrão, usa o preço dela. Se não, pega o menor preço geral.
        if ($this->defaultVariant) {
            return $this->defaultVariant->price;
        }
        return $this->variants->min('price') ?? 0;
    }

    /**
     * Mantém compatibilidade com código antigo que chama 'base_price'
     */
    public function getBasePriceAttribute()
    {
        return $this->price; // Reutiliza a lógica acima
    }

    /**
     * Verifica se ALGUMA variante está em promoção
     */
    public function isOnSale()
    {
        // Verifica se existe alguma variante com sale_price definido e válido
        return $this->variants->contains(function ($variant) {
            return $variant->sale_price > 0 && $variant->sale_price < $variant->price;
        });
    }

    /**
     * Retorna a porcentagem de desconto da variante padrão (ou da maior promoção encontrada)
     */
    public function getDiscountPercentageAttribute()
    {
        $variant = $this->defaultVariant ?? $this->variants->first();

        if (!$variant || !$variant->sale_price || $variant->sale_price >= $variant->price) {
            return 0;
        }

        return round((($variant->price - $variant->sale_price) / $variant->price) * 100);
    }

    /**
     * Retorna o preço final para exibição na listagem (considerando a variante padrão)
     */
    public function getFinalPriceAttribute()
    {
        $variant = $this->defaultVariant ?? $this->variants->sortBy('price')->first();

        if (!$variant) return 0;

        return $variant->sale_price ?? $variant->price;
    }

    // --- SCOPES (FILTROS DE BANCO DE DADOS) ---

    /**
     * Filtra produtos que tenham pelo menos uma variante em promoção.
     * Atualizado para usar whereHas nas variantes.
     */
    public function scopeOnSaleQuery($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->whereNotNull('sale_price')
              ->whereColumn('sale_price', '<', 'price');
        });
    }
}