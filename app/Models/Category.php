<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class Category
 * Representa a estrutura de navegação e organização do catálogo.
 * Otimizado para menus multinível e carregamento eficiente.
 */
class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // =========================================================================
    // RELACIONAMENTOS DE HIERARQUIA
    // =========================================================================

    /**
     * Subcategorias (Filhos).
     * Ordenados alfabeticamente para consistência nos menus.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name', 'asc');
    }

    /**
     * Categoria Pai.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // =========================================================================
    // RELACIONAMENTO COM PRODUTOS
    // =========================================================================

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    // =========================================================================
    // SCOPES (Filtros de Banco de Dados)
    // =========================================================================

    /**
     * Filtra apenas categorias Raiz (Menu Principal).
     */
    public function scopeRoot(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Filtra apenas categorias ativas.
     * Essencial para não mostrar links quebrados no menu.
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    // =========================================================================
    // HELPERS DE ESCALABILIDADE (Cache & Árvore)
    // =========================================================================

    /**
     * Retorna a árvore de categorias completa para o menu.
     * Algoritmo In-Memory: Previne a LazyLoadingViolationException hidratando os
     * relacionamentos de forma descendente em todas as categorias usando apenas 1 query.
     */
    public static function getTree()
    {
        // 1. Busca todas as categorias ativas de uma vez (Apenas 1 Query massiva)
        $allCategories = static::active()
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']);

        // 2. Agrupa as categorias de acordo com o parent_id
        $grouped = $allCategories->groupBy('parent_id');

        // 3. Hidrata manualmente a relação 'children' para evitar o Lazy Loading no Blade
        // O setRelation informa ao Eloquent que a relação já foi pré-carregada.
        $allCategories->each(function ($category) use ($grouped) {
            $category->setRelation('children', $grouped->get($category->id, collect()));
        });

        // 4. Retorna as categorias de nível 0 (raízes), já com as ramificações recursivas populadas
        return $allCategories->whereNull('parent_id')->values();
    }
}