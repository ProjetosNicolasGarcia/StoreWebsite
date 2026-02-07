<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ShippingService;
use Illuminate\Database\Eloquent\Builder;

class ShopController extends Controller
{
    /**
     * OTIMIZAÇÃO: Define colunas essenciais para performance.
     * As datas são obrigatórias para validar se a promoção está ativa.
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

    public function category($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $products = $category->products()
            ->where('is_active', true)
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->latest()
            ->paginate(12);

        return view('shop.listing', [
            'title' => $category->name,
            'description' => null,
            'image_url' => $category->image_url,
            'products' => $products
        ]);
    }

    public function collection($slug)
    {
        $collection = Collection::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $collection->products()
            ->where('is_active', true)
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->latest()
            ->paginate(12);

        return view('shop.listing', [
            'title' => $collection->title,
            'description' => $collection->description,
            'image_url' => $collection->image_url,
            'products' => $products
        ]);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'categories', 
                'collections', 
                'variants', // Carrega variantes completas na PDP para garantir todos os dados
                'reviews.user' 
            ])
            ->firstOrFail();

        $preSelectedVariant = null;
        if (request()->has('variant')) {
            $preSelectedVariant = $product->variants
                ->where('id', request()->query('variant'))
                ->first();
        }

        $relatedProducts = Product::where('is_active', true)
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
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->take(4)
            ->inRandomOrder()
            ->get();

        return view('shop.product', compact('product', 'relatedProducts', 'preSelectedVariant'));
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) return redirect()->route('home');

        $terms = explode(' ', $query);

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('products.name', 'like', "%{$term}%")
                             ->orWhere('products.description', 'like', "%{$term}%")
                             ->orWhereHas('variants', function ($variantQ) use ($term) {
                                 $variantQ->where('sku', 'like', "%{$term}%"); 
                             });
                    });
                }
            })
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->paginate(20);

        return view('shop.listing', [
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null,
            'products' => $products
        ]);
    }

    /**
     * CORREÇÃO: Sugestões agora usam a lógica inteligente de vitrine.
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q');

        if (!$query || strlen($query) < 2) return response()->json([]);

        $products = Product::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            // Carregamos TODAS as colunas necessárias para calcular a promoção corretamente
            ->with(['variants' => fn($q) => $this->variantFields($q)]) 
            ->get(['products.id', 'products.name', 'products.slug', 'products.image_url']); 

        $results = $products->map(function ($product) {
            // [CORREÇÃO] Em vez de pegar a primeira, pega a MELHOR variante (com promoção)
            // O atributo showcase_variant no Model Product já faz essa escolha inteligente.
            $variant = $product->showcase_variant;
            
            // Fallback se não tiver nenhuma variante
            if (!$variant) return null;

            $price = $variant->price;
            $salePrice = $variant->sale_price;
            $isOnSale = $product->isOnSale(); // Usa a lógica robusta de data do Model

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image_url' => $product->image_url ?? 'images/placeholder.jpg',
                'price' => $isOnSale ? $salePrice : $price,
                'original_price' => $isOnSale ? $price : null,
                'on_sale' => $isOnSale
            ];
        })->filter(); // Remove nulos

        return response()->json($results->values());
    }

    public function offers()
    {
        $products = Product::onSaleQuery()
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')->take(1)])
            ->latest()
            ->paginate(12);

        return view('shop.listing', [
            'products' => $products,
            'title' => 'Ofertas Especiais',
            'description' => 'Aproveite nossos descontos por tempo limitado.',
            'image_url' => null
        ]);
    }

    public function simulateShipping(Request $request, ShippingService $shippingService)
    {
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