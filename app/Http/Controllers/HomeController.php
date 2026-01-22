<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Collection;

/**
 * Controller responsável pela Página Inicial (Home) da loja.
 * Agrega os principais blocos de conteúdo: Banners, Novidades e Coleções em Destaque.
 */
class HomeController extends Controller
{
    /**
     * Renderiza a vitrine principal.
     */
    public function index()
    {
        // 1. Carrossel Principal (Hero)
        // Busca apenas banners ativos, marcados para o topo ('hero') e respeita a ordem definida no admin.
        $heroBanners = Banner::where('is_active', true)
            ->where('location', 'hero')
            ->orderBy('position')
            ->get();

        // 2. Seção de Novidades
        // Exibe os últimos 8 produtos cadastrados que estão ativos.
        $newArrivals = Product::where('is_active', true)
            ->latest() // Atalho do Laravel para 'orderBy created_at desc'
            ->take(8)
            ->get();

        // 3. Coleções em Destaque
        // Busca coleções marcadas para a home e carrega seus produtos (Eager Loading)
        // Aplica um filtro (Closure) para trazer apenas 8 produtos ativos por coleção.
        $collections = Collection::where('featured_on_home', true)
            ->where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_active', true)
                      ->take(8); // Limita a 8 produtos para não poluir o layout
            }])
            ->get();

        return view('home', compact('heroBanners', 'newArrivals', 'collections'));
    }
}