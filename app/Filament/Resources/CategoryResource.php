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
use Filament\Forms\Components\Select; // Import necessário
use Illuminate\Database\Eloquent\Builder; // Import para queries

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
                            ->description('Defina o nome e a hierarquia da categoria.')
                            ->schema([
                               Forms\Components\TextInput::make('name')
                                    ->label('Nome da Categoria')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Eletrônicos, Roupas...')
                                    // 'live(onBlur: true)' é essencial para disparar o evento assim que o usuário sai do campo
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                        // [REGRA DE OURO]
                                        // Só geramos o slug automaticamente se estivermos CRIANDO um novo registro.
                                        // Se estivermos EDITANDO, não mexemos no slug para não estragar URLs personalizadas (ex: calca-masculina).
                                        if ($operation === 'create') {
                                            $set('slug', \Illuminate\Support\Str::slug($state));
                                        }
                                    }),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (URL Amigável)')
                                    //->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                    ->helperText('Gerado automaticamente a partir do nome.'),

                                // [NOVO] Campo de Hierarquia
                                Select::make('parent_id')
                                    ->label('Categoria Pai (Opcional)')
                                    ->relationship('parent', 'name', function ($query, ?Category $record) {
                                        // [ALTERAÇÃO] Removemos o ->whereNull('parent_id')
                                        // Agora, se estivermos editando, apenas impedimos que a categoria seja pai dela mesma.
                                        if ($record) {
                                            return $query->where('id', '!=', $record->id);
                                        }
                                        return $query;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Nenhuma (É uma categoria principal)'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela em desktops

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Visibilidade')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Ativo')
                                    ->default(true)
                                    ->helperText('Categorias inativas não aparecem no menu.'),
                            ]),

                        Section::make('Metadados')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (?Category $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (?Category $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Category $record) => $record === null),
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
                    ->weight('bold'),

                // [NOVO] Coluna para mostrar quem é o pai
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Dentro de')
                    ->icon('heroicon-m-arrow-turn-right-down')
                    ->color('gray')
                    ->sortable()
                    ->placeholder('—') // Mostra um traço se for raiz
                    ->badge(), // Opcional: dá um visual de etiqueta

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->icon('heroicon-m-link')
                    ->color('gray')
                    ->copyable()
                    ->copyMessage('Slug copiado!')
                    ->toggleable(isToggledHiddenByDefault: true), // Ocultei por padrão para dar espaço

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
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
            // Futuramente podemos adicionar um RelationManager para listar os Produtos desta categoria
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