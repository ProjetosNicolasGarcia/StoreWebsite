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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // Nova ação em massa SUPER OTIMIZADA
                    Tables\Actions\BulkAction::make('apply_promotion')
                        ->label('Aplicar Promoção')
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
                            // Atualiza diretamente no banco de dados as linhas selecionadas na tabela
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