<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Ensure this is imported for caching
use App\Models\Product;
use App\Models\Banner;
use App\Models\Collection;

class HomeController extends Controller
{
    /**
     * Helper privado para otimização de consultas SQL.
     */
    private function variantFields($query)
    {
        $query->select([
            'id',
            'product_id',
            'price',
            'sale_price',
            'sale_start_date',
            'sale_end_date',
            'image',
            'images',
            'options',
            'quantity',
            'is_default'
        ]);
    }

    public function index()
    {
        // Define tempo de cache: 60 minutos (ajuste conforme a necessidade do negócio)
        $cacheTtl = now()->addMinutes(60);

        // 1. Carrossel Principal (Hero) - Cacheado
        $heroBanners = Cache::remember('home_hero_banners', $cacheTtl, function () {
            return Banner::where('is_active', true)
                ->where('location', 'hero')
                ->orderBy('position')
                ->get();
        });

        // 2. Seção de Novidades - Cacheado
        $newArrivals = Cache::remember('home_new_arrivals', $cacheTtl, function () {
            return Product::where('is_active', true)
                ->latest()
                ->take(8)
                ->with(['variants' => fn($q) => $this->variantFields($q)])
                ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
                ->get();
        });

        // 3. Coleções em Destaque - Cacheado
        $collections = Cache::remember('home_collections', $cacheTtl, function () {
            $cols = Collection::where('featured_on_home', true)
                ->where('is_active', true)
                ->with(['products' => function ($query) {
                    $query->where('is_active', true)
                          ->latest()
                          ->take(8) 
                          ->with(['variants' => fn($q) => $this->variantFields($q)])
                          ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)]);
                }])
                ->get();

            // Fallback de segurança para limitar a visualização
            $cols->each(function($collection) {
                $collection->setRelation('products', $collection->products->take(8));
            });

            return $cols;
        });

        return view('home', compact('heroBanners', 'newArrivals', 'collections'));
    }
}