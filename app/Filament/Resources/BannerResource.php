<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Filament\Resources\BannerResource\RelationManagers;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Imagem Promocional')->schema([
                
                Forms\Components\FileUpload::make('image_url')
                    ->image()
                    ->directory('banners') // Salva na pasta 'banners'
                    ->required()
                    ->label('Imagem do Banner')
                    ->columnSpanFull(), // Ocupa a largura toda

                Forms\Components\TextInput::make('title')
                    ->label('Título (Opcional - Para acessibilidade)'),

                Forms\Components\TextInput::make('link_url')
                    ->label('Link de Destino')
                    ->placeholder('https://... ou /categoria/camisetas')
                    ->url(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('position')
                        ->numeric()
                        ->default(0)
                        ->label('Ordem de Exibição')
                        ->helperText('Números menores aparecem primeiro (0, 1, 2...)'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Ativo no Site')
                        ->default(true),
                ]),
            ]),
        ]);
}

   public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Mostra uma miniatura da imagem na lista
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Preview')
                ->height(50),

            Tables\Columns\TextColumn::make('title')
                ->label('Título')
                ->searchable(),

            Tables\Columns\TextColumn::make('position')
                ->label('Ordem')
                ->sortable(),

            Tables\Columns\IconColumn::make('is_active')
                ->boolean()
                ->label('Ativo'),
        ])
        ->defaultSort('position', 'asc') // Ordena por posição automaticamente
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
