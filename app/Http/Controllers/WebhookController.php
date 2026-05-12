<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    /**
     * Recebe as notificações do Mercado Pago.
     */
    public function handleMercadoPago(Request $request, PaymentService $paymentService)
    {
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            return response()->json(['message' => 'Nenhum ID fornecido'], 200); 
        }

        try {
            // Consulta a API oficial para validar a veracidade da notificação
            $mpData = $paymentService->getPaymentStatus($paymentId);

            if ($mpData['success']) {
                $order = Order::where('payment_id', $paymentId)->first();

                if ($order) {
                    // 1. CAMINHO FELIZ: Pagamento Aprovado
                    if ($mpData['status'] === 'approved') {
                        
                        DB::transaction(function () use ($order) {
                            // Re-seleciona com lock para evitar race conditions com a Job de expiração
                            $order = Order::where('id', $order->id)->lockForUpdate()->first();

                            // Se o pedido já foi cancelado pela Job (TTL), mas o pagamento chegou agora
                            if ($order->status === Order::STATUS_CANCELLED) {
                                Log::critical("ALERTA: Pedido #{$order->id} foi pago APÓS a expiração/cancelamento. Verifique estoque manualmente.");
                                $order->update(['status' => 'paid_after_expiration']); // Status especial para sua gestão
                                return;
                            }

                            // Se ainda estiver pendente, marca como pago
                            if ($order->status !== Order::STATUS_PAID) {
                                $order->update(['status' => Order::STATUS_PAID]);
                                Log::info("SUCESSO: Pedido #{$order->id} confirmado via Webhook.");
                                
                                // NOTA: O estoque NÃO é decrementado aqui, pois já foi decrementado 
                                // no ato da criação do pedido (CheckoutPage@placeOrder).
                            }
                        });
                    } 
                    
                    // 2. CAMINHO DE FALHA: Pagamento Rejeitado ou Cancelado
                    elseif (in_array($mpData['status'], ['cancelled', 'rejected', 'refunded'])) {
                        
                        DB::transaction(function () use ($order) {
                            $order = Order::where('id', $order->id)->lockForUpdate()->first();

                            // Se o pedido ainda estiver pendente e o pagamento falhou definitivamente
                            if ($order->status === Order::STATUS_PENDING) {
                                $order->update(['status' => Order::STATUS_CANCELLED]);

                                // 🛠️ DEVOLUÇÃO DE ESTOQUE: Como o pagamento falhou, devolvemos os itens para a loja
                                foreach ($order->items as $item) {
                                    if ($item->product_variant_id) {
                                        ProductVariant::where('id', $item->product_variant_id)
                                            ->increment('quantity', $item->quantity);
                                    } else {
                                        Product::where('id', $item->product_id)
                                            ->increment('quantity', $item->quantity);
                                    }
                                }
                                Log::info("CANCELADO: Pedido #{$order->id} falhou e estoque foi devolvido.");
                            }
                        });
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("ERRO WEBHOOK MP: " . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500); 
        }

        return response()->json(['status' => 'success'], 200);
    }
}