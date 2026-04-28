<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
                // ATENÇÃO: A chave 'entity_type' foi RIGOROSAMENTE REMOVIDA daqui, 
                // pois o endpoint /payments para PIX a rejeita com erro 500 interno.
                'identification' => [
                    'type' => 'CPF',
                    'number' => $cleanCpf
                ]
            ]
        ];

        try {
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

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Erro Genérico HTTP ' . $response->status();
            
            if (isset($errorData['message']) && $errorData['message'] === 'internal_error') {
                $errorMessage = "Mercado Pago (PIX): Falha na geração. Verifique se os dados do cliente (CPF/E-mail) são válidos ou se a conta vendedora possui chave PIX cadastrada.";
            }
            
            Log::error('Erro MercadoPago PIX', ['payload' => $payload, 'response' => $errorData]);

            return ['success' => false, 'message' => $errorMessage];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Falha de comunicação: ' . $e->getMessage()];
        }
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
            'payment_method_id' => 'bolbradesco', 
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
                'entity_type' => 'individual', // Mantido APENAS para o boleto (exigência da API)
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
                ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
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
            Log::error('Erro MercadoPago Boleto', ['payload' => $payload, 'response' => $errorData]);

            return ['success' => false, 'message' => $errorMessage];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Falha de comunicação: ' . $e->getMessage()];
        }
    }

    /**
     * Gera um pagamento via Cartão de Crédito no Mercado Pago.
     */
    public function createCreditCardPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email, string $token, int $installments, string $paymentMethodId, ?string $issuerId = null): array
    {
        $cleanCpf = preg_replace('/\D/', '', $cpf);

        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'token' => $token, 
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'installments' => $installments,
            'payment_method_id' => $paymentMethodId,
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
                'entity_type' => 'individual',
                'identification' => [
                    'type' => 'CPF',
                    'number' => $cleanCpf
                ]
            ]
        ];

        if ($issuerId) {
            $payload['issuer_id'] = $issuerId;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
                ->timeout(20)
                ->post("{$this->baseUrl}/payments", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'payment_id' => $data['id'],
                    'status' => $data['status'], 
                ];
            }

            $errorData = $response->json();
            Log::error('Erro MercadoPago Credit Card', ['payload' => $payload, 'response' => $errorData]);
            
            return ['success' => false, 'message' => $errorData['message'] ?? 'Pagamento recusado pela operadora. Verifique os dados.'];

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
                'status' => $data['status'], 
            ];
        }

        return [
            'success' => false,
            'message' => 'Não foi possível verificar o status do pagamento.'
        ];
    }
}