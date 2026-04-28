<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ShippingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage; // Necessário para correção das imagens na busca

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
                'variants', 
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
            // [CORREÇÃO DE BUG]: Removido o ->take(1) dentro do select de relacionamento. 
            // O Laravel não aplica o take(1) "por produto" no Eager Load, ele destrói os resultados da array geral.
            ->with(['categories' => fn($q) => $q->select('categories.id', 'categories.name', 'categories.slug')])
            ->latest()
            ->take(4)
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

    public function suggestions(Request $request)
    {
        $query = $request->input('q');

        if (!$query || strlen($query) < 2) return response()->json([]);

        $products = Product::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            ->with(['variants' => fn($q) => $this->variantFields($q)]) 
            ->get(['products.id', 'products.name', 'products.slug', 'products.image_url']); 

        $results = $products->map(function ($product) {
            $variant = $product->showcase_variant;
            
            if (!$variant) return null;

            $price = $variant->price;
            $salePrice = $variant->sale_price;
            $isOnSale = $product->isOnSale(); 

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                // [CORREÇÃO]: Frontend espera path válido. Adicionado Storage::url direto na API
                'image_url' => $product->image_url ? Storage::url($product->image_url) : asset('images/placeholder.jpg'),
                'price' => $isOnSale ? $salePrice : $price,
                'original_price' => $isOnSale ? $price : null,
                'on_sale' => $isOnSale
            ];
        })->filter();

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

