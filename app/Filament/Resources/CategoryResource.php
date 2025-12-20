<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Categorias'; // Traduzindo o menu

   public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->label('Nome da Categoria')
                ->live(onBlur: true)
                // Isso gera o slug (link amigável) automaticamente quando você digita o nome
                ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                    $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->disabled()
                ->dehydrated() // Garante que será salvo mesmo estando desabilitado
                ->unique(ignoreRecord: true),
        ]);
}

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome'),
            Tables\Columns\TextColumn::make('slug')->label('Slug'),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
