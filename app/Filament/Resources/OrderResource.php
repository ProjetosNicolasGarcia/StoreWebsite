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
use Filament\Tables\Actions\Action; // ✅ novo: importação necessária para os botões de ação customizados
use Filament\Forms\Components\TextInput;

/**
 * Resource responsável pelo gerenciamento de Pedidos (Orders).
 * Centraliza o fluxo de vendas, permitindo visualizar itens comprados,
 * atualizar status de entrega e inserir códigos de rastreio.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Ícone de "Sacola de Compras" para fácil identificação visual
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Definições de Labels para o Menu Admin
    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Pedidos';
    protected static ?string $navigationLabel = 'Pedidos';

    /**
     * Define o formulário de visualização e edição do pedido.
     * Nota: Muitos campos são 'disabled' para garantir a integridade fiscal/histórica do pedido.
     */
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
                                // Repeater usado apenas para visualização (disabled), listando os OrderItems relacionados
                                Forms\Components\Repeater::make('items')
                                    ->hiddenLabel() // Remove o label "Items" para limpar o visual
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->relationship('product', 'name')
                                            ->label('Produto')
                                            ->disabled() // Impede troca de produto após venda
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qtd.')
                                            ->numeric()
                                            ->disabled() // Impede alteração de quantidade
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Valor Un.')
                                            ->prefix('R$')
                                            ->disabled() // Impede alteração de preço histórico
                                            ->columnSpan(1),
                                    ])
                                    ->columns(4) // Grid de 4 colunas dentro do repeater
                                    ->addable(false)     // Não permite adicionar novos itens
                                    ->deletable(false)   // Não permite remover itens
                                    ->reorderable(false), // Não permite mudar a ordem
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Gerenciamento Logístico e Fiscal') // ✏️ alterado: Nome mais descritivo
                            ->schema([
                                // Campo principal de controle de fluxo do pedido
                                Forms\Components\Select::make('status')
                                    ->label('Status do Pedido')
                                    ->options([
                                        'pending'   => 'Pendente',
                                        'paid'      => 'Pago (Aprovado)',
                                        'preparing' => 'Em Separação', // ✏️ alterado: substituído 'processing' por 'preparing'
                                        'shipped'   => 'Enviado / Em Trânsito',
                                        'delivered' => 'Entregue',
                                        'canceled'  => 'Cancelado',
                                        'refunded'  => 'Reembolsado',
                                    ])
                                    ->required()
                                    ->native(false) // Componente UI melhorado
                                    ->selectablePlaceholder(false),

                                Forms\Components\TextInput::make('tracking_code')
                                    ->label('Código de Rastreio')
                                    ->placeholder('Ex: AA123456789BR')
                                    ->helperText('Informe este código após despachar o produto.'),
                                    
                                // ✅ novos: Campos para Nota Fiscal
                                Forms\Components\TextInput::make('nf_code')
                                    ->label('Número da NF')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('nf_url')
                                    ->label('URL da Nota Fiscal (PDF/XML)')
                                    ->url()
                                    ->maxLength(65535),
                            ]),

                        Section::make('Resumo Financeiro')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('Cliente')
                                    ->disabled(), // Não permite transferir o pedido para outro usuário

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total do Pedido')
                                    ->prefix('R$')
                                    ->disabled()
                                    ->dehydrated() // IMPORTANTE: Envia o valor ao banco mesmo estando disabled (caso seja necessário recalcular)
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
            ->columns(3); // Layout mestre de 3 colunas
    }

    /**
     * Define a tabela de listagem de pedidos.
     */
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

                // Coluna de Status com Badge colorida e tradução direta
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'Pendente',
                        'paid'      => 'Pago',
                        'preparing' => 'Em Separação', // ✏️ alterado: ajuste na nomenclatura
                        'shipped'   => 'Enviado',
                        'delivered' => 'Entregue',
                        'canceled'  => 'Cancelado',
                        'refunded'  => 'Reembolsado',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending'               => 'gray',
                        'paid'                  => 'success', // ✏️ alterado: verde para pagamento ok
                        'preparing'             => 'primary', // ✏️ alterado: azul para separação
                        'shipped'               => 'warning', // Amarelo
                        'delivered'             => 'success', // Verde
                        'canceled', 'refunded'  => 'danger',  // Vermelho
                        default                 => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BRL') // Formata automaticamente para moeda brasileira
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
            ->defaultSort('created_at', 'desc') // Exibe pedidos mais recentes primeiro
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filtrar por Status')
                    ->options([
                        'pending'   => 'Pendente',
                        'paid'      => 'Pago',
                        'preparing' => 'Em Separação', // ✏️ alterado
                        'shipped'   => 'Enviado',
                        'delivered' => 'Entregue',
                        'canceled'  => 'Cancelado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Gerenciar'), // Termo mais adequado que "Editar" para pedidos
                    
                // ✅ novo: Ação rápida para Iniciar Separação
                Action::make('mark_preparing')
                    ->label('Separar')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Order $record) => $record->status === 'paid') // Só aparece se estiver pago
                    ->action(fn (Order $record) => $record->update(['status' => 'preparing']))
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Separação')
                    ->modalDescription('Confirmar o início da separação deste pedido? O cliente verá que o pedido está sendo preparado.'),

                // ✅ novo: Ação rápida para Informar Expedição/Rastreio
                Action::make('mark_shipped')
                    ->label('Despachar')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (Order $record) => $record->status === 'preparing') // Só aparece se estiver em separação
                    ->form([
                        TextInput::make('tracking_code')
                            ->label('Código de Rastreio')
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'status' => 'shipped',
                            'tracking_code' => $data['tracking_code']
                        ]);
                    })
                    ->modalHeading('Informar Expedição e Rastreio'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relações extras podem ser adicionadas aqui
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