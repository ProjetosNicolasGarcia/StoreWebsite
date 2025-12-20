<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('id')
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('Pedido #')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'pending' => 'gray',
                    'shipped' => 'info',
                    'canceled' => 'danger',
                    default => 'warning',
                }),

            Tables\Columns\TextColumn::make('total_amount')
                ->money('BRL')
                ->label('Total'),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime('d/m/Y')
                ->label('Data'),
        ])
        ->headerActions([
            // Não permitimos criar pedido por aqui para não quebrar o fluxo
        ])
        ->actions([
            // Botão para ver o pedido completo
            Tables\Actions\Action::make('Ver')
                ->url(fn ($record) => \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record])),
        ]);
}
}
