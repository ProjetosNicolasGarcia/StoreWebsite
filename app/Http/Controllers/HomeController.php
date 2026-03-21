<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;
use App\Models\Banner;
use App\Models\Collection;

class HomeController extends Controller
{
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
        // Banners mudam pouco, podem ficar cacheados por 60 min
        $bannersCacheTtl = now()->addMinutes(60);
        
        // [MODIFICADO] Produtos e Ofertas precisam de dados "frescos". Cache curto de 2 minutos.
        $productsCacheTtl = now()->addMinutes(2);

        // 1. Carrossel Principal (Hero)
        $heroBanners = Cache::remember('home_hero_banners', $bannersCacheTtl, function () {
            return Banner::where('is_active', true)
                ->where('location', 'hero')
                ->orderBy('position')
                ->get();
        });

        // 2. Seção de Novidades
        $newArrivals = Cache::remember('home_new_arrivals', $productsCacheTtl, function () {
            return Product::where('is_active', true)
                ->latest()
                ->take(8)
                ->with(['variants' => fn($q) => $this->variantFields($q)])
                ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
                ->get();
        });

        // 3. Coleções em Destaque
        $collections = Cache::remember('home_collections', $productsCacheTtl, function () {
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

            $cols->each(function($collection) {
                $collection->setRelation('products', $collection->products->take(8));
            });

            return $cols;
        });

        return view('home', compact('heroBanners', 'newArrivals', 'collections'));
    }
}