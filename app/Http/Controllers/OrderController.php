<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Exibe a página de confirmação de pedido (Sucesso).
     * Renderizada de forma estática para máxima performance.
     *
     * @param int $orderId
     * @return \Illuminate\View\View
     */
    public function success($orderId)
    {
        // 1. OTIMIZAÇÃO (Eager Loading): Busca o pedido já incluindo o usuário, 
        // os itens, os produtos e as variantes em apenas 2 queries de banco de dados.
        $order = Order::with([
            'user', 
            'items.product', 
            'items.variant'
        ])->findOrFail($orderId);

        // 2. SEGURANÇA: Garante que o usuário logado só possa visualizar o seu próprio pedido.
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Acesso negado. Este pedido não pertence à sua conta.');
        }

        // 3. RENDERIZAÇÃO: Retorna a view Blade padrão (sem a carga do Livewire)
        return view('shop.success', compact('order'));
    }
}