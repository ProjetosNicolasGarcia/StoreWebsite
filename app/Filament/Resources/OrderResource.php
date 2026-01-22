<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Ícone de "Sacola de Compras" ou "Caminhão"
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Traduções
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';
    protected static ?string $navigationLabel = 'Pedidos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Itens do Pedido')
                            ->description('Lista de produtos comprados neste pedido.')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->hiddenLabel() // Remove o label "Items" para limpar o visual
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->label('Produto')
                                            ->disabled()
                                            ->columnSpan(2), // Ocupa mais espaço

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qtd.')
                                            ->numeric()
                                            ->disabled()
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Valor Un.')
                                            ->prefix('R$')
                                            ->disabled()
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4) // Grid de 4 colunas dentro do repeater
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Gerenciamento')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status do Pedido')
                                    ->options([
                                        'pending' => 'Pendente',
                                        'paid' => 'Pago (Aprovado)',
                                        'processing' => 'Em Processamento',
                                        'shipped' => 'Enviado / Em Trânsito',
                                        'delivered' => 'Entregue',
                                        'canceled' => 'Cancelado',
                                        'refunded' => 'Reembolsado',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false),

                                Forms\Components\TextInput::make('tracking_code')
                                    ->label('Código de Rastreio')
                                    ->placeholder('Ex: AA123456789BR')
                                    ->helperText('Informe após o envio.'),
                            ]),

                        Section::make('Resumo Financeiro')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('Cliente')
                                    ->disabled(),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total do Pedido')
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated() // Garante que o valor seja enviado caso precise
                                    ->numeric(),
                            ]),

                        Section::make('Metadados')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Realizado em')
                                    ->content(fn (?Order $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (?Order $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Order $record) => $record === null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Nº Pedido')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'processing' => 'Processando',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregue',
                        'canceled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid', 'processing' => 'info',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'canceled', 'refunded' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('Rastreio')
                    ->copyable()
                    ->icon('heroicon-m-truck')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filtro Rápido por Status
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filtrar por Status')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'processing' => 'Em Processamento',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregue',
                        'canceled' => 'Cancelado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Gerenciar'), // Alterei para "Gerenciar" pois dá ideia de controle
            ])
            ->bulkActions([
                // Pedidos geralmente não devem ser deletados em massa por segurança fiscal/histórico
                // Mas mantive a opção caso precise limpar testes
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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