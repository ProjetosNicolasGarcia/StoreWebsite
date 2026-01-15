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
                    ->maxLength(255), //,

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('position')
                        ->numeric()
                        ->default(0)
                        ->label('Ordem de Exibição')
                        ->helperText('Números menores aparecem primeiro (0, 1, 2...)'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Ativo no Site')
                        ->default(true),

                   
                    Forms\Components\Select::make('location')
                        ->label('Localização do Banner')
                        ->options([
                            'hero' => 'Carrossel Principal (Topo)',
                            'section' => 'Banner de Seção (Meio)',
                        ])
                        ->required()
                        ->default('hero'),
                    Forms\Components\Textarea::make('description')
                        ->label('Descrição do Banner')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(), // Opcional: faz ocupar a largura toda
                    // ...
                                    ]),
                                ]),
                            ]);
}

 public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Exibe a imagem em miniatura
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Imagem'),
            
            Tables\Columns\TextColumn::make('title')
                ->label('Título')
                ->searchable(),

            // NOVA COLUNA: Localização (Hero ou Seção)
            Tables\Columns\TextColumn::make('location')
                ->label('Local')
                ->badge() // Deixa com visual de etiqueta colorida
                ->color(fn (string $state): string => match ($state) {
                    'hero' => 'success', // Verde para o topo
                    'section' => 'info', // Azul para seções
                    default => 'gray',
                }),

            // NOVA COLUNA: Descrição (limitada a 30 caracteres para não quebrar a tabela)
            Tables\Columns\TextColumn::make('description')
                ->label('Descrição')
                ->limit(30)
                ->toggleable(isToggledHiddenByDefault: true), // Escondido por padrão para não poluir

            Tables\Columns\TextColumn::make('link_url')
                ->label('Link')
                ->limit(20),
            
            Tables\Columns\TextColumn::make('position')
                ->label('Ordem')
                ->sortable(),
            
            Tables\Columns\ToggleColumn::make('is_active')
                ->label('Ativo'),

            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            //
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
