<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentService
{
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('services.payment_gateway.url', 'https://api.mercadopago.com/v1');
        $this->accessToken = config('services.payment_gateway.access_token');
    }

    /**
     * Gera um pagamento PIX no Mercado Pago.
     */
    public function createPixPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email): array
    {
        // Limpa a máscara do CPF (Deixa apenas números)
        $cleanCpf = preg_replace('/\D/', '', $cpf);

        $payload = [
            'transaction_amount' => (float) $order->total_amount,
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'identification' => [
                    'type' => 'CPF',
                    'number' => $cleanCpf
                ]
            ]
        ];

        // Chamada à API com Timeout e Idempotency Key (Evita duplicidade em caso de timeout)
        $response = Http::withToken($this->accessToken)
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->timeout(15)
            ->post("{$this->baseUrl}/payments", $payload);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_id' => $data['id'],
                'qr_code' => $data['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'qr_code_base64' => $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => $response->json('message') ?? 'Erro desconhecido ao comunicar com o gateway de pagamento.'
        ];
    }

    public function getPaymentStatus(string $paymentId): array
    {
        $response = Http::withToken($this->accessToken)
            ->timeout(10)
            ->get("{$this->baseUrl}/payments/{$paymentId}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status' => $data['status'], // Valores comuns: 'approved', 'pending', 'cancelled', 'rejected'
            ];
        }

        return [
            'success' => false,
            'message' => 'Não foi possível verificar o status do pagamento.'
        ];
    }
}