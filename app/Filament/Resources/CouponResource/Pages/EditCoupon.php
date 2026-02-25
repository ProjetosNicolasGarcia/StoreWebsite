<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Intercepta os dados antes de atualizar no banco de dados.
     * Mantém a consistência forçando o MAIÚSCULO caso o admin edite o código.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        
        return $data;
    }
    
    // Opcional: Redireciona de volta para a lista após editar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}