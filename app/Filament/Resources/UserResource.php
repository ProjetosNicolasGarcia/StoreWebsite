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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Ícone de "Grupo de Usuários"
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    // Traduções
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $navigationLabel = 'Clientes';

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
                                    // BLOQUEIO DE EDIÇÃO:
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    // Opcional: Adiciona um cadeado visual para indicar que está travado
                                    ->suffixIcon(fn (string $operation) => $operation === 'edit' ? 'heroicon-m-lock-closed' : null)
                                    ->dehydrated(), // Garante que o dado seja enviado ao banco se for um create

                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->unique(ignoreRecord: true)
                                    // ADIÇÃO DE SEGURANÇA:
                                    // Só deixa editar se estiver criando um novo. 
                                    // Se estiver editando um existente, bloqueia.
                                    ->disabled(fn (string $operation) => $operation === 'edit')
                                    ->helperText(fn (string $operation) => $operation === 'edit' 
                                        ? 'Por segurança, o e-mail não pode ser alterado pelo painel. Peça ao cliente para alterar no perfil dele ou contate o suporte técnico (desenvolvedor).' 
                                        : null),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Acesso e Segurança')
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Senha Inicial')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Defina uma senha apenas ao criar o usuário.')
                                    ->hiddenOn('edit'), // Mantive sua lógica de segurança
                                
                                // Apenas informativo na edição
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
            ->columns(3);
    }

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
                    ->copyable() // Facilita copiar para enviar email
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // Mantém a aba de pedidos que você já configurou
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\AddressesRelationManager::class,
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