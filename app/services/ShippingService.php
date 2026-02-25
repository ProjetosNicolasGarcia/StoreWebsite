<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    protected string $originCep;
    protected string $apiToken;
    protected string $apiUrl;

    public function __construct()
    {
        $this->originCep = env('STORE_CEP_ORIGIN', '09425050');
        $this->apiToken = env('MELHOR_ENVIO_API_TOKEN', '');
        $this->apiUrl = env('MELHOR_ENVIO_URL', 'https://sandbox.melhorenvio.com.br/api/v2/me');
    }

    public function calculate(string $destinationCep, Collection $items): array
    {
        // Remove tudo que não for número (ex: traços)
        $destinationCep = preg_replace('/\D/', '', $destinationCep);
        
        if (strlen($destinationCep) !== 8) {
            return [];
        }

        $productsPayload = [];
        
        foreach ($items as $item) {
            $product = $item instanceof Product ? $item : $item->product;
            
            $productsPayload[] = [
                'id' => (string) $product->id,
                'width' => $product->width ?? 11,
                'height' => $product->height ?? 2,
                'length' => $product->length ?? 16,
                'weight' => $product->weight > 0 ? $product->weight : 0.3,
                'insurance_value' => $product->price,
                'quantity' => $item->quantity ?? 1,
            ];
        }

        try {
            // Chamada real para a API
            $response = Http::withToken($this->apiToken)
                ->acceptJson()
                ->post($this->apiUrl . '/shipment/calculate', [
                    'from' => ['postal_code' => $this->originCep],
                    'to' => ['postal_code' => $destinationCep],
                    'products' => $productsPayload,
                ]);

            if ($response->successful()) {
                return $this->formatResponse($response->json());
            }

            Log::error('Erro API Melhor Envio', ['status' => $response->status(), 'response' => $response->json()]);
            return [];

        } catch (\Exception $e) {
            Log::error('Exceção ao calcular frete: ' . $e->getMessage());
            return [];
        }
    }

    private function formatResponse(array $apiResponse): array
    {
        $options = [];
        
        foreach ($apiResponse as $service) {
            // Ignora se a transportadora não atende a região ou o pacote for muito grande
            if (isset($service['error'])) {
                continue;
            }

            // Junta o nome da empresa e do serviço com um espaço simples (sem o travessão)
            $originalName = $service['company']['name'] . ' ' . $service['name'];

            // Limpa os nomes técnicos para o cliente final ler melhor
            $friendlyName = str_replace(
                ['Jadlog .Com', 'Jadlog .Package', 'Correios PAC', 'Correios SEDEX'], 
                ['Jadlog Expresso', 'Jadlog Econômico', 'Correios PAC', 'Correios Sedex'], 
                $originalName
            );

            $options[] = [
                'id' => (string) $service['id'],
                'name' => $friendlyName, // Utiliza o nome amigável configurado acima
                'price' => (float) $service['price'],
                'days' => (int) $service['delivery_time'],
            ];
        }

        // Ordena do frete mais barato para o mais caro
        usort($options, fn($a, $b) => $a['price'] <=> $b['price']);

        return $options;
    }
}