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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Produtos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === GRUPO 1: INFORMAÇÕES PRINCIPAIS ===
                Group::make()
                    ->schema([
                        Section::make('Dados do Produto')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->label('Nome do Produto')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                TextInput::make('slug')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Textarea::make('description')
                                    ->label('Descrição Geral')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                // Características Técnicas
                                KeyValue::make('characteristics')
                                    ->label('Ficha Técnica Geral')
                                    ->keyLabel('Característica (Ex: Material)')
                                    ->valueLabel('Valor (Ex: Algodão)')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // === SEÇÃO DE VARIANTES E ESTOQUE ===
                        Section::make('Variações e Estoque')
                            ->description('Gerencie tamanhos, cores, preços e estoques específicos.')
                            ->headerActions([
                                // --- AÇÃO DE PROMOÇÃO EM MASSA (COM SELECTS) ---
   Action::make('apply_promotion')
    ->label('Aplicar Promoção em Massa')
    ->icon('heroicon-m-tag')
    ->color('warning')
    ->form([
        // 1. Toggle
        Forms\Components\Toggle::make('apply_to_all')
            ->label('Aplicar a TODAS as variantes desta lista?')
            ->helperText('Ignora filtros e aplica a todas as variantes visíveis acima.')
            ->default(false)
            ->live(),

        // 2. Selecionar o Atributo
        Select::make('target_attribute')
            ->label('Escolha o Atributo')
            ->placeholder('Selecione... (Ex: Cor)')
            ->options(function (\Livewire\Component $livewire) {
                // CORREÇÃO: Acessamos os dados direto do Livewire ($livewire->data)
                // Isso garante que pegamos as variantes mesmo antes de salvar o produto.
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
                return $attributes; // Retorna array limpo (Ex: ['Cor' => 'Cor', 'Tamanho' => 'Tamanho'])
            })
            ->live()
            ->required(fn (Forms\Get $get) => !$get('apply_to_all'))
            ->hidden(fn (Forms\Get $get) => $get('apply_to_all')),

        // 3. Selecionar o Valor
        Select::make('target_value')
            ->label('Escolha o Valor')
            ->placeholder('Selecione... (Ex: Branco)')
            ->options(function (Forms\Get $get, \Livewire\Component $livewire) {
                // Acessa dados do Modal
                $attribute = $get('target_attribute');
                if (!$attribute) return [];

                // Acessa dados do Formulário Principal
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
            ->required(fn (Forms\Get $get) => !$get('apply_to_all'))
            ->hidden(fn (Forms\Get $get) => $get('apply_to_all')),

        // 4. Dados da Promoção
        Forms\Components\Group::make([
            Forms\Components\TextInput::make('bulk_sale_price')
                ->label('Novo Preço Promocional')
                ->numeric()
                ->prefix('R$')
                ->required(),
        ])->columnSpanFull(),

        Forms\Components\Group::make([
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
        // CORREÇÃO: Buscamos as variantes da fonte da verdade (Livewire Data)
        $variants = $livewire->data['variants'] ?? [];
        
        if (empty($variants)) {
            Notification::make()
                ->title("Erro")
                ->body("Não há variantes criadas para aplicar a promoção.")
                ->danger()
                ->send();
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
                // Compara frouxamente (==) para evitar problemas de tipo string/int
                if (isset($options[$targetAttribute]) && $options[$targetAttribute] == $targetValue) {
                    $shouldUpdate = true;
                }
            }

            if ($shouldUpdate) {
                // Atualização usando Dot Notation no $set do Formulário
                // Isso atualiza visualmente os campos na tela
                $set("variants.{$key}.sale_price", $data['bulk_sale_price']);
                $set("variants.{$key}.sale_start_date", $data['bulk_start_date']);
                $set("variants.{$key}.sale_end_date", $data['bulk_end_date']);
                $updatedCount++;
            }
        }
        
        if ($updatedCount > 0) {
            Notification::make()
                ->title("Sucesso!")
                ->body("Promoção aplicada a {$updatedCount} variantes. Salve o produto para persistir.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Atenção")
                ->body("Nenhuma variante correspondeu aos critérios (Atributo: $targetAttribute, Valor: $targetValue).")
                ->warning()
                ->send();
        }
    }),
                            ])
                            ->schema([
                                Repeater::make('variants')
                                    ->relationship()
                                    ->schema([
                                        Group::make([
                                            KeyValue::make('options')
                                                ->label('Opções (Ex: Cor: Azul)')
                                                ->keyLabel('Atributo')
                                                ->valueLabel('Valor')
                                                ->columnSpanFull(),
                                            
                                            TextInput::make('sku')
                                                ->label('SKU (Cód. Único)')
                                                ->required(),
                                                
                                            Toggle::make('is_default')
                                                ->label('Opção Padrão?')
                                                ->inline(false)
                                                ->onColor('success'),
                                        ])->columns(3),

                                        Group::make([
                                            TextInput::make('price')
                                                ->numeric()
                                                ->prefix('R$')
                                                ->label('Preço')
                                                ->required(),

                                            TextInput::make('sale_price')
                                                ->numeric()
                                                ->prefix('R$')
                                                ->label('Preço Promocional')
                                                ->lt('price'), 

                                            TextInput::make('quantity')
                                                ->numeric()
                                                ->default(0)
                                                ->label('Estoque')
                                                ->required(),
                                        ])->columns(3),

                                        Forms\Components\Group::make([
                                            Forms\Components\DateTimePicker::make('sale_start_date')->label('Início da Promoção')->native(false)->seconds(false),
                                            Forms\Components\DateTimePicker::make('sale_end_date')->label('Fim da Promoção')->native(false)->seconds(false)->after('sale_start_date'), 
                                        ])->columns(2),

                                        // Mídia
                                        FileUpload::make('image')
                                            ->label('Foto de Capa da Variante')
                                            ->image()
                                            ->directory('products/variants')
                                            ->columnSpanFull(),

                                        FileUpload::make('images')
                                            ->label('Galeria Extra da Variante')
                                            ->helperText('Adicione ângulos extras e detalhes específicos desta cor/modelo.')
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

                    ])->columnSpan(2),

                // === GRUPO 2: LATERAL ===
                Group::make()
                    ->schema([
                        Section::make('Status e Organização')
                            ->schema([
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Visível na Loja'),

                                Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->label('Categoria')
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, $state) => $set('slug', Str::slug($state))),
                                        TextInput::make('slug')->required(),
                                    ]),
                            ]),

                        Section::make('Mídia Geral')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Capa Principal (Vitrine)')
                                    ->image()
                                    ->directory('products')
                                    ->required(),

                                FileUpload::make('gallery')
                                    ->label('Galeria Geral')
                                    ->image()
                                    ->multiple()
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
                    ])->columnSpan(1),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('A partir de')
                    ->money('BRL')
                    ->sortable(false),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Ativo'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->label('Criado em')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListProducts::route('/'), 'create' => Pages\CreateProduct::route('/create'), 'edit' => Pages\EditProduct::route('/{record}/edit')]; }
}