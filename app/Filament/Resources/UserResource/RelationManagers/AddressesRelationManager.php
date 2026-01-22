<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Endereços Cadastrados';
    
    protected static ?string $modelLabel = 'Endereço';

    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('zip_code')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->columnSpan(1),
                
                Forms\Components\TextInput::make('street')
                    ->label('Rua / Logradouro')
                    ->columnSpan(2),

                Forms\Components\TextInput::make('number')
                    ->label('Número'),

                Forms\Components\TextInput::make('complement')
                    ->label('Complemento'),

                Forms\Components\TextInput::make('neighborhood')
                    ->label('Bairro'),

                Forms\Components\TextInput::make('city')
                    ->label('Cidade'),

                Forms\Components\TextInput::make('state')
                    ->label('Estado (UF)'),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street')
            ->columns([
                Tables\Columns\TextColumn::make('zip_code')
                    ->label('CEP')
                    ->copyable(), // Útil para copiar e calcular frete manualmente se precisar

                Tables\Columns\TextColumn::make('street')
                    ->label('Rua')
                    ->description(fn ($record) => "Nº {$record->number}" . ($record->complement ? " - {$record->complement}" : '')),

                Tables\Columns\TextColumn::make('neighborhood')
                    ->label('Bairro'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->formatStateUsing(fn ($record) => "{$record->city}/{$record->state}"),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Removido CreateAction para não permitir criar endereços pelo admin
            ])
            ->actions([
                // Apenas Visualizar (Read-only)
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalhes')
                    ->modalHeading('Detalhes do Endereço')
                    ->icon('heroicon-m-eye'), 
            ])
            ->bulkActions([
                // Removido DeleteBulkAction para segurança
            ]);
    }
}