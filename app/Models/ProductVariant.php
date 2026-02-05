<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductVariant
 * * Representa a unidade mínima de venda (SKU) do e-commerce.
 * * Arquitetura: Enquanto o 'Product' contém as informações macro, a 'Variant' detém
 * os dados granulares de estoque, preço e atributos específicos (Cor, Tamanho, Voltagem).
 *
 * @package App\Models
 */
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * Configuração de Mass Assignment.
     * * Utiliza $guarded vazio para permitir flexibilidade na criação de variações complexas.
     * * Nota: A integridade dos campos críticos (preço/estoque) deve ser validada 
     * nas FormRequests ou Services de inventário.
     */
    protected $guarded = [];

    /**
     * Conversão de tipos (Casting).
     * * 'options': Essencial para converter o JSON de atributos (ex: {"Tamanho": "G"}) em array PHP.
     * * 'sale_start_date/end_date': Garante que as datas de promoção sejam instâncias de Carbon,
     * permitindo comparações temporais precisas.
     */
    protected $casts = [
        'options' => 'array', 
        'images' => 'array',
        'is_default' => 'boolean',
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
    ];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Relacionamento com o Produto Pai.
     * Vincula a variante ao catálogo principal para herança de nome e categoria.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // =========================================================================
    // LÓGICA DE PRECIFICAÇÃO E PROMOÇÃO
    // =========================================================================

    /**
     * Accessor: Preço Final.
     * * Retorna o valor real que será cobrado do cliente, considerando
     * automaticamente se há uma promoção ativa ou não.
     */
    public function getFinalPriceAttribute()
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    /**
     * Validador de Promoção Ativa.
     * * Regras para ativação do preço promocional:
     * 1. O sale_price deve existir e ser menor que o preço base.
     * 2. Se houver data de início, o momento atual deve ser posterior a ela.
     * 3. Se houver data de término, o momento atual deve ser anterior a ela.
     *
     * @return bool
     */
    public function isOnSale(): bool
    {
        // Validação de valor: Promoção não pode ser gratuita ou mais cara que o original
        if (!$this->sale_price || $this->sale_price >= $this->price) {
            return false;
        }

        $now = now();

        // Validação temporal: Verifica se está dentro da janela de oportunidade
        if ($this->sale_start_date && $now->lt($this->sale_start_date)) {
            return false;
        }

        if ($this->sale_end_date && $now->gt($this->sale_end_date)) {
            return false;
        }

        return true;
    }
}