<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Recebe as notificações IPN (Instant Payment Notification) ou Webhooks do Mercado Pago.
     */
    public function handleMercadoPago(Request $request, PaymentService $paymentService)
    {
        // O Mercado Pago pode enviar o ID do pagamento de diferentes formas dependendo da configuração
        // Normalmente vem em $request->input('data.id') para Webhooks ou $request->input('id') para IPN.
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if (!$paymentId) {
            // Retorna 200 para o Mercado Pago não tentar reenviar caso seja uma notificação sem ID que não nos interessa
            return response()->json(['message' => 'Nenhum ID fornecido'], 200); 
        }

        try {
            // Consultamos a API oficial do Mercado Pago para evitar fraudes (spoofing de webhooks)
            $mpData = $paymentService->getPaymentStatus($paymentId);

            if ($mpData['success']) {
                // Procuramos o pedido associado a este ID de pagamento
                $order = Order::where('payment_id', $paymentId)->first();

                if ($order) {
                    // Se foi aprovado e ainda não estava marcado como pago
                    if ($mpData['status'] === 'approved' && $order->status !== Order::STATUS_PAID) {
                        
                        $order->update(['status' => Order::STATUS_PAID]);
                        
                        // DISPARO DE E-MAIL: Aqui entraria a lógica para enviar o recibo ao cliente
                        Log::info("SUCESSO: Pedido #{$order->id} foi pago via Webhook do Mercado Pago.");
                        
                    } elseif (in_array($mpData['status'], ['cancelled', 'rejected']) && $order->status === Order::STATUS_PENDING) {
                        
                        $order->update(['status' => Order::STATUS_CANCELED]);
                        Log::info("CANCELADO: Pedido #{$order->id} foi cancelado/rejeitado no Mercado Pago.");
                        
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("ERRO WEBHOOK MP: " . $e->getMessage());
            // Retornamos erro 500 para que o Mercado Pago saiba que falhou e tente reenviar mais tarde
            return response()->json(['error' => 'Internal Server Error'], 500); 
        }

        // Obrigatório devolver HTTP 200 ou 201 para o Mercado Pago parar de enviar notificações repetidas
        return response()->json(['status' => 'success'], 200);
    }
}