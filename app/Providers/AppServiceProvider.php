<?php

namespace App\Providers;

use Illuminate\Support\Facades\View; // [Novo] Importação necessária para manipular views
use App\Models\Category; // [Novo] Importação do seu modelo de Categoria
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [Novo] Configura um "View Composer".
        // Toda vez que o arquivo 'resources/views/components/layout.blade.php' for renderizado,
        // o Laravel vai buscar todas as categorias no banco e injetar na variável $globalCategories.
        View::composer('components.layout', function ($view) {
            $view->with('globalCategories', Category::all());
        });
    }
}