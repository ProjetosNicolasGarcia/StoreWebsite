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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    // Ícone de "Etiqueta" combina mais com Categoria
    protected static ?string $navigationIcon = 'heroicon-o-tag'; 

    // Traduções do Recurso
    protected static ?string $modelLabel = 'Categoria';
    protected static ?string $pluralModelLabel = 'Categorias';
    protected static ?string $navigationLabel = 'Categorias';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA) ===
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (URL Amigável)')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                    ->helperText('Gerado automaticamente a partir do nome.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela

                // === COLUNA LATERAL (DIREITA) ===
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
                            ->hidden(fn (?Category $record) => $record === null), // Só aparece na edição
                    ])
                    ->columnSpan(['lg' => 1]), // Ocupa 1/3 da tela
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

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->icon('heroicon-m-link')
                    ->color('gray')
                    ->copyable() // Permite copiar o link com um clique
                    ->copyMessage('Slug copiado!'),

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
            // Se quiser listar produtos desta categoria aqui futuramente, usamos RelationManagers
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