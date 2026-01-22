<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Get;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // Ícone de "Etiqueta de Preço" ou "Caixa"
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    // Traduções
    protected static ?string $modelLabel = 'Produto';
    protected static ?string $pluralModelLabel = 'Produtos';
    protected static ?string $navigationLabel = 'Produtos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === COLUNA PRINCIPAL (ESQUERDA - 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('Dados do Produto')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Nome do Produto')
                                    ->placeholder('Ex: Camiseta Básica Algodão')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->required()
                                    ->label('Slug (URL)')
                                    ->unique(ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated(),

                                Textarea::make('description')
                                    ->label('Descrição Detalhada')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                // Características Técnicas
                                KeyValue::make('characteristics')
                                    ->label('Ficha Técnica')
                                    ->helperText('Adicione detalhes técnicos. Ex: Material = Algodão, Garantia = 3 meses.')
                                    ->keyLabel('Característica')
                                    ->valueLabel('Valor')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // === SEÇÃO DE VARIANTES E ESTOQUE ===
                        Section::make('Variações e Estoque')
                            ->description('Gerencie tamanhos, cores, preços e estoques específicos.')
                            ->headerActions([
                                // --- AÇÃO DE PROMOÇÃO EM MASSA ---
                                Action::make('apply_promotion')
                                    ->label('Aplicar Promoção em Massa')
                                    ->icon('heroicon-m-tag')
                                    ->color('warning')
                                    ->form([
                                        // 1. Toggle
                                        Toggle::make('apply_to_all')
                                            ->label('Aplicar a TODAS as variantes?')
                                            ->helperText('Ignora filtros e aplica a todas as variantes.')
                                            ->default(false)
                                            ->live(),

                                        // 2. Selecionar o Atributo
                                        Select::make('target_attribute')
                                            ->label('Atributo Alvo')
                                            ->placeholder('Selecione... (Ex: Cor)')
                                            ->options(function (\Livewire\Component $livewire) {
                                                $variants = $livewire->data['variants'] ?? [];
                                                $attributes = [];
                                                foreach ($variants as $variant) {
                                                    $options = $variant['options'] ?? [];
                                                    if (is_array($options)) {
                                                        foreach (array_keys($options) as $key) {
                                                            $attributes[$key] = $key;
                                                        }
                                                    }
                                                }
                                                return $attributes;
                                            })
                                            ->live()
                                            ->required(fn (Get $get) => !$get('apply_to_all'))
                                            ->hidden(fn (Get $get) => $get('apply_to_all')),

                                        // 3. Selecionar o Valor
                                        Select::make('target_value')
                                            ->label('Valor do Atributo')
                                            ->placeholder('Selecione... (Ex: Azul)')
                                            ->options(function (Get $get, \Livewire\Component $livewire) {
                                                $attribute = $get('target_attribute');
                                                if (!$attribute) return [];
                                                $variants = $livewire->data['variants'] ?? [];
                                                $values = [];
                                                foreach ($variants as $variant) {
                                                    $options = $variant['options'] ?? [];
                                                    if (isset($options[$attribute])) {
                                                        $val = $options[$attribute];
                                                        $values[$val] = $val;
                                                    }
                                                }
                                                return array_unique($values);
                                            })
                                            ->required(fn (Get $get) => !$get('apply_to_all'))
                                            ->hidden(fn (Get $get) => $get('apply_to_all')),

                                        // 4. Dados da Promoção
                                        Group::make([
                                            TextInput::make('bulk_sale_price')
                                                ->label('Novo Preço Promocional')
                                                ->numeric()
                                                ->prefix('R$')
                                                ->required(),
                                        ])->columnSpanFull(),

                                        Group::make([
                                            Forms\Components\DateTimePicker::make('bulk_start_date')
                                                ->label('Início')
                                                ->native(false)
                                                ->seconds(false),
                                            Forms\Components\DateTimePicker::make('bulk_end_date')
                                                ->label('Fim')
                                                ->native(false)
                                                ->seconds(false),
                                        ])->columns(2),
                                    ])
                                    ->action(function (array $data, \Livewire\Component $livewire, Forms\Set $set) {
                                        $variants = $livewire->data['variants'] ?? [];
                                        
                                        if (empty($variants)) {
                                            Notification::make()->title("Erro")->body("Não há variantes criadas.")->danger()->send();
                                            return;
                                        }

                                        $updatedCount = 0;
                                        $applyToAll = $data['apply_to_all'] ?? false;
                                        $targetAttribute = $data['target_attribute'] ?? null;
                                        $targetValue = $data['target_value'] ?? null;

                                        foreach ($variants as $key => $variant) {
                                            $shouldUpdate = false;
                                            if ($applyToAll) {
                                                $shouldUpdate = true;
                                            } else {
                                                $options = $variant['options'] ?? [];
                                                if (isset($options[$targetAttribute]) && $options[$targetAttribute] == $targetValue) {
                                                    $shouldUpdate = true;
                                                }
                                            }

                                            if ($shouldUpdate) {
                                                $set("variants.{$key}.sale_price", $data['bulk_sale_price']);
                                                $set("variants.{$key}.sale_start_date", $data['bulk_start_date']);
                                                $set("variants.{$key}.sale_end_date", $data['bulk_end_date']);
                                                $updatedCount++;
                                            }
                                        }
                                        
                                        if ($updatedCount > 0) {
                                            Notification::make()->title("Sucesso")->body("Promoção aplicada a {$updatedCount} variantes.")->success()->send();
                                        } else {
                                            Notification::make()->title("Atenção")->body("Nenhuma variante encontrada.")->warning()->send();
                                        }
                                    }),
                            ])
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship()
                                    ->label('Lista de Variantes')
                                    ->schema([
                                        Group::make([
                                            KeyValue::make('options')
                                                ->label('Opções (Ex: Cor: Azul, Tam: G)')
                                                ->keyLabel('Atributo')
                                                ->valueLabel('Valor')
                                                ->columnSpanFull(),
                                            
                                            TextInput::make('sku')
                                                ->label('SKU')
                                                ->required(),
                                                
                                            Toggle::make('is_default')
                                                ->label('Variante Principal?')
                                                ->helperText('Será a primeira a aparecer na página do produto.')
                                                ->inline(false)
                                                ->onColor('success'),
                                        ])->columns(3),

                                        Group::make([
                                            TextInput::make('price')
                                                ->numeric()
                                                ->prefix('R$')
                                                ->label('Preço Base')
                                                ->required(),

                                            TextInput::make('sale_price')
                                                ->numeric()
                                                ->prefix('R$')
                                                ->label('Preço Oferta')
                                                ->lt('price'), 

                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->default(0)
                                                ->label('Estoque')
                                                ->required(),
                                        ])->columns(3),

                                        Group::make([
                                            Forms\Components\DateTimePicker::make('sale_start_date')->label('Início Oferta')->native(false)->seconds(false),
                                            Forms\Components\DateTimePicker::make('sale_end_date')->label('Fim Oferta')->native(false)->seconds(false)->after('sale_start_date'), 
                                        ])->columns(2),

                                        FileUpload::make('image')
                                            ->label('Foto desta Variante')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('products/variants')
                                            ->columnSpanFull(),

                                        FileUpload::make('images')
                                            ->label('Galeria Extra (Ângulos)')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->directory('products/variants')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed()
                                    ->cloneable()
                                    ->itemLabel(fn (array $state): ?string => 
                                        (isset($state['sku']) ? $state['sku'] : 'Nova Variante') . 
                                        (isset($state['price']) ? ' - R$ ' . $state['price'] : '')
                                    )
                                    ->columnSpanFull(),
                            ]),

                    ])->columnSpan(['lg' => 2]),

                // === COLUNA LATERAL (DIREITA - 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('Organização')
                            ->schema([
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Produto Ativo')
                                    ->onColor('success'),

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->label('Categoria')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('slug', Str::slug($state))),
                                        TextInput::make('slug')->required(),
                                    ]),
                            ]),

                        Section::make('Mídia Principal')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Capa da Vitrine')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->required(),

                                FileUpload::make('gallery')
                                    ->label('Galeria Geral')
                                    ->helperText('Fotos que aparecem para todas as variantes.')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->directory('products/gallery')
                                    ->maxFiles(5),
                            ]),

                        Section::make('Dimensões (Frete)')
                            ->schema([
                                TextInput::make('weight')->label('Peso (Kg)')->numeric()->step(0.001)->default(0),
                                TextInput::make('height')->label('Altura (cm)')->numeric()->default(0),
                                TextInput::make('width')->label('Largura (cm)')->numeric()->default(0),
                                TextInput::make('length')->label('Comp. (cm)')->numeric()->default(0),
                            ])->columns(2),

                        Section::make('Auditoria')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Criado em')
                                    ->content(fn (?Product $record): string => $record?->created_at?->format('d/m/Y H:i') ?? '-'),

                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Atualizado em')
                                    ->content(fn (?Product $record): string => $record?->updated_at?->format('d/m/Y H:i') ?? '-'),
                            ])
                            ->hidden(fn (?Product $record) => $record === null),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Capa')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (Tables\Columns\TextColumn $column): ?string => $column->getState()),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('price')
                    ->label('A partir de')
                    ->money('BRL')
                    ->sortable(false),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo')
                    ->onColor('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Cadastro')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Categoria'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),
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

    public static function getRelations(): array { return []; }
    public static function getPages(): array { 
        return [
            'index' => Pages\ListProducts::route('/'), 
            'create' => Pages\CreateProduct::route('/create'), 
            'edit' => Pages\EditProduct::route('/{record}/edit')
        ]; 
    }
}