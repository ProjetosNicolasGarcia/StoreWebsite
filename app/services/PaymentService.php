<?php

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

    private function buildAdditionalInfo(Order $order): array
    {
        $order->loadMissing('items');

        $mpItems = $order->items->map(function ($item) {
            return [
                "id" => (string) $item->product_id,
                "title" => mb_substr($item->product_name ?? 'Produto', 0, 250),
                "description" => mb_substr($item->product_name ?? 'Produto', 0, 250),
                "category_id" => "others",
                "quantity" => (int) $item->quantity,
                "unit_price" => (float) $item->unit_price,
            ];
        })->toArray();

        $phone = preg_replace('/\D/', '', $order->customer_phone ?? '');
        $areaCode = substr($phone, 0, 2) ?: '11';
        $number = substr($phone, 2) ?: '999999999';

        return [
            'items' => $mpItems,
            'payer' => [
                'first_name' => $order->customer_first_name ?? 'Nome',
                'last_name' => trim($order->customer_last_name ?? '') === '' ? 'Sobrenome' : $order->customer_last_name,
                'phone' => [
                    'area_code' => $areaCode,
                    'number' => $number
                ]
            ]
        ];
    }

    // 🛠️ O método agora aceita o $deviceId opcional para injetar no cabeçalho
    private function processPaymentWithSDK(array $payload, ?string $deviceId = null): array
    {
        try {
            $client = new PaymentClient();
            $requestOptions = new RequestOptions();
            
            // 🛠️ Injeta as chaves de idempotência e o Fingerprint do Dispositivo
            $headers = ["X-Idempotency-Key: " . (string) Str::uuid()];
            if (!empty($deviceId)) {
                $headers[] = "X-Meli-Session-Id: " . $deviceId;
            }
            $requestOptions->setCustomHeaders($headers);

            // Chamada oficial da API através do pacote
            $payment = $client->create($payload, $requestOptions);
            Log::info($payload);

            // 1. Falha severa da API (Não retornou o objeto esperado)
            if ($payment === null || (isset($payment->error) && $payment->error)) {
                $errorMsg = $payment->error->message ?? 'Erro na requisição SDK';
                Log::error('Erro SDK MercadoPago', ['payload' => $payload, 'error' => $payment->error ?? 'Desconhecido']);
                return ['success' => false, 'message' => 'Ocorreu uma falha de comunicação com o gateway de pagamento.'];
            }

            // 2. 🛠️ O MAPEAMENTO DE RECUSAS (O Cartão passou, mas a transação foi recusada)
            if ($payment->status === 'rejected') {
                $friendlyMessage = $this->translateMercadoPagoError($payment->status_detail);
                
                Log::warning('Pagamento Recusado', [
                    'pedido_id' => $payload['external_reference'] ?? 'N/A',
                    'status_detail' => $payment->status_detail
                ]);

                return [
                    'success' => false,
                    'status'  => 'rejected',
                    'message' => $friendlyMessage // Devolve a mensagem amigável para o front-end
                ];
            }

            // 3. Status de Sucesso ou Pendente (Boleto/PIX)
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
            
            // GARANTIA ABSOLUTA CONTRA TYPEERROR DE JSON_DECODE
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
            
            return ['success' => false, 'message' => 'Pagamento recusado pela API. Veja o log do sistema.'];
            
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
        // 🛠️ Identificação B2B ou B2C
        $cleanDocument = preg_replace('/\D/', '', $cpf);
        $docType = strlen($cleanDocument) === 14 ? 'CNPJ' : 'CPF';

        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'pix',
            'external_reference' => (string) $order->id,
            'statement_descriptor' => 'MINHALOJA',
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
                'identification' => [
                    'type' => $docType,
                    'number' => $cleanDocument
                ]
            ],
            'additional_info' => $this->buildAdditionalInfo($order)
        ];

        return $this->processPaymentWithSDK($payload);
    }

    public function createBoletoPayment(Order $order, string $cpf, string $firstName, string $lastName, string $email, array $address): array
    {
        $streetNumber = preg_replace('/\D/', '', $address['number'] ?? '');
        
        // 🛠️ Identificação B2B ou B2C
        $cleanDocument = preg_replace('/\D/', '', $cpf);
        $docType = strlen($cleanDocument) === 14 ? 'CNPJ' : 'CPF';

        $payload = [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'description' => "Pedido #" . str_pad($order->id, 6, '0', STR_PAD_LEFT),
            'payment_method_id' => 'bolbradesco', 
            'external_reference' => (string) $order->id,
            'statement_descriptor' => 'MINHALOJA',
            'payer' => [
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => trim($lastName) === '' ? 'Sobrenome' : $lastName,
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
            ],
            'additional_info' => $this->buildAdditionalInfo($order)
        ];

        return $this->processPaymentWithSDK($payload);
    }

    // 🛠️ O método agora aceita o $deviceId como último parâmetro
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
            // 🛠️ Passamos apenas o IP no additional_info para não causar colisão de array de itens
            'additional_info' => [
                'ip_address' => request()->ip()
            ]
        ];

        if (!empty($deviceId)) {
            $payload['device_id'] = $deviceId;
        }

        if (!empty($issuerId) && $issuerId !== 'null') {
            $payload['issuer_id'] = $issuerId;
        }

        // 🛠️ Passamos o $deviceId adiante para ele ser injetado nos Cabeçalhos HTTP
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

    /**
     * 🛠️ NOVO: Dicionário de Tradução de Recusas do Mercado Pago
     * Traduz o código de recusa para uma mensagem amigável e acionável para o cliente.
     */
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