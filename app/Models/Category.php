<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Recomendado manter se usar Factories
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    // Permite que todos os campos sejam preenchidos (incluindo o novo 'parent_id')
    protected $guarded = [];

    /**
     * =========================================================================
     * RELACIONAMENTOS DE HIERARQUIA (PAI E FILHOS)
     * =========================================================================
     */

    /**
     * Relacionamento: Uma categoria pode ter várias subcategorias (filhos).
     * Ex: "Hardware" tem filhos "Placas de Vídeo", "Processadores".
     */
    public function children(): HasMany
    {
        // Adicionamos 'orderBy' para que os filhos já venham em ordem alfabética no menu
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name', 'asc');
    }

    /**
     * Relacionamento: Uma categoria pertence a uma categoria pai.
     * Ex: "Placa de Vídeo" pertence a "Hardware".
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * =========================================================================
     * RELACIONAMENTO COM PRODUTOS
     * =========================================================================
     */

    /**
     * Define a relação: Uma Categoria TEM MUITOS Produtos.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * =========================================================================
     * SCOPES (FILTROS REUTILIZÁVEIS)
     * =========================================================================
     */

    /**
     * Filtra apenas as categorias "Raiz" (que não têm pai).
     * Útil para gerar o menu principal sem trazer as subcategorias soltas.
     * * Uso: Category::root()->get();
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}