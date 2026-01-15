<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Pedidos'; // Traduzindo o menu

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: STATUS E DADOS GERAIS ---
                Forms\Components\Section::make('Detalhes do Pedido')->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Cliente')
                        ->disabled(), // Não mudamos o dono do pedido

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pendente',
                            'paid' => 'Pago',
                            'processing' => 'Em Processamento',
                            'shipped' => 'Enviado',
                            'delivered' => 'Entregue',
                            'canceled' => 'Cancelado',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('total_amount')
                        ->prefix('R$')
                        ->label('Valor Total')
                        ->disabled(), // Valor não deve ser mexido manualmente

                    Forms\Components\TextInput::make('tracking_code')
                        ->label('Código de Rastreio')
                        ->placeholder('Ex: AA123456789BR'),
                ])->columns(2),

                // --- SEÇÃO 2: ITENS COMPRADOS ---
                Forms\Components\Section::make('Itens do Carrinho')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('product', 'name')
                                ->label('Produto')
                                ->disabled(),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->label('Qtd')
                                ->disabled(),

                            Forms\Components\TextInput::make('unit_price')
                                ->prefix('R$')
                                ->label('Preço Un.')
                                ->disabled(),
                        ])
                        ->columns(3)
                        ->addable(false) // Não adiciona itens manualmente
                        ->deletable(false) // Não deleta itens (preserva histórico)
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->money('BRL')->label('Total'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'success',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'canceled' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Data'),

                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('Rastreio')
                    ->copyable() // Permite copiar com um clique
                    ->searchable(),

            ])
            ->defaultSort('created_at', 'desc') // Mais recentes primeiro
            ->actions([
                Tables\Actions\EditAction::make(),


            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
