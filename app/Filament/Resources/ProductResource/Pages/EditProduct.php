<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRecordQuery(): Builder
    {
        return parent::getRecordQuery()->with('categories');
    }

    // ADICIONE ESTE MÉTODO:
    // Unifica o formulário e as variantes em abas.
    // O botão de salvar se manterá perfeitamente ancorado no layout da página principal.
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}