<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\Models\CartItem;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
             View::share('globalCategories', Category::all());
        }

        // Compartilha dados do carrinho com o componente de Layout
        View::composer('components.layout', function ($view) {
            $sessionId = Session::getId();
            $userId = Auth::id();

            // [CORREÇÃO]: Carregamos 'variant' para exibir fotos e preços corretos no menu lateral
            $cartItems = CartItem::with(['product', 'variant'])
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })->get();

            // [CORREÇÃO]: Cálculo do total alinhado com o CartController (prioriza variante)
            $cartTotal = $cartItems->sum(function ($item) {
                if ($item->variant) {
                    return $item->quantity * $item->variant->final_price;
                }
                // Fallback para produtos sem variante
                $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
                return $item->quantity * $price;
            });

            $view->with('globalCartItems', $cartItems)
                ->with('globalCartTotal', $cartTotal);
        });
    }
}