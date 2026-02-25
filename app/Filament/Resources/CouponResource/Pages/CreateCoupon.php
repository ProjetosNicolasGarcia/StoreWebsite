<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    protected static string $resource = CouponResource::class;

    /**
     * Intercepta os dados antes de criar no banco de dados.
     * Garante que o código do cupom seja salvo sempre em MAIÚSCULO,
     * evitando problemas de case-sensitivity na validação do checkout.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }
        
        return $data;
    }
    
    // Opcional: Redireciona de volta para a lista após criar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}