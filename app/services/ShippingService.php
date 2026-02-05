<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class ShippingService
{
    protected string $originCep;

    public function __construct()
    {
        // Pega o CEP do .env, ou usa um padrão se não existir
        $this->originCep = env('STORE_CEP_ORIGIN', '00000000');
    }

    /**
     * Calcula o frete para uma lista de itens
     * * @param string $destinationCep
     * @param Collection $items (Pode ser os itens do carrinho)
     * @return array
     */
    public function calculate(string $destinationCep, Collection $items): array
    {
        // 1. Calcular Peso Total e Dimensões Totais
        $totalWeight = 0;
        
        foreach ($items as $item) {
            // Se o item for um Product direto ou um CartItem com relação product
            $product = $item instanceof Product ? $item : $item->product;
            $qty = $item->quantity ?? 1;

            // Peso padrão de 1kg se não estiver cadastrado
            $weight = $product->weight > 0 ? $product->weight : 1.0; 
            
            $totalWeight += ($weight * $qty);
        }

        // 2. Lógica de Cálculo (AQUI ENTRARIA A API DOS CORREIOS/MELHOR ENVIO)
        // Por enquanto, faremos uma simulação baseada no peso para testar:
        
        // Exemplo: R$ 20,00 base + R$ 5,00 por quilo adicional
        $costSedex = 20.00 + ($totalWeight * 5.00); 
        $costPac = 15.00 + ($totalWeight * 3.00);

        // Se o CEP for da mesma cidade (exemplo simplório pelos 3 primeiros dígitos)
        if (substr($this->originCep, 0, 3) === substr($destinationCep, 0, 3)) {
            $costSedex *= 0.5; // 50% de desconto para local
            $costPac *= 0.5;
        }

        return [
            [
                'name' => 'PAC',
                'price' => max(10, $costPac), // Mínimo 10 reais
                'days' => 7,
            ],
            [
                'name' => 'SEDEX',
                'price' => max(15, $costSedex), // Mínimo 15 reais
                'days' => 2,
            ]
        ];
    }
}