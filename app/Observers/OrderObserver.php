<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Gatilho disparado sempre que um pedido sofre um UPDATE no banco de dados.
     */
    public function updated(Order $order): void
    {
        // Verifica se a coluna 'status' foi alterada nesta transação e se o novo valor é 'paid' ou 'approved'
        if ($order->isDirty('status') && in_array($order->status, ['paid', 'approved', Order::STATUS_PAID ?? 'paid'])) {
            
            Log::info("ESTOQUE: Iniciando baixa para o Pedido #{$order->id}");

            // Varre os itens comprados neste pedido
            foreach ($order->items as $item) {
                
                // Prioridade 1: Baixar estoque da Variante (Ex: Camiseta Tamanho M)
                if ($item->product_variant_id) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    if ($variant && $variant->quantity >= $item->quantity) {
                        $variant->decrement('quantity', $item->quantity);
                        Log::info("ESTOQUE: Variante ID {$variant->id} reduzida em {$item->quantity}.");
                    }
                } 
                // Prioridade 2: Produto simples (Sem variantes)
                elseif ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product && $product->quantity >= $item->quantity) {
                        $product->decrement('quantity', $item->quantity);
                        Log::info("ESTOQUE: Produto ID {$product->id} reduzido em {$item->quantity}.");
                    }
                }
            }
        }
    }
}