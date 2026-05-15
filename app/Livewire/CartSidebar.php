<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On; // ✅ NOVO: Importação do Atributo de Eventos do Livewire 3
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartSidebar extends Component
{
    protected $listeners = ['cartUpdated' => '$refresh'];

    public function updateQuantity($itemId, $action)
    {
        $item = CartItem::find($itemId);

        if (!$item) return;

        $isOwner = Auth::check() ? $item->user_id === Auth::id() : $item->session_id === Session::getId();
        if (!$isOwner) return;

        if ($action === 'increase') {
            $item->increment('quantity');
        } elseif ($action === 'decrease') {
            if ($item->quantity > 1) {
                $item->decrement('quantity');
            } else {
                $item->delete(); 
            }
        }
    }

    public function removeItem($itemId)
    {
        $item = CartItem::find($itemId);

        if ($item) {
            $isOwner = Auth::check() ? $item->user_id === Auth::id() : $item->session_id === Session::getId();
            if ($isOwner) {
                $item->delete();
            }
        }
    }

    /**
     * ✅ NOVO: Método que intercepta o botão "Repetir Compra" do front-end.
     * Processa a inserção assíncrona dos itens no carrinho.
     */
    #[On('reorder-cart')]
    public function handleReorder($order)
    {
        if (!Auth::check()) return; // Trava extra de segurança

        // Busca o pedido garantindo que pertença ao usuário logado
        $orderRecord = Auth::user()->orders()->with('items')->findOrFail($order);
        
        $sessionId = Session::getId();
        $userId = Auth::id();

        foreach ($orderRecord->items as $orderItem) {
            $conditions = [
                'product_id' => $orderItem->product_id,
                'product_variant_id' => $orderItem->product_variant_id,
            ];

            if ($userId) {
                $conditions['user_id'] = $userId;
            } else {
                $conditions['session_id'] = $sessionId;
            }

            // Lógica Upsert: Incrementa se já existir, cria se for novo.
            $cartItem = CartItem::where($conditions)->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $orderItem->quantity);
            } else {
                CartItem::create(array_merge($conditions, ['quantity' => $orderItem->quantity]));
            }
        }

        // Não é necessário retornar nada. O ciclo de vida do Livewire 
        // chamará a função render() automaticamente após este método finalizar, 
        // carregando e exibindo os novos itens na tela instantaneamente.
    }

    public function render()
    {
        $cartItems = collect();
        
        if (Auth::check() || Session::getId()) {
            $cartItems = CartItem::with(['product.categories', 'product.variants', 'variant'])
                ->where(function ($query) {
                    if (Auth::check()) {
                        $query->where('user_id', Auth::id());
                    } else {
                        $query->where('session_id', Session::getId());
                    }
                })->get();
        }

        $cartTotal = $cartItems->sum(function ($item) {
            if (!$item->product) return 0;
            
            $unitPrice = $item->variant 
                ? $item->variant->final_price 
                : ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
                
            return $unitPrice * $item->quantity;
        });

        // [PERFORMANCE FIX] O Sidebar mesmo conta os itens e envia o número exato para o Alpine.js no navegador
        // Isso mata a necessidade de uma segunda requisição ao servidor.
        $this->dispatch('update-cart-count', count: $cartItems->count());

        return view('livewire.cart-sidebar', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
        ]);
    }
}