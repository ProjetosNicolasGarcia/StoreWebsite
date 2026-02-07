<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        // 1. Carrossel Principal (Hero)
        $heroBanners = Banner::where('is_active', true)
            ->where('location', 'hero')
            ->orderBy('position')
            ->get();

        // 2. Seção de Novidades
        $newArrivals = Product::where('is_active', true)
            ->latest()
            ->take(8)
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            // CORREÇÃO: Usamos 'categories.' para evitar erro de ambiguidade SQL e pegamos apenas o necessário.
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->get();

        // 3. Coleções em Destaque
        $collections = Collection::where('featured_on_home', true)
            ->where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_active', true)
                      ->latest()
                      ->take(8) 
                      ->with(['variants' => fn($q) => $this->variantFields($q)])
                      // CORREÇÃO: Mesmo ajuste aqui para as categorias dentro das coleções
                      ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)]);
            }])
            ->get();

        // Fallback de segurança para limitar a visualização
        $collections->each(function($collection) {
            $collection->setRelation('products', $collection->products->take(8));
        });

        return view('home', compact('heroBanners', 'newArrivals', 'collections'));
    }
}