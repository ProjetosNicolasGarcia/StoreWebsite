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

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    // Ícone de "Pastas/Álbuns" combina bem com Coleções
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    // Traduções
    protected static ?string $modelLabel = 'Coleção';
    protected static ?string $pluralModelLabel = 'Coleções';
    protected static ?string $navigationLabel = 'Coleções';

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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug (URL)')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->unique(Collection::class, 'slug', ignoreRecord: true),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->placeholder('Uma breve descrição sobre o tema desta coleção.'),

                                Forms\Components\Select::make('products')
                                    ->label('Produtos nesta Coleção')
                                    ->relationship('products', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->helperText('Selecione quais produtos fazem parte deste grupo.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Mídia e Visibilidade')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Capa / Banner')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('collections')
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
                            ->hidden(fn (?Collection $record) => $record === null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->height(40)
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Qtd. Produtos')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                // Transformei em ToggleColumn para edição rápida na tabela
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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