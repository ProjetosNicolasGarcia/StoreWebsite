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

    /**
     * Gera um pagamento via Boleto Bancário no Mercado Pago.
     */
public function createBoletoPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email, array $address): array
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);
        $cleanZip = preg_replace('/\D/', '', $address['zip_code'] ?? '');
        
        $streetNumber = preg_replace('/\D/', '', $address['number'] ?? '');
        if (empty($streetNumber)) {
            $streetNumber = '1'; 
        }

        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            // 'bolbradesco' é o ID clássico. 'pec' é Lotérica, que também gera boleto.
            'payment_method_id' => 'bolbradesco', 
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
                'entity_type' => 'individual', // Parâmetro extra para evitar bloqueio 500
                'identification' => [
                    'type' => 'CPF',
                    'number' => $cleanCpf
                ],
                'address' => [
                    'zip_code' => $cleanZip,
                    'street_name' => mb_substr($address['street'] ?? 'Rua', 0, 250),
                    'street_number' => $streetNumber,
                    'neighborhood' => mb_substr($address['neighborhood'] ?? 'Bairro', 0, 250),
                    'city' => mb_substr($address['city'] ?? 'Cidade', 0, 250),
                    'federal_unit' => strtoupper(substr($address['state'] ?? 'SP', 0, 2))
                ]
            ]
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders(['X-Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
                ->timeout(20)
                ->post("{$this->baseUrl}/payments", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'boleto_url' => $data['transaction_details']['external_resource_url'] ?? null, 
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro Genérico HTTP ' . $response->status();
            
            // Log do erro real no terminal para podermos auditar
            \Illuminate\Support\Facades\Log::error('Erro MercadoPago Boleto', ['payload' => $payload, 'response' => $errorData]);

            return ['success' => false, 'message' => $errorMessage];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Falha de comunicação: ' . $e->getMessage()];
        }
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