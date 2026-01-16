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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Produtos'; // Traduzindo o menu

// Dentro do schema do Repeater...
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // --- BLOCO 1: DADOS PRINCIPAIS (Com a Imagem) ---
            Forms\Components\Section::make('Dados do Produto')->schema([
                
                // 1. Categoria
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Categoria')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        Forms\Components\TextInput::make('slug')->required(),
                    ]),

                // 2. FOTO DE CAPA (O código que você queria)
                Forms\Components\FileUpload::make('image_url')
                    ->image()
                    ->directory('products')
                    ->label('Imagem de Capa')
                    ->required(),

                // 3. Nome e Slug
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Nome do Produto')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                        $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                // 4. Preço e Detalhes
                Forms\Components\TextInput::make('base_price')
                    ->numeric()
                    ->prefix('R$')
                    ->label('Preço Base')
                    ->required(),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Descrição'),
                
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Ativo na Loja'),
            ]),

            // --- BLOCO 2: VARIAÇÕES DE ESTOQUE (Mantendo o que fizemos antes) ---
            Forms\Components\Section::make('Variações de Estoque')->schema([
                Forms\Components\Repeater::make('variants')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU (Cód. Único)')
                            ->required(),
                        
                        // Campos Genéricos (Atualizados)
                        Forms\Components\TextInput::make('variation_type_1')
                            ->label('Tipo 1 (Ex: Cor / Voltagem)'),

                        Forms\Components\TextInput::make('variation_type_2')
                            ->label('Tipo 2 (Ex: Tam. / Capacidade)'),

                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('R$')
                            ->label('Preço Final')
                            ->required(),

                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0)
                            ->label('Estoque'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
            ]),


                // --- BLOCO 3: OFERTA E PROMOÇÃO ---
       
            Section::make('Oferta e Promoção')
                ->schema([
                    TextInput::make('sale_price')
                        ->label('Preço Promocional')
                        ->numeric()
                        ->prefix('R$')
                        // AQUI MUDOU: Valida se é menor que 'base_price' e não 'price'
                        ->lt('base_price'), 
                        
                    DateTimePicker::make('sale_start_date')
                        ->label('Início'),
                        
                    DateTimePicker::make('sale_end_date')
                        ->label('Término')
                        ->after('sale_start_date'), 
                ])->columns(3),

            Section::make('Envio e Entrega')
                ->schema([
                    TextInput::make('weight')
                        ->label('Peso (Kg)')
                        ->numeric()
                        ->step(0.001)
                        ->required(),
                    TextInput::make('height')->label('Altura (cm)')->numeric()->required(),
                    TextInput::make('width')->label('Largura (cm)')->numeric()->required(),
                    TextInput::make('length')->label('Comprimento (cm)')->numeric()->required(),
                ])->columns(4)
                    ]);

        
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome'),
            Tables\Columns\TextColumn::make('base_price')->money('BRL')->label('Preço'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Ativo'),
            // Exibe a imagem de capa do produto
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Capa')
                ->circular(), // Opcional: deixa redonda ou quadrada

            // Exibe a categoria do produto
            Tables\Columns\TextColumn::make('category.name')
                ->label('Categoria')
                ->sortable()
                ->searchable(),

            // Exibe o preço promocional (se houver)
            Tables\Columns\TextColumn::make('sale_price')
                ->money('BRL')
                ->label('Promoção')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true), // Escondido por padrão para não poluir

            // Datas da promoção (úteis para controle)
            Tables\Columns\TextColumn::make('sale_end_date')
                ->dateTime('d/m/Y')
                ->label('Fim Promoção')
                ->toggleable(isToggledHiddenByDefault: true),

            // Slug (Link amigável)
            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('sale_price')
                ->money('BRL')
                ->label('Promoção')
                ->sortable()
                ->placeholder('-'), // Mostra um traço se não houver promoção
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}