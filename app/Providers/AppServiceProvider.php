<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
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
        // Previne erros de performance (N+1) durante o desenvolvimento.
        Model::shouldBeStrict(!app()->isProduction());

        View::composer('components.layout', function ($view) {
            
            // =========================================================================
            // A. ÁRVORE DE CATEGORIAS DO MENU
            // Execução direta. O método getTree() já é otimizado in-memory (1 query).
            // Elimina o overhead letal de desserialização.
            // =========================================================================
            $categories = collect();
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
                    $categories = Category::getTree();
                }
            } catch (\Exception $e) {
                // Ignore silent failure before migrations
            }

            // =========================================================================
            // B. LÓGICA DO CARRINHO
            // =========================================================================
            $sessionId = Session::getId();
            $userId = Auth::id();

            $cartItems = CartItem::with([
                    'product' => fn($q) => $q->select('id', 'name', 'slug', 'image_url')
                        ->with([
                            'categories' => fn($c) => $c->select('categories.id', 'categories.name', 'categories.slug'),
                            'variants' => fn($v) => $v->select('id', 'product_id', 'price', 'sale_price', 'sale_start_date', 'sale_end_date', 'is_default', 'options', 'image')
                        ]),
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
                    $price = $item->variant->sale_price > 0 ? $item->variant->sale_price : $item->variant->price;
                    return $item->quantity * $price;
                }
                
                $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
                return $item->quantity * $price;
            });

            $view->with('globalCategories', $categories)
                 ->with('globalCartItems', $cartItems)
                 ->with('globalCartTotal', $cartTotal);
        });
    }
}