<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class SuccessPage extends Component
{
    public Order $order;

    public function mount(Order $order)
    {
        // CORREÇÃO: Carrega os itens com seus produtos E variantes simultaneamente
        $this->order = $order->load(['items.product', 'items.variant']);

        // Segurança: Impede que um usuário veja o pedido de outro
        if ($this->order->user_id !== Auth::id()) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.success-page')->layout('components.layout', ['title' => 'Pedido Confirmado']);
    }
}