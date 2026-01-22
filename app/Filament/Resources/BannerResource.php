<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    // Alterei o ícone para um mais representativo de "Imagem/Banner"
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    // Rótulos em Português para o Menu
    protected static ?string $modelLabel = 'Banner';
    protected static ?string $pluralModelLabel = 'Banners';
    protected static ?string $navigationLabel = 'Banners';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Conteúdo Visual')
                            ->description('A imagem e os textos principais do banner.')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Imagem do Banner')
                                    ->image()
                                    ->imageEditor() // Permite cortar/ajustar a imagem no painel
                                    ->directory('banners')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('title')
                                    ->label('Título Principal')
                                    ->placeholder('Ex: Promoção de Verão')
                                    ->helperText('Usado para acessibilidade (Alt Text) e SEO.')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Subtítulo / Descrição')
                                    ->placeholder('Breve descrição que aparece sobre o banner (se o layout permitir).')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('link_url')
                                    ->label('Link de Destino')
                                    ->placeholder('https://... ou /categoria/ofertas')
                                    ->suffixIcon('heroicon-m-link')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2 colunas em telas grandes

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Configurações de Exibição')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Visível no Site')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),

                                Forms\Components\Select::make('location')
                                    ->label('Localização')
                                    ->options([
                                        'hero' => 'Carrossel Principal (Topo)',
                                        'section' => 'Banner de Seção (Meio)',
                                        'footer' => 'Rodapé',
                                    ])
                                    ->required()
                                    ->default('hero')
                                    ->native(false), // Visual mais bonito do select

                                Forms\Components\TextInput::make('position')
                                    ->label('Ordem de Exibição')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Menor número aparece primeiro.'),
                            ]),

                        Section::make('Informações do Sistema')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (Banner $record): string => $record->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (Banner $record): string => $record->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Banner $record) => $record === null), // Só mostra na edição
                    ])
                    ->columnSpan(['lg' => 1]), // Ocupa 1 coluna
            ])
            ->columns(3); // Define o grid mestre como 3 colunas
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->height(40)
                    ->circular(false), // Quadrado fica melhor para ver banners

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                Tables\Columns\TextColumn::make('location')
                    ->label('Local')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hero' => 'Topo (Hero)',
                        'section' => 'Meio (Seção)',
                        'footer' => 'Rodapé',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'hero' => 'success',
                        'section' => 'info',
                        'footer' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Ordem')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position', 'asc') // Ordenação padrão por posição
            ->reorderable('position') // Permite arrastar e soltar na tabela (se o Filament estiver configurado para isso)
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Filtrar por Local')
                    ->options([
                        'hero' => 'Topo',
                        'section' => 'Seção',
                    ]),
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