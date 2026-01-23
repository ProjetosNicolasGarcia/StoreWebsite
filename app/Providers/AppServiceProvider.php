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
       View::composer('*', function ($view) {
        try {
            // Verifica se a tabela existe antes de consultar (evita erro em migrations frescas)
            if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
               $categories = Category::root()
                    ->where('is_active', true)
                    // [ALTERAÇÃO] Carrega até 4 níveis de profundidade (Pai > Filho > Neto > Bisneto)
                    ->with([
                        'children' => fn($q) => $q->where('is_active', true)->orderBy('name')->with([
                            'children' => fn($q) => $q->where('is_active', true)->orderBy('name')->with([
                                'children' => fn($q) => $q->where('is_active', true)->orderBy('name')
                            ])
                        ])
                    ])
                    ->orderBy('name')
                    ->get();
                
                $view->with('globalCategories', $categories);
            } else {
                $view->with('globalCategories', collect());
            }
        } catch (\Exception $e) {
            // Em caso de erro, envia lista vazia para não quebrar a página de erro do Laravel
            // Isso permite que você veja a mensagem real do erro (ex: "Method scopeRoot does not exist")
            $view->with('globalCategories', collect());
        }
    });

        // [MANTIDO] Compartilha dados do carrinho com o componente de Layout
        View::composer('components.layout', function ($view) {
            $sessionId = Session::getId();
            $userId = Auth::id();

            // Carregamos 'variant' para exibir fotos e preços corretos no menu lateral
            $cartItems = CartItem::with(['product', 'variant'])
                ->where(function ($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })->get();

            // Cálculo do total alinhado com o CartController (prioriza variante)
            $cartTotal = $cartItems->sum(function ($item) {
                // Se tiver variante, usa o preço final dela (que já considera promoção se houver)
                if ($item->variant) {
                    return $item->quantity * $item->variant->final_price;
                }
                
                // Fallback para produtos simples (sem variante)
                $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
                return $item->quantity * $price;
            });

            $view->with('globalCartItems', $cartItems)
                ->with('globalCartTotal', $cartTotal);
        });
    }
}