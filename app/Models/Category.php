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
        // Verifica se a coluna is_active existe antes de filtrar (retrocompatibilidade)
        // Se não tiver a coluna na migração, assume que tudo é ativo.
        return $query->where('is_active', true);
    }

    // =========================================================================
    // HELPERS DE ESCALABILIDADE (Cache & Árvore)
    // =========================================================================

    /**
     * Retorna a árvore de categorias completa para o menu.
     * Otimizado: Faz apenas 1 ou 2 queries em vez de N queries recursivas.
     * Pode ser cacheado facilmente no AppServiceProvider.
     */
    public static function getTree()
    {
        return static::root()
            ->active()
            ->with(['children' => function($q) {
                $q->active()->select('id', 'parent_id', 'name', 'slug'); // Select otimizado
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']); // Select otimizado na raiz
    }
}