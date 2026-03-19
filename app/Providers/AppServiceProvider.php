<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
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

        // 2. COMPOSER GLOBAL PARA O LAYOUT PRINCIPAL
        // Agrupamos Categorias e Carrinho em um único composer para máxima performance do TTFB.
        View::composer('components.layout', function ($view) {
            
            // =========================================================================
            // A. CACHE DE CATEGORIAS DO MENU
            // =========================================================================
            $categories = Cache::remember('global_categories_menu', 60, function () {
                try {
                    if (Schema::hasTable('categories')) {
                        return Category::whereNull('parent_id')
                            ->where('is_active', true)
                            // Otimização: Seleciona apenas colunas necessárias e carrega hierarquia profunda
                            ->with([
                                'children' => fn($q) => $q->where('is_active', true)
                                    ->select('id', 'parent_id', 'name', 'slug')
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
                            ->get(['id', 'name', 'slug']);
                    }
                    return collect();
                } catch (\Exception $e) {
                    return collect();
                }
            });

        // =========================================================================
            // B. LÓGICA DO CARRINHO (Suporte a Visitantes + Eager Loading)
            // =========================================================================
            $sessionId = Session::getId();
            $userId = Auth::id();

            $cartItems = CartItem::with([
                    // 1. Produto Pai e dependências mínimas para a View
                    // CORREÇÃO: Removidos 'base_price' e 'sale_price' pois foram deletados da tabela products.
                    'product' => fn($q) => $q->select('id', 'name', 'slug', 'image_url')
                        ->with([
                            'categories' => fn($c) => $c->select('categories.id', 'categories.name', 'categories.slug'),
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

            // Cálculo dinâmico do total do carrinho em memória
            $cartTotal = $cartItems->sum(function ($item) {
                // Se o item tem variante, usa o preço final da variante
                if ($item->variant) {
                    // Tenta acessar o final_price (se você tiver o accessor), senão calcula na hora
                    $price = $item->variant->sale_price > 0 ? $item->variant->sale_price : $item->variant->price;
                    return $item->quantity * $price;
                }
                
                // Fallback de segurança para produtos sem variante (se ainda existirem)
                // Assumindo que seu model Product tem métodos de fallback para preço
                $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
                return $item->quantity * $price;
            });

            // Injeta as variáveis na View
            $view->with('globalCategories', $categories)
                 ->with('globalCartItems', $cartItems)
                 ->with('globalCartTotal', $cartTotal);
        });
    }
}