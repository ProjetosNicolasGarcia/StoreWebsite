<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory;

    /**
     * Lista de chaves usadas para identificar variações de cor.
     * Centralizado aqui para facilitar a manutenção.
     */
    const COLOR_KEYS = ['Cor', 'Color', 'COR', 'cor', 'color', 'Tonalidade', 'Matiz'];

    /**
     * O $fillable define quais campos podem ser salvos no banco.
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'image_url', // Capa principal do produto (vitrine)
        'gallery',   // Galeria geral
        'is_active',
        'characteristics',
        // Dimensões e Peso
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

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Helper para pegar a variante padrão
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

    // --- LÓGICA DE VITRINE INTELIGENTE (CORREÇÃO DE BUGS VISUAIS) ---

    /**
     * Define qual variante será usada para "representar" o produto na listagem (Home/Shop).
     * Lógica:
     * 1. Se tiver Variante Padrão (ex: cor principal), usa ela.
     * 2. Se não, tenta pegar a variante mais barata QUE ESTEJA EM OFERTA (para atrair clique).
     * 3. Se não tiver oferta, pega a variante mais barata geral.
     */
   public function getShowcaseVariantAttribute()
    {
        // Carrega as variantes da memória
        $variants = $this->variants;

        if ($variants->isEmpty()) {
            return null;
        }

        // 1. PRIORIDADE MÁXIMA: Menor preço promocional
        // Se houver QUALQUER variante em oferta, ela deve "furar a fila" para aparecer na vitrine.
        $cheapestPromo = $variants->filter(function ($v) {
            return $v->isOnSale();
        })->sortBy('sale_price')->first();

        if ($cheapestPromo) {
            return $cheapestPromo;
        }

        // 2. Se NINGUÉM estiver em oferta, usamos a Variante Padrão definida no Admin
        // Nota: Acessamos via relationLoaded para evitar query extra se já estiver carregado, 
        // ou filtramos a coleção manualmente para performance.
        $default = $variants->firstWhere('is_default', true);
        if ($default) {
            return $default;
        }

        // 3. Último caso: Menor preço base geral (A partir de...)
        return $variants->sortBy('price')->first();
    }

    // --- ACCESSORS DE COMPATIBILIDADE (VIEW) ---

    /**
     * Retorna o preço de venda (Sale Price) da variante de vitrine.
     * Corrige o bug de aparecer "R$ 0,00" quando o produto pai não tem preço.
     */
    public function getSalePriceAttribute()
    {
        return $this->showcase_variant?->sale_price;
    }

    /**
     * Retorna o preço original (Base Price) da variante de vitrine.
     * Garante consistência: Se mostramos a oferta da Variante X, mostramos o preço original da Variante X.
     */
    public function getBasePriceAttribute()
    {
        return $this->showcase_variant?->price;
    }

    /**
     * Verifica se o produto está em promoção baseando-se na variante escolhida para a vitrine.
     */
    public function isOnSale()
    {
        return $this->showcase_variant?->isOnSale() ?? false;
    }

    /**
     * Retorna a porcentagem de desconto correta baseada na variante de vitrine.
     */
    public function getDiscountPercentageAttribute()
    {
        $variant = $this->showcase_variant;

        if (!$variant || !$this->isOnSale()) {
            return 0;
        }

        // Evita divisão por zero
        if ($variant->price <= 0) return 0;

        return round((($variant->price - $variant->sale_price) / $variant->price) * 100);
    }

    /**
     * Apenas um alias para manter compatibilidade com códigos que chamam ->price direto
     */
    public function getPriceAttribute()
    {
        return $this->base_price;
    }

    // --- SCOPES (FILTROS DE BANCO DE DADOS) ---

    /**
     * Filtra produtos que tenham pelo menos uma variante em promoção.
     */
    public function scopeOnSaleQuery($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->whereNotNull('sale_price')
              ->whereColumn('sale_price', '<', 'price');
        });
    }

    // --- NOVO MÉTODO PARA MINIATURAS ÚNICAS (REFATORADO) ---

    /**
     * Retorna apenas as variantes visualmente únicas (ex: 1 de cada cor),
     * removendo duplicatas de tamanho (38, 39, 40 usam a mesma foto).
     */
    public function getVisualVariantsAttribute()
    {
        return $this->variants
            ->whereNotNull('image') // Garante que tem imagem
            ->unique(function ($variant) {
                // 1. Tenta encontrar a opção de "Cor" para agrupar
                $options = $variant->options ?? [];
                
                // [REFATORAÇÃO]: Usa a constante definida no topo da classe
                foreach (self::COLOR_KEYS as $key) {
                    if (isset($options[$key])) {
                        // Retorna o valor da cor (ex: "Azul") normalizado.
                        // Assim, "Azul 38" e "Azul 39" serão considerados iguais.
                        return mb_strtolower(trim($options[$key]));
                    }
                }

                // 2. Se não tiver atributo de cor, usa o caminho da imagem como fallback
                // Isso garante que se a imagem for idêntica, não repete.
                return $variant->image;
            });
    }
}