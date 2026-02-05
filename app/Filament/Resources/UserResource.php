<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

/**
 * Resource responsável pelo gerenciamento de Clientes (Usuários).
 * Centraliza os dados cadastrais e fornece acesso rápido ao histórico de pedidos e endereços.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Ícone de "Grupo de Usuários"
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // Configurações de Labels
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $navigationLabel = 'Clientes';

    /**
     * Define o formulário de cadastro e edição de clientes.
     * Implementa travas de segurança para impedir a alteração de dados sensíveis (email/nome) pelo painel.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Dados Pessoais')
                            ->description('Informações de identificação do cliente.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user')
                                    // SEGURANÇA: Bloqueia edição para manter consistência com dados fiscais/pedidos
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->suffixIcon(fn (string $operation) => $operation === 'edit' ? 'heroicon-m-lock-closed' : null)
                                    ->dehydrated(), // Garante envio ao banco na criação

                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->unique(ignoreRecord: true)
                                    // SEGURANÇA: Bloqueia alteração de e-mail para evitar Account Takeover
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->helperText(fn (string $operation) => $operation === 'edit'
                                        ? 'Por segurança, o e-mail não pode ser alterado pelo painel. Contate o suporte técnico se necessário.'
                                        : null),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Acesso e Segurança')
                            ->schema([
                                // Campo de senha visível APENAS na criação
                                Forms\Components\TextInput::make('password')
                                    ->label('Senha Inicial')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Defina uma senha apenas ao criar o usuário.')
                                    ->hiddenOn('edit'),

                                // Placeholder informativo para a tela de edição
                                Forms\Components\Placeholder::make('password_info')
                                    ->label('Senha')
                                    ->content('A senha está oculta por segurança.')
                                    ->hiddenOn('create'),
                            ]),

                        Section::make('Metadados')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Cliente desde')
                                    ->content(fn (?User $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (?User $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?User $record) => $record === null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // Layout mestre de 3 colunas
    }

    /**
     * Define a tabela de listagem de clientes.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->icon('heroicon-m-envelope')
                    ->copyable() // Facilita o trabalho do suporte ao copiar e-mail
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') // Clientes novos primeiro
            ->actions([
                Tables\Actions\EditAction::make(),
                // DeleteAction mantida, mas use com cautela (pode quebrar integridade de pedidos antigos)
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Define as abas de relacionamento no rodapé da edição.
     * Transforma a tela de usuário em um mini-CRM.
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,    // Histórico de Compras
            RelationManagers\AddressesRelationManager::class, // Endereços de Entrega
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}