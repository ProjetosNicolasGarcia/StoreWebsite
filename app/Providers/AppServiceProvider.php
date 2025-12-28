<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Importar View
use App\Models\Category; // Importar Model de Categoria

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
        // Compartilha a variável $globalCategories com todas as Views do site.
        // Isso garante que o menu sempre terá as categorias, não importa em qual página você esteja.
        // Se você tiver categorias "Pai" (parent_id null), pode filtrar aqui: Category::whereNull('parent_id')->get()
        if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
             View::share('globalCategories', Category::all());
        }
    }
}