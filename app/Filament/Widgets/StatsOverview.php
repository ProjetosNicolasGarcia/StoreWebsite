<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Banner;
use App\Models\Collection;
use App\Models\Category;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StatsOverview extends BaseWidget
{
    // Atualização em tempo real (polling)
    protected static ?string $pollingInterval = '30s';

    // Otimização: Carrega os dados de forma preguiçosa para não travar o painel
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        // 1. DADOS DE FATURAMENTO (Últimos 7 dias)
        $revenueData = Trend::model(Order::class)
            ->between(now()->subDays(7), now())
            ->perDay()
            ->sum('total_amount');

        $totalRevenue = Order::sum('total_amount');
        
        // 2. DADOS DE PEDIDOS (Últimos 7 dias)
        $ordersData = Trend::model(Order::class)
            ->between(now()->subDays(7), now())
            ->perDay()
            ->count();

        // 3. DADOS DE NOVOS CLIENTES (Últimos 7 dias)
        $usersData = Trend::model(User::class)
            ->between(now()->subDays(7), now())
            ->perDay()
            ->count();

        // --- CONTAGENS RÁPIDAS ---
        $activeProducts = Product::where('is_active', true)->count();
        $totalProducts = Product::count();
        
        $lowStock = ProductVariant::where('stock_quantity', '<', 5)->count();
        
        // Contagem de Conteúdo (Agrupado)
        $bannersCount = Banner::where('is_active', true)->count();
        $collectionsCount = Collection::count();
        $categoriesCount = Category::count();

        return [
            // === LINHA 1: FINANCEIRO E VENDAS ===
            
            Stat::make('Faturamento Total', 'R$ ' . number_format($totalRevenue, 2, ',', '.'))
                ->description('Vendas nos últimos 7 dias')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart(
                    $revenueData->map(fn (TrendValue $value) => $value->aggregate)->toArray()
                )
                ->color('success'), // Verde

            Stat::make('Pedidos Recentes', Order::count())
                ->description('Total acumulado')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart(
                    $ordersData->map(fn (TrendValue $value) => $value->aggregate)->toArray()
                )
                ->color('primary'), // Cor principal do tema

            Stat::make('Base de Clientes', User::count())
                ->description('Novos cadastros (7 dias)')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart(
                    $usersData->map(fn (TrendValue $value) => $value->aggregate)->toArray()
                )
                ->color('info'), // Azul claro

            // === LINHA 2: OPERACIONAL E CONTEÚDO ===

            Stat::make('Produtos na Loja', $activeProducts . ' / ' . $totalProducts)
                ->description('Produtos Ativos vs. Total')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'), // Amarelo/Laranja

            Stat::make('Alerta de Estoque', $lowStock)
                ->description('Variações com estoque < 5')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success'), // Vermelho se tiver problema

            // Widget Agregado (Para economizar espaço visual)
            Stat::make('Conteúdo Ativo', $bannersCount + $collectionsCount + $categoriesCount . ' Itens')
                ->description("{$bannersCount} Banners, {$collectionsCount} Coleções, {$categoriesCount} Categorias")
                ->descriptionIcon('heroicon-m-photo')
                ->color('gray'),
        ];
    }
}