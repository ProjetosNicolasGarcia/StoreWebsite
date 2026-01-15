<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\ProductVariant;

class StatsOverview extends BaseWidget
{
    // Atualiza a cada 15 segundos
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        return [
            // 1. Total de Pedidos
            Stat::make('Total de Pedidos', Order::count())
                ->description('Vendas realizadas')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            // 2. Faturamento
            Stat::make('Faturamento', 'R$ ' . number_format(Order::sum('total_amount'), 2, ',', '.'))
                ->description('Receita bruta')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            // 3. Alerta de Estoque
            Stat::make('Estoque Baixo', ProductVariant::where('stock_quantity', '<', 5)->count())
                ->description('Produtos acabando')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}