<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache; // Importante para performance
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model; // Importante para Strict Mode
use App\Models\Category;
use App\Models\CartItem;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. MODO ESTRITO (Strict Mode)
        // Previne erros de performance (N+1) e acessos a atributos inexistentes durante o desenvolvimento.
        Model::shouldBeStrict(!app()->isProduction());

        // 2. MENU DE CATEGORIAS (Com Cache)
        View::composer('*', function ($view) {
            // Cache por 60 minutos para evitar query pesada em toda página
            $categories = Cache::remember('global_categories_menu', 60, function () {
                try {
                    if (Schema::hasTable('categories')) {
                        return Category::whereNull('parent_id') // Equivalente ao scopeRoot()
                            ->where('is_active', true)
                            // Otimização: Seleciona apenas colunas necessárias e carrega hierarquia
                            ->with([
                                'children' => fn($q) => $q->where('is_active', true)
                                    ->select('id', 'parent_id', 'name', 'slug') // Select Otimizado
                                    ->orderBy('name')
                                    ->with([
                                        'children' => fn($q) => $q->where('is_active', true)
                                            ->select('id', 'parent_id', 'name', 'slug')
                                            ->orderBy('name')
                                            ->with([
                                                'children' => fn($q) => $q->where('is_active', true)
                                                    ->select('id', 'parent_id', 'name', 'slug')
                                                    ->orderBy('name')
                                            ])
                                    ])
                            ])
                            ->orderBy('name')
                            ->get(['id', 'name', 'slug']); // Select na raiz
                    }
                    return collect();
                } catch (\Exception $e) {
                    return collect();
                }
            });

            $view->with('globalCategories', $categories);
        });

// 3. CARRINHO NO LAYOUT (Otimizado)
        View::composer('components.layout', function ($view) {
            $sessionId = Session::getId();
            $userId = Auth::id();

            $cartItems = CartItem::with([
                    // 1. Produto Pai e dependências
                    'product' => fn($q) => $q->select('id', 'name', 'slug', 'image_url')
                        ->with([
                            'categories' => fn($c) => $c->select('categories.id', 'categories.name', 'categories.slug'),
                            // [ATUALIZADO] Adicionado 'options' e 'image' para evitar erros visuais
                            'variants' => fn($v) => $v->select('id', 'product_id', 'price', 'sale_price', 'sale_start_date', 'sale_end_date', 'is_default', 'options', 'image')
                        ]),

                    // 2. Variante Comprada
                    'variant' => fn($q) => $q->select('id', 'product_id', 'price', 'sale_price', 'sale_start_date', 'sale_end_date', 'image', 'options')
                ])
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })->get();

            $cartTotal = $cartItems->sum(function ($item) {
                if ($item->variant) {
                    return $item->quantity * $item->variant->final_price;
                }
                return 0;
            });

            $view->with('globalCartItems', $cartItems)
                ->with('globalCartTotal', $cartTotal);
        });
    }
}