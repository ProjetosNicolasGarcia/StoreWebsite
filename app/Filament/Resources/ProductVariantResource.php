<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductVariantResource\Pages;
use App\Filament\Resources\ProductVariantResource\RelationManagers;
use App\Models\ProductVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductVariantResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Variações de produtos'; // Traduzindo o menu

public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->required()
                ->label('Produto Pai'),

            Forms\Components\TextInput::make('sku')
                ->label('SKU (Código Único)')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('variation_type_1')
                ->label('Variação 1 (Ex: Cor)'),

            Forms\Components\TextInput::make('variation_type_2')
                ->label('Variação 2 (Ex: Tamanho)'),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->prefix('R$')
                ->label('Preço Final')
                ->required(),

            Forms\Components\TextInput::make('stock_quantity')
                ->numeric()
                ->default(0)
                ->label('Quantidade em Estoque')
                ->required(),
                
            Forms\Components\FileUpload::make('image_url')
                ->image()
                ->directory('variants')
                ->label('Foto Específica (Opcional)'),
        ]);
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('product.name')->label('Produto')->searchable(),
            Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable(),
            Tables\Columns\TextColumn::make('variation_type_1')->label('Var. 1'),
            Tables\Columns\TextColumn::make('variation_type_2')->label('Var. 2'),
            Tables\Columns\TextColumn::make('stock_quantity')->label('Estoque'),
            Tables\Columns\TextColumn::make('price')->money('BRL'),
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Foto')
                ->circular(),
        ])
        ->filters([
            //
        ])
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
            'index' => Pages\ListProductVariants::route('/'),
            'create' => Pages\CreateProductVariant::route('/create'),
            'edit' => Pages\EditProductVariant::route('/{record}/edit'),
        ];
    }
}
