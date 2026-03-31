<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'Produto';
    protected static ?string $pluralModelLabel = 'Produtos';
    protected static ?string $navigationLabel = 'Produtos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Dados do Produto')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Nome do Produto')
                                    ->placeholder('Ex: Camiseta Básica Algodão')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->required()
                                    ->label('Slug (URL)')
                                    ->unique(ignoreRecord: true)
                                    ->dehydrated()
                                    ->helperText('URL amigável. Altere manualmente se necessário.'),

                                Textarea::make('description')
                                    ->label('Descrição Detalhada')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                KeyValue::make('characteristics')
                                    ->label('Ficha Técnica')
                                    ->helperText('Adicione detalhes técnicos para especificação.')
                                    ->keyLabel('Característica')
                                    ->valueLabel('Valor')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // NOVA SEÇÃO: Visível apenas na criação
                        Section::make('Configuração Inicial (Variante Padrão)')
                            ->description('Defina o preço e estoque base. Variações adicionais (cores, tamanhos) poderão ser cadastradas na próxima etapa.')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU (Código Único)')
                                    ->required(),
                                
                                TextInput::make('price')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->label('Preço Base')
                                    ->required(),
                                
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(0)
                                    ->label('Estoque Inicial')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->visibleOn('create'), 

                        Section::make('Dimensões (Frete)')
                            ->description('Usado para cálculo de Correios/Transportadora.')
                            ->schema([
                                TextInput::make('weight')->label('Peso (Kg)')->numeric()->step(0.001)->default(0),
                                TextInput::make('height')->label('Altura (cm)')->numeric()->default(0),
                                TextInput::make('width')->label('Largura (cm)')->numeric()->default(0),
                                TextInput::make('length')->label('Comp. (cm)')->numeric()->default(0),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Organização')
                            ->schema([
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Produto Ativo')
                                    ->onColor('success'),

                                Select::make('categories')
                                    ->label('Categorias')
                                    ->multiple()
                                    ->relationship(
                                        name: 'categories',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query) => $query->with('parent')
                                    )
                                    ->searchable(['name', 'slug'])
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                                        $record->parent 
                                            ? "{$record->name} ({$record->parent->name})" 
                                            : $record->name
                                    )
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nome da Categoria')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) =>
                                                $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                        
                                        TextInput::make('slug')
                                            ->label('Slug (URL)')
                                            ->required()
                                            ->dehydrated()
                                            ->unique('categories', 'slug', ignoreRecord: true),
                                        
                                        Select::make('parent_id')
                                            ->label('Categoria Pai')
                                            ->relationship('parent', 'name')
                                            ->searchable(),
                                    ]),
                            ]),

                        Section::make('Mídia Principal')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Capa da Vitrine')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->optimize('webp')
                                    ->maxImageWidth(1920)
                                    ->required(),

                                FileUpload::make('gallery')
                                    ->label('Galeria Geral')
                                    ->helperText('Fotos que aparecem para todas as variantes.')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->directory('products/gallery')
                                    ->optimize('webp')
                                    ->maxImageWidth(1920)
                                    ->maxFiles(5),
                            ]),

                        Section::make('Auditoria')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (?Product $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Atualizado em')
                                    ->content(fn (?Product $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Product $record) => $record === null),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'variants:id,product_id,price,sale_price,sale_start_date,sale_end_date,is_default',
                'categories'
            ]))
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categorias')
                    ->badge()
                    ->listWithLineBreaks()
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('A partir de')
                    ->money('BRL')
                    ->sortable(false),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->onColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Cadastro')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->label('Categorias')
                    ->searchable(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),
            ])
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
            RelationManagers\VariantsRelationManager::class,
        ]; 
    }
    
    public static function getPages(): array 
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit')
        ];
    }
}