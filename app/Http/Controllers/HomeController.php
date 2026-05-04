<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Importação OBRIGATÓRIA
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

    /**
     * Mitigação de Cache Stampede utilizando Atomic Locks.
     */
    private function rememberWithLock(array $tags, string $key, int $ttl, \Closure $callback)
    {
        $data = Cache::tags($tags)->get($key);
        
        if (is_null($data)) {
            // Se não houver cache, adquire o lock por 10s e faz as outras requisições esperarem até 5s.
            $data = Cache::lock('lock_' . $key, 10)->block(5, function () use ($tags, $key, $ttl, $callback) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            });
        }
        
        return $data;
    }

    public function index()
    {
        // 1. Carrossel Principal (Hero)
        $heroBanners = $this->rememberWithLock(
            ['catalog', 'banners'],
            'home_hero_banners',
            3600, // 1 hora de TTL
            function () {
                return Banner::where('is_active', true)
                    ->where('location', 'hero')
                    ->orderBy('position')
                    ->get();
            }
        );

        // 2. Seção de Novidades
        $newArrivals = $this->rememberWithLock(
            ['catalog', 'products'],
            'home_new_arrivals',
            3600,
            function () {
                return Product::where('is_active', true)
                    ->latest()
                    ->take(8)
                    ->with(['variants' => fn($q) => $this->variantFields($q)])
                    ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
                    ->get();
            }
        );

        // 3. Coleções em Destaque
        $collections = $this->rememberWithLock(
            ['catalog', 'collections', 'products'],
            'home_featured_collections',
            3600,
            function () {
                $cols = Collection::where('featured_on_home', true)
                    ->where('is_active', true)
                    ->with(['products' => function ($query) {
                        $query->where('is_active', true)
                              ->latest()
                              ->with(['variants' => fn($q) => $this->variantFields($q)])
                              ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)]);
                    }])
                    ->get();

                // Aplicação do limite in-memory nas coleções
                $cols->each(function($collection) {
                    $collection->setRelation('products', $collection->products->take(8));
                });

                return $cols;
            }
        );

        return view('home', compact('heroBanners', 'newArrivals', 'collections'));
    }
}