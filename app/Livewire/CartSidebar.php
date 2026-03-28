<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartSidebar extends Component
{
    // Escuta eventos globais para atualizar o carrinho (ex: quando adiciona item na PDP)
    #[On('cartUpdated')]
    public function refreshCart()
    {
        // No Livewire 3, limpar a propriedade computada força ela a buscar dados novos no próximo acesso
        unset($this->cartItems);
    }

    public function updateQuantity($itemId, $action)
    {
        $item = CartItem::find($itemId);

        if (!$item) return;

        // Proteção de segurança: Garante que apenas o dono do carrinho pode modificá-lo
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

        // Limpa o cache computado para forçar re-renderização precisa
        unset($this->cartItems);
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

        unset($this->cartItems);
    }

    // =========================================================================
    // #[Computed] - Arquitetura de Performance
    // O Livewire só executa esta query SE a view pedir os dados, e descarta
    // da memória após renderizar, evitando sobrecarregar o HTML com JSON.
    // =========================================================================
    #[Computed]
    public function cartItems()
    {
        if (Auth::check() || Session::getId()) {
            return CartItem::with(['product.categories', 'product.variants', 'variant'])
                ->where(function ($query) {
                    if (Auth::check()) {
                        $query->where('user_id', Auth::id());
                    } else {
                        $query->where('session_id', Session::getId());
                    }
                })->get();
        }

        return collect();
    }

    public function render()
    {
        // Envia a contagem exata para o Header via Alpine.js sem precisar de queries extras
        $this->dispatch('update-cart-count', count: $this->cartItems->count());

        // Não passamos mais arrays pesados para a view. A view acessa via $this->cartItems.
        return view('livewire.cart-sidebar');
    }
}