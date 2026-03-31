<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $variantData = [];

    // Intercepta os dados da variante antes de tentar salvar o produto
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->variantData = [
            'sku' => $data['sku'] ?? null,
            'price' => $data['price'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'is_default' => true,
        ];

        // Remove os campos virtuais para não dar erro de coluna inexistente no banco
        unset($data['sku'], $data['price'], $data['quantity']);

        return $data;
    }

    // Salva a variante logo após o produto ser criado
    protected function afterCreate(): void
    {
        if (!empty($this->variantData['sku']) && !empty($this->variantData['price'])) {
            $this->getRecord()->variants()->create($this->variantData);
        }
    }

    // Altera o destino pós-criação: envia o usuário direto para a tela de edição
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    // Atualiza o texto do botão para refletir a ação real
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Salvar e Adicionar Variações');
    }
}