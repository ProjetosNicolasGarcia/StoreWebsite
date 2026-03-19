<?php

namespace App\Livewire;

use Livewire\Component;
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