<?php
// app/Services/PaymentService.php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Exceptions\MPApiException;

class PaymentService
{
    public function __construct()
    {
        // Inicializa o SDK Oficial do Backend
        MercadoPagoConfig::setAccessToken(config('services.payment_gateway.access_token'));
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
    }

    /**
     * ✏️ alterado: Constrói o Antifraude (APENAS PARA CARTÃO DE CRÉDITO).
     * Sanitiza caracteres e fixa categorias para evitar Crash 500 no MP.
     */
    private function buildAdditionalInfo(Order $order): array
    {
        $order->loadMissing('items');

        // 1. Mapeia os produtos reais do pedido
        $mpItems = $order->items->map(function ($item) {
            // Remove aspas simples e duplas para evitar a quebra do parser JSON interno do MP
            $safeTitle = preg_replace('/[\'"]/', '', $item->product_name ?? 'Produto');

            return [
                "id" => (string) $item->product_id,
                "title" => mb_substr($safeTitle, 0, 250),
                "description" => mb_substr($safeTitle, 0, 250),
                "category_id" => "others", // Obrigatório usar 'others' para evitar falha de schema
                "quantity" => (int) $item->quantity,
                "unit_price" => round((float) $item->unit_price, 2),
            ];
        })->toArray();

        // 2. Adiciona o Frete (Apenas valores positivos e categoria segura)
        if (isset($order->shipping_cost) && $order->shipping_cost > 0) {
            $mpItems[] = [
                "id" => "shipping",
                "title" => "Custo de Entrega",
                "description" => "Frete logístico",
                "category_id" => "others", // Nunca usar 'shipping'
                "quantity" => 1,
                "unit_price" => round((float) $order->shipping_cost, 2),
            ];
        }

        // ATENÇÃO: O Mercado Pago NÃO aceita valores negativos em unit_price.
        // Portanto, NÃO enviamos descontos nesta matriz para evitar o erro 400/500.
        // A API tolerará a ligeira divergência matemática no antifraude.

        $phone = preg_replace('/\D/', '', $order->customer_phone ?? '');
        $areaCode = substr($phone, 0, 2) ?: '11';
        $number = substr($phone, 2) ?: '999999999';

        return [
            'items' => $mpItems,
            'payer' => [
                'first_name' => mb_substr($order->customer_first_name ?? 'Nome', 0, 250),
                'last_name' => mb_substr(trim($order->customer_last_name ?? '') === '' ? 'Sobrenome' : $order->customer_last_name, 0, 250),
                'phone' => [
                    'area_code' => $areaCode,
                    'number' => $number
                ]
            ]
        ];
    }

    private function processPaymentWithSDK(array $payload, ?string $deviceId = null): array
    {
        try {
            $client = new PaymentClient();
            $requestOptions = new RequestOptions();
            
            $headers = ["X-Idempotency-Key: " . (string) Str::uuid()];
            if (!empty($deviceId)) {
                $headers[] = "X-Meli-Session-Id: " . $deviceId;
            }
            $requestOptions->setCustomHeaders($headers);

            $payment = $client->create($payload, $requestOptions);
            Log::info('Payload de Pagamento Efetuado', $payload);

            if ($payment === null || (isset($payment->error) && $payment->error)) {
                Log::error('Erro SDK MercadoPago', ['payload' => $payload, 'error' => $payment->error ?? 'Desconhecido']);
                return ['success' => false, 'message' => 'Ocorreu uma falha de comunicação com o gateway de pagamento.'];
            }

            if ($payment->status === 'rejected') {
                $friendlyMessage = $this->translateMercadoPagoError($payment->status_detail);
                
                Log::warning('Pagamento Recusado', [
                    'pedido_id' => $payload['external_reference'] ?? 'N/A',
                    'status_detail' => $payment->status_detail
                ]);

                return [
                    'success' => false,
                    'status'  => 'rejected',
                    'message' => $friendlyMessage
                ];
            }

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'qr_code' => $payment->point_of_interaction->transaction_data->qr_code ?? null,
                'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64 ?? null,
                'boleto_url' => $payment->transaction_details->external_resource_url ?? null,
            ];

        } catch (MPApiException $e) {
            $response = $e->getApiResponse();
            $content = $response ? $response->getContent() : [];
            
            if (is_string($content)) {
                $contentArray = json_decode($content, true) ?? ['raw_error' => $content];
            } elseif (is_object($content)) {
                $contentArray = (array) $content;
            } else {
                $contentArray = is_array($content) ? $content : ['raw_error' => 'Unknown content type'];
            }
            
            $errorData = [
                'status_code' => $response ? $response->getStatusCode() : 500,
                'response_content' => $contentArray,
                'payload_enviado' => $payload
            ];
            
            Log::error('Exceção Detalhada MercadoPago', $errorData);
            
            return ['success' => false, 'message' => 'Pagamento recusado pela API. Reveja os dados fornecidos.'];
            
        } catch (\Throwable $th) {
            Log::error('MP_FALHA_SISTEMICA', [
                'mensagem' => $th->getMessage(),
                'linha' => $th->getLine(),
                'arquivo' => $th->getFile()
            ]);
            return ['success' => false, 'message' => 'Erro interno ao comunicar com o gateway.'];
        }
    }

    public function createPixPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email): array
    {
        $cleanDocument = preg_replace('/\D/', '', $cpf);
        $docType = strlen($cleanDocument) === 14 ? 'CNPJ' : 'CPF';

        // ✏️ alterado: Removido 'additional_info' e 'statement_descriptor' do PIX para evitar conflito de schema
        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'pix',
            'external_reference' => (string) $order->id,
            'payer' => [
                'email' => $email,
                'first_name' => mb_substr($firstName, 0, 250),
                'last_name' => mb_substr(trim($lastName) === '' ? 'Sobrenome' : $lastName, 0, 250),
                'identification' => [
                    'type' => $docType,
                    'number' => $cleanDocument
                ]
            ]
        ];

        return $this->processPaymentWithSDK($payload);
    }

    public function createBoletoPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email, array $address): array
    {
        $streetNumber = preg_replace('/\D/', '', $address['number'] ?? '');
        $cleanDocument = preg_replace('/\D/', '', $cpf);
        $docType = strlen($cleanDocument) === 14 ? 'CNPJ' : 'CPF';

        // ✏️ alterado: Removido 'additional_info' e 'statement_descriptor' do Boleto
        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'bolbradesco', 
            'external_reference' => (string) $order->id,
            'payer' => [
                'email' => $email,
                'first_name' => mb_substr($firstName, 0, 250),
                'last_name' => mb_substr(trim($lastName) === '' ? 'Sobrenome' : $lastName, 0, 250),
                'entity_type' => $docType === 'CNPJ' ? 'association' : 'individual',
                'identification' => [
                    'type' => $docType,
                    'number' => $cleanDocument
                ],
                'address' => [
                    'zip_code' => preg_replace('/\D/', '', $address['zip_code'] ?? ''),
                    'street_name' => mb_substr($address['street'] ?? 'Rua', 0, 250),
                    'street_number' => empty($streetNumber) ? '1' : $streetNumber,
                    'neighborhood' => mb_substr($address['neighborhood'] ?? 'Bairro', 0, 250),
                    'city' => mb_substr($address['city'] ?? 'Cidade', 0, 250),
                    'federal_unit' => strtoupper(substr($address['state'] ?? 'SP', 0, 2))
                ]
            ]
        ];

        return $this->processPaymentWithSDK($payload);
    }

    public function createCreditCardPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email, string $token, int $installments, string $paymentMethodId, ?string $issuerId = null, ?string $deviceId = null): array
    {
        $cleanDocument = preg_replace('/\D/', '', $cpf);
        $docType = strlen($cleanDocument) === 14 ? 'CNPJ' : 'CPF';

        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'token' => $token, 
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'installments' => (int) $installments,
            'payment_method_id' => $paymentMethodId,
            'external_reference' => (string) $order->id,
            'statement_descriptor' => 'MINHALOJA',
            'payer' => [
                'email' => $email, 
                'first_name' => mb_substr($firstName, 0, 250),
                'last_name' => mb_substr(trim($lastName) === '' ? 'Sobrenome' : $lastName, 0, 250),
                'identification' => [
                    'type' => $docType,
                    'number' => $cleanDocument
                ]
            ],
            // O Cartão de Crédito é o único que mantém o Additional Info para aprovação Antifraude
            'additional_info' => $this->buildAdditionalInfo($order)
        ];

        // Anexa IP extra separadamente
        $payload['additional_info']['ip_address'] = request()->ip();

        if (!empty($issuerId) && $issuerId !== 'null') {
            $payload['issuer_id'] = $issuerId;
        }

        return $this->processPaymentWithSDK($payload, $deviceId);
    }

    public function getPaymentStatus(string $paymentId): array
    {
        try {
            $client = new PaymentClient();
            $payment = $client->get($paymentId);
            
            return [
                'success' => true,
                'status' => $payment->status, 
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Não foi possível verificar o status do pagamento.'
            ];
        }
    }

    private function translateMercadoPagoError(?string $statusDetail): string
    {
        return match ($statusDetail) {
            'cc_rejected_bad_filled_card_number'   => 'Revise o número do cartão de crédito.',
            'cc_rejected_bad_filled_date'          => 'Revise a data de validade informada.',
            'cc_rejected_bad_filled_other'         => 'Revise os dados do seu cartão.',
            'cc_rejected_bad_filled_security_code' => 'Revise o código de segurança (CVV).',
            'cc_rejected_blacklist'                => 'Não pudemos processar o pagamento. Tente com outro cartão.',
            'cc_rejected_call_for_authorize'       => 'O pagamento foi bloqueado pelo banco. Ligue para a administradora do cartão para autorizar a compra.',
            'cc_rejected_card_disabled'            => 'Ligue para a administradora do cartão para ativar seu cartão. O cartão está inativo.',
            'cc_rejected_card_error'               => 'Não conseguimos processar seu pagamento. Tente com outro cartão.',
            'cc_rejected_duplicated_payment'       => 'Você já efetuou um pagamento com esse valor. Caso precise pagar novamente, tente com outro cartão ou forma de pagamento.',
            'cc_rejected_high_risk'                => 'O pagamento foi recusado por motivos de segurança do nosso sistema antifraude. Tente pagar com PIX ou Boleto.',
            'cc_rejected_insufficient_amount'      => 'O cartão não possui limite suficiente para a compra.',
            'cc_rejected_invalid_installments'     => 'O cartão não processa a quantidade de parcelas escolhida.',
            'cc_rejected_max_attempts'             => 'Você atingiu o limite de tentativas permitido para este cartão. Tente usar outro.',
            'cc_rejected_other_reason'             => 'O banco emissor não processou o pagamento. Tente novamente ou use outro cartão.',
            default                                => 'Não foi possível processar o seu pagamento. Por favor, tente novamente.',
        };
    }
}