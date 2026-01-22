<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Collection
 * * Representa agrupamentos temáticos ou sazonais de produtos (ex: "Lançamentos", "Coleção de Verão").
 * * Diferente das Categorias (que são estruturais), as Coleções são flexíveis e servem
 * para estratégias de marketing e curadoria visual na loja.
 *
 * @package App\Models
 */
class Collection extends Model
{
    use HasFactory;

    /**
     * Atributos atribuíveis em massa.
     * * 'featured_on_home': Define se esta coleção deve aparecer em seções de destaque na landing page.
     * * 'is_active': Toggle global para exibir/ocultar a coleção e seus respectivos links.
     */
    protected $fillable = [
        'title', 
        'slug', 
        'image_url', 
        'description', 
        'is_active', 
        'featured_on_home'
    ];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Relacionamento Muitos-para-Muitos com Produtos.
     * * Uma coleção pode conter vários produtos e um produto pode pertencer a várias coleções.
     * * Tabela Pivot: 'collection_product'.
     * * Utilidade: Permite criar vitrines dinâmicas baseadas em temas sem alterar a 
     * árvore de categorias principal do produto.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product');
    }
}