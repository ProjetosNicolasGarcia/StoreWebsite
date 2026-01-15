<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionResource\Pages;
use App\Filament\Resources\CollectionResource\RelationManagers;
use App\Models\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

  public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('slug')->required(),
            
            // ADICIONE ESTE CAMPO:
            Forms\Components\Textarea::make('description')
                ->label('Descrição da Coleção')
                ->rows(2)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('image_url')->image()->label('Banner da Coleção'),
            Forms\Components\Toggle::make('featured_on_home')->label('Mostrar na Home?'),
            
            Forms\Components\Select::make('products')
                ->relationship('products', 'name')
                ->multiple()
                ->preload()
                ->label('Produtos desta Coleção'),
        ]);
}

 public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Imagem da capa da coleção
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Capa'),

            Tables\Columns\TextColumn::make('title')
                ->label('Título')
                ->searchable(),

            // Conta quantos produtos existem dentro dessa coleção
            Tables\Columns\TextColumn::make('products_count')
                ->counts('products')
                ->label('Qtd. Produtos')
                ->sortable(),

            // Mostra se está aparecendo na Home Page
            Tables\Columns\IconColumn::make('featured_on_home')
                ->label('Na Home?')
                ->boolean(),

            Tables\Columns\ToggleColumn::make('is_active')
                ->label('Ativo'),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('slug')
                ->label('Slug')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('description')
                ->label('Descrição')
                ->limit(30) // Limita texto longo
                ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
