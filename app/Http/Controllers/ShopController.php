<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache; // Importação OBRIGATÓRIA
use App\Services\ShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
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
            $data = Cache::lock('lock_' . $key, 10)->block(5, function () use ($tags, $key, $ttl, $callback) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            });
        }
        
        return $data;
    }

    // ✏️ alterado: Apenas recupera a Categoria e passa para a View. A listagem de produtos agora é do Livewire.
    public function category($slug)
    {
        $category = $this->rememberWithLock(
            ['catalog', 'categories'],
            "category_entity_{$slug}",
            3600,
            fn() => Category::where('slug', $slug)->firstOrFail()
        );

        return view('shop.listing', [
            'category' => $category, // Passado para o Livewire consumir
            'title' => $category->name,
            'description' => null,
            'image_url' => $category->image_url,
        ]);
    }

    // ✏️ alterado: Apenas recupera a Coleção e passa para a View. 
    public function collection($slug)
    {
        $collection = $this->rememberWithLock(
            ['catalog', 'collections'],
            "collection_entity_{$slug}",
            3600,
            fn() => Collection::where('slug', $slug)->where('is_active', true)->firstOrFail()
        );

        return view('shop.listing', [
            'collection' => $collection, // Passado para o Livewire consumir
            'title' => $collection->title,
            'description' => $collection->description,
            'image_url' => $collection->image_url,
        ]);
    }

    // Mantido original (apenas a exibição individual não usa os filtros reativos)
    public function show($slug)
    {
        $product = $this->rememberWithLock(
            ['catalog', 'products'],
            "product_show_{$slug}",
            3600,
            fn() => Product::where('slug', $slug)
                ->where('is_active', true)
                ->with(['categories', 'collections', 'variants', 'reviews.user'])
                ->firstOrFail()
        );

        $preSelectedVariant = null;
        if (request()->has('variant')) {
            $preSelectedVariant = $product->variants
                ->where('id', request()->query('variant'))
                ->first();
        }

        $relatedProducts = $this->rememberWithLock(
            ['catalog', 'products'],
            "product_related_{$slug}",
            3600,
            function () use ($product) {
                return Product::where('is_active', true)
                    ->where('id', '!=', $product->id)
                    ->where(function (Builder $query) use ($product) {
                        $categoryIds = $product->categories->pluck('id');
                        if ($categoryIds->isNotEmpty()) {
                            $query->orWhereHas('categories', function ($q) use ($categoryIds) {
                                $q->whereIn('categories.id', $categoryIds); 
                            });
                        }
                        $collectionIds = $product->collections->pluck('id');
                        if ($collectionIds->isNotEmpty()) {
                            $query->orWhereHas('collections', function ($q) use ($collectionIds) {
                                $q->whereIn('collections.id', $collectionIds);
                            });
                        }
                    })
                    ->with(['variants' => fn($q) => $this->variantFields($q)])
                    ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')])
                    ->latest()
                    ->take(4)
                    ->get();
            }
        );

        return view('shop.product', compact('product', 'relatedProducts', 'preSelectedVariant'));
    }

    // ✏️ alterado: Redireciona a query string do navbar para a tela do Livewire
    public function search(Request $request)
    {
        $query = $request->input('q');
        if (!$query) return redirect()->route('home');
        
        return view('shop.listing', [
            'searchQuery' => $query, // Passado para o Livewire consumir
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null,
        ]);
    }

    // Mantido original para o dropdown ajax de auto-complete
    public function suggestions(Request $request)
    {
        $query = $request->input('q');
        if (!$query || strlen($query) < 2) return response()->json([]);

        $searchHash = md5($query);

        $results = $this->rememberWithLock(
            ['catalog', 'products'],
            "search_suggestions_{$searchHash}",
            60, // TTL curto
            function () use ($query) {
                $products = Product::where('is_active', true)
                    ->where('name', 'like', "%{$query}%")
                    ->take(5)
                    ->with(['variants' => fn($q) => $this->variantFields($q)]) 
                    ->get(['products.id', 'products.name', 'products.slug', 'products.image_url']); 

                return $products->map(function ($product) {
                    $variant = $product->showcase_variant;
                    
                    if (!$variant) return null;

                    $price = $variant->price;
                    $salePrice = $variant->sale_price;
                    $isOnSale = $product->isOnSale(); 

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'image_url' => $product->image_url ? Storage::url($product->image_url) : asset('images/placeholder.jpg'),
                        'price' => $isOnSale ? $salePrice : $price,
                        'original_price' => $isOnSale ? $price : null,
                        'on_sale' => $isOnSale
                    ];
                })->filter()->values();
            }
        );

        return response()->json($results);
    }

    // ✏️ alterado: Passa a flag de ofertas para o Livewire filtrar dinamicamente
    public function offers()
    {
        return view('shop.listing', [
            'isOffers' => true, // Passado para o Livewire saber o escopo
            'title' => 'Ofertas Especiais',
            'description' => 'Aproveite nossos descontos por tempo limitado.',
            'image_url' => null
        ]);
    }

    // Mantido original
    public function simulateShipping(Request $request, ShippingService $shippingService)
    {
        // O frete depende de API externa (Correios/Transportadora), logo, NÃO deve ser feito cache em disco por hora, 
        // a menos que desejado com chaves baseadas em CEP. Foi mantido inalterado.
        $request->validate([
            'zip_code' => 'required|string|min:8|max:9',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $items = collect([$product]);
            $options = $shippingService->calculate($request->zip_code, $items);
            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao calcular frete: ' . $e->getMessage()], 400);
        }
    }
}