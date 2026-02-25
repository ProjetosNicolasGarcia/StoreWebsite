<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    // Ícone de ticket combina bem com cupons
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    
    // Agrupando no menu lateral (ajuste conforme o nome do seu grupo atual)
    protected static ?string $navigationGroup = 'Loja';
    
    protected static ?string $modelLabel = 'Cupom';
    protected static ?string $pluralModelLabel = 'Cupons';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Informações Principais')->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código do Cupom')
                            ->required()
                            ->unique(ignoreRecord: true)
                            // Força o usuário a digitar/ver em maiúsculo no painel
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('type')
                            ->label('Tipo de Desconto')
                            ->options([
                                'percentage' => 'Porcentagem (%)',
                                'fixed' => 'Valor Fixo (R$)',
                            ])
                            ->required()
                            ->default('percentage')
                            ->live(), // Útil se quiser mudar a label do campo "Valor" dinamicamente no futuro
                        
                        Forms\Components\TextInput::make('value')
                            ->label('Valor do Desconto')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                    ])->columns(2),

                    Forms\Components\Section::make('Regras e Restrições')->schema([
                        Forms\Components\TextInput::make('min_cart_value')
                            ->label('Valor Mínimo do Carrinho (R$)')
                            ->numeric()
                            ->nullable()
                            ->prefix('R$'),
                        
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Limite Total de Usos')
                            ->numeric()
                            ->nullable()
                            ->helperText('Deixe em branco para usos ilimitados.'),
                            
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Válido a partir de')
                            ->native(false), // Usa o datepicker do próprio Filament
                            
                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Válido até')
                            ->native(false),
                    ])->columns(2),
                ])->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela

                Forms\Components\Group::make()->schema([
                    Forms\Components\Section::make('Status')->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Cupom Ativo')
                            ->default(true)
                            ->helperText('Desative para pausar o cupom imediatamente.'),
                        
                        // Placeholder apenas para exibir a informação, não é editável diretamente
                        Forms\Components\Placeholder::make('used_count')
                            ->label('Vezes Utilizado')
                            ->content(fn (?Coupon $record): string => $record ? number_format($record->used_count, 0, '', '.') : '0'),
                    ]),
                ])->columnSpan(['lg' => 1]), // Ocupa 1/3 da tela
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Porcentagem',
                        'fixed' => 'Valor Fixo',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'info',
                        'fixed' => 'success',
                    }),
                    
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage' 
                        ? $record->value . '%' 
                        : 'R$ ' . number_format($record->value, 2, ',', '.'))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Usos')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Validade')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(), // Permite ocultar a coluna na tabela
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status do Cupom')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            // Podemos adicionar relacionamentos depois, se necessário
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}