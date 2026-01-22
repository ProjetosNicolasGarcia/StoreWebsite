<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

/**
 * Resource responsável pela gestão das Categorias de Produtos.
 * As categorias são fundamentais para a organização da loja e navegação do cliente.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    // Ícone de "Etiqueta" (Tag) representa bem o conceito de categorização
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // Definições de Labels para o Menu Admin
    protected static ?string $modelLabel = 'Categoria';
    protected static ?string $pluralModelLabel = 'Categorias';
    protected static ?string $navigationLabel = 'Categorias';

    /**
     * Define o formulário de criação e edição.
     * Utiliza layout de 3 colunas: 2 para dados principais e 1 para metadados.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Informações Gerais')
                            ->description('Defina o nome e a identificação da categoria.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome da Categoria')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Eletrônicos, Roupas...')
                                    // 'live(onBlur: true)' melhora a performance: só dispara a atualização quando o usuário sai do campo
                                    ->live(onBlur: true)
                                    // Gera o Slug automaticamente apenas na criação para evitar quebrar links de SEO na edição
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (URL Amigável)')
                                    ->disabled() // Impede edição manual para garantir padrão
                                    ->dehydrated() // IMPORTANTE: Obriga o envio do dado ao salvar, mesmo estando desabilitado
                                    ->required()
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                    ->helperText('Gerado automaticamente a partir do nome.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela em desktops

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Metadados')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (?Category $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (?Category $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Category $record) => $record === null), // Oculta na tela de criação
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // Define o grid mestre como 3 colunas
    }

    /**
     * Define a tabela de listagem das categorias.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'), // Destaque visual para o nome

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->icon('heroicon-m-link')
                    ->color('gray')
                    ->copyable() // Facilita para o admin copiar o link da categoria
                    ->copyMessage('Slug copiado!'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por padrão para limpar a interface
            ])
            ->defaultSort('name', 'asc') // Ordenação alfabética padrão
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
            // Espaço reservado para RelationManagers (ex: listar produtos dentro da categoria)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}