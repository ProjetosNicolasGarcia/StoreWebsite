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

/**
 * Resource responsável pelo gerenciamento de Banners Publicitários.
 * Permite criar banners para diferentes seções do site (Hero, Seções, Rodapé).
 */
class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    // Definição de ícone e labels para o menu de navegação do Filament
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $modelLabel = 'Banner';
    protected static ?string $pluralModelLabel = 'Banners';
    protected static ?string $navigationLabel = 'Banners';

    /**
     * Define o esquema do formulário de criação/edição.
     * Utiliza um layout de Grade (Grid) com proporção 2/3 (Conteúdo) e 1/3 (Configurações).
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3 da tela) ===
                Group::make()
                    ->schema([
                        Section::make('Conteúdo Visual')
                            ->description('A imagem e os textos principais do banner.')
                            ->schema([
                                // Upload da Imagem com editor integrado
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Imagem do Banner')
                                    ->image()
                                    ->imageEditor() // Permite cortar/ajustar a imagem direto no navegador
                                    ->directory('banners') // Salva em: storage/app/public/banners
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
                                    ->suffixIcon('heroicon-m-link') // Ícone visual para indicar link
                                    ->maxLength(255),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]), // Ocupa 2 de 3 colunas em telas grandes

                // === COLUNA LATERAL (DIREITA - 1/3 da tela) ===
                Group::make()
                    ->schema([
                        Section::make('Configurações de Exibição')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Visível no Site')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),

                                // Select para definir onde o banner será renderizado no frontend
                                Forms\Components\Select::make('location')
                                    ->label('Localização')
                                    ->options([
                                        'hero' => 'Carrossel Principal (Topo)',
                                        'section' => 'Banner de Seção (Meio)',
                                        'footer' => 'Rodapé',
                                    ])
                                    ->required()
                                    ->default('hero')
                                    ->native(false), // Usa o componente UI do Filament em vez do select nativo do navegador

                                Forms\Components\TextInput::make('position')
                                    ->label('Ordem de Exibição')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Menor número aparece primeiro.'),
                            ]),

                        // Exibe metadados apenas na edição (quando o registro já existe)
                        Section::make('Informações do Sistema')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (Banner $record): string => $record->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Última atualização')
                                    ->content(fn (Banner $record): string => $record->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Banner $record) => $record === null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3); // Define o grid mestre como 3 colunas
    }

    /**
     * Define as colunas e filtros da listagem de banners.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->height(40)
                    ->circular(false), // Mantém formato original (quadrado/retangular)

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                // Badge colorida para facilitar identificação visual do local do banner
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
                        'hero' => 'success',   // Verde
                        'section' => 'info',   // Azul
                        'footer' => 'warning', // Amarelo
                        default => 'gray',
                    }),

                // Permite ativar/desativar diretamente na listagem sem entrar na edição
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
                    ->toggleable(isToggledHiddenByDefault: true), // Oculto por padrão para limpar a view
            ])
            ->defaultSort('position', 'asc')
            ->reorderable('position') // Habilita drag-and-drop se a trait estiver na ListBanners
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Filtrar por Local')
                    ->options([
                        'hero' => 'Topo',
                        'section' => 'Seção',
                        'footer' => 'Rodapé',
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
            // Relações adicionais (ex: Analytics) podem ser adicionadas aqui futuramente
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