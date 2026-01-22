<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionResource\Pages;
use App\Models\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

/**
 * Resource responsável pela gestão de Coleções de Produtos.
 * Coleções são agrupamentos manuais de produtos (ex: "Promoção de Inverno", "Mais Vendidos")
 * e diferem das Categorias por não serem necessariamente hierárquicas.
 */
class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    // Ícone de "Pastas/Álbuns" combina bem com a ideia de agrupar itens
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    // Configurações de Labels e Navegação
    protected static ?string $modelLabel = 'Coleção';
    protected static ?string $pluralModelLabel = 'Coleções';
    protected static ?string $navigationLabel = 'Coleções';

    /**
     * Define o formulário de criação e edição.
     * Segue o padrão de layout Mestre-Detalhe (2 colunas principais, 1 lateral).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Detalhes da Coleção')
                            ->description('Informações básicas e produtos associados.')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Título da Coleção')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ex: Inverno 2026, Mais Vendidos...')
                                    // Live updates com onBlur melhora performance em campos de texto
                                    ->live(onBlur: true)
                                    // Gera o Slug automaticamente apenas na criação
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->disabled() // Impede edição manual para manter consistência
                                    ->dehydrated() // Garante que o valor seja enviado ao banco mesmo desabilitado
                                    ->required()
                                    ->unique(Collection::class, 'slug', ignoreRecord: true),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Uma breve descrição sobre o tema desta coleção.'),

                                // Campo de relacionamento Many-to-Many para associar produtos
                                Forms\Components\Select::make('products')
                                    ->label('Produtos nesta Coleção')
                                    ->relationship('products', 'name') // Relacionamento definido no Model Collection
                                    ->multiple()
                                    ->preload() // Carrega opções antecipadamente (cuidado se houver milhares de produtos)
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->helperText('Selecione quais produtos fazem parte deste grupo.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Mídia e Visibilidade')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Capa / Banner')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('collections') // storage/app/public/collections
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Coleção Ativa')
                                    ->default(true)
                                    ->onColor('success'),

                                Forms\Components\Toggle::make('featured_on_home')
                                    ->label('Destacar na Home?')
                                    ->helperText('Exibe esta coleção na página inicial da loja.')
                                    ->onColor('warning'),
                            ]),

                        Section::make('Metadados')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (?Collection $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (?Collection $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Collection $record) => $record === null), // Só exibe na edição
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // Grid mestre de 3 colunas
    }

    /**
     * Define a tabela de listagem das coleções.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->height(40)
                    ->circular(), // Exibe miniatura circular

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Coluna calculada que conta os relacionamentos sem carregar todos os registros (performance otimizada)
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Qtd. Produtos')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                // ToggleColumn permite alterar o status booleano diretamente na listagem (requer Model bindado corretamente)
                Tables\Columns\ToggleColumn::make('featured_on_home')
                    ->label('Na Home?')
                    ->onColor('warning')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por padrão

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc') // Mais recentes primeiro
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
            // Managers de relação podem ser adicionados aqui futuramente
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}