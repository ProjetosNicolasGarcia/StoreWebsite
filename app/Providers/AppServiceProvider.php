<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Importar View
use App\Models\Category; // Importar Model de Categoria
use App\Models\CartItem;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartilha a variável $globalCategories com todas as Views do site.
        // Isso garante que o menu sempre terá as categorias, não importa em qual página você esteja.
        // Se você tiver categorias "Pai" (parent_id null), pode filtrar aqui: Category::whereNull('parent_id')->get()
        if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
             View::share('globalCategories', Category::all());
        }

            // Compartilha dados do carrinho com o componente de Layout
        View::composer('components.layout', function ($view) {
            $sessionId = Session::getId();
            $userId = Auth::id();

            $cartItems = CartItem::with('product')
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })->get();

            $cartTotal = $cartItems->sum(function ($item) {
                // Lógica de Preço (Oferta vs Base)
                $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
                return $item->quantity * $price;
            });

            $view->with('globalCartItems', $cartItems)
                ->with('globalCartTotal', $cartTotal);
        });
    }
}