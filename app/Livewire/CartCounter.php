<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartCounter extends Component
{
    // Escuta o evento específico do Sidebar para se atualizar, 
    // além do evento global caso a página de produto também use.
    protected $listeners = [
        'updateCartCount' => '$refresh',
        'cartUpdated' => '$refresh'
    ];

    public function render()
    {
        $count = 0;
        
        if (Auth::check() || Session::getId()) {
            $count = CartItem::where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', Session::getId());
                }
            })->count();
        }

        return view('livewire.cart-counter', [
            'count' => $count
        ]);
    }
}