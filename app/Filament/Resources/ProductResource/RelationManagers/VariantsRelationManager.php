<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Database\Eloquent\Collection;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variações e Estoque';
    
    protected static ?string $recordTitleAttribute = 'sku';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    KeyValue::make('options')
                        ->label('Opções (Ex: Cor: Azul, Tam: G)')
                        ->keyLabel('Atributo')
                        ->valueLabel('Valor')
                        ->columnSpanFull(),
                    
                    TextInput::make('sku')
                        ->label('SKU (Código Único)')
                        ->required(),
                    
                    Toggle::make('is_default')
                        ->label('Variante Principal?')
                        ->helperText('Define qual variação aparece primeiro na loja.')
                        ->inline(false)
                        ->onColor('success'),
                ])->columns(3)->columnSpanFull(),

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
                ])->columns(3)->columnSpanFull(),

                Group::make([
                    DateTimePicker::make('sale_start_date')->label('Início Oferta')->native(false)->seconds(false),
                    DateTimePicker::make('sale_end_date')->label('Fim Oferta')->native(false)->seconds(false)->after('sale_start_date'),
                ])->columns(2)->columnSpanFull(),

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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Capa'),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Preço Base')->money('BRL'),
                Tables\Columns\TextColumn::make('sale_price')->label('Preço Oferta')->money('BRL')->placeholder('-'),
                Tables\Columns\TextColumn::make('quantity')->label('Estoque')->sortable(),
                Tables\Columns\ToggleColumn::make('is_default')->label('Principal')->onColor('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Nova Variante'),

                Tables\Actions\Action::make('apply_promotion')
                    ->label('Aplicar Promoção Específica')
                    ->icon('heroicon-m-tag')
                    ->color('warning')
                    ->form([
                        Toggle::make('apply_to_all')
                            ->label('Aplicar a TODAS as variantes do produto?')
                            ->live()
                            ->default(false),

                        Select::make('target_attribute')
                            ->label('Atributo Alvo (Ex: Cor)')
                            ->options(function (\Livewire\Component $livewire) {
                                $variants = $livewire->ownerRecord->variants;
                                $attributes = [];
                                foreach ($variants as $variant) {
                                    if (is_array($variant->options)) {
                                        foreach (array_keys($variant->options) as $key) {
                                            $attributes[$key] = $key;
                                        }
                                    }
                                }
                                return $attributes;
                            })
                            ->live()
                            ->required(fn (\Filament\Forms\Get $get) => !$get('apply_to_all'))
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('apply_to_all')),

                        Select::make('target_value')
                            ->label('Valor do Atributo (Ex: Azul)')
                            ->options(function (\Livewire\Component $livewire, \Filament\Forms\Get $get) {
                                $attribute = $get('target_attribute');
                                if (!$attribute) return [];

                                $variants = $livewire->ownerRecord->variants;
                                $values = [];
                                foreach ($variants as $variant) {
                                    if (is_array($variant->options) && isset($variant->options[$attribute])) {
                                        $val = $variant->options[$attribute];
                                        $values[$val] = $val;
                                    }
                                }
                                return array_unique($values);
                            })
                            ->required(fn (\Filament\Forms\Get $get) => !$get('apply_to_all'))
                            ->hidden(fn (\Filament\Forms\Get $get) => $get('apply_to_all')),

                        Group::make([
                            TextInput::make('bulk_sale_price')
                                ->label('Novo Preço Promocional')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                        ])->columnSpanFull(),

                        Group::make([
                            DateTimePicker::make('bulk_start_date')->label('Início')->native(false),
                            DateTimePicker::make('bulk_end_date')->label('Fim')->native(false),
                        ])->columns(2),
                    ])
                    ->action(function (array $data, \Livewire\Component $livewire) {
                        $query = $livewire->getRelationship()->getQuery();

                        if (empty($data['apply_to_all'])) {
                            $attribute = $data['target_attribute'];
                            $value = $data['target_value'];
                            if ($attribute && $value) {
                                $query->whereJsonContains("options->{$attribute}", $value);
                            }
                        }

                        $updated = $query->update([
                            'sale_price' => $data['bulk_sale_price'],
                            'sale_start_date' => $data['bulk_start_date'],
                            'sale_end_date' => $data['bulk_end_date'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title("Sucesso")
                            ->body("Promoção aplicada a {$updated} variantes.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('apply_promotion_bulk')
                        ->label('Aplicar Promoção nas Selecionadas')
                        ->icon('heroicon-m-tag')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            TextInput::make('bulk_sale_price')
                                ->label('Novo Preço Promocional')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                            DateTimePicker::make('bulk_start_date')->label('Início')->native(false),
                            DateTimePicker::make('bulk_end_date')->label('Fim')->native(false),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'sale_price' => $data['bulk_sale_price'],
                                    'sale_start_date' => $data['bulk_start_date'],
                                    'sale_end_date' => $data['bulk_end_date'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}