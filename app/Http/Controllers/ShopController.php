<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ShippingService;

class ShopController extends Controller
{
    // Exibe produtos de uma Categoria
    public function category($slug)
    {
        $item = Category::where('slug', $slug)->firstOrFail();
        
        $products = $item->products()
            ->where('is_active', true)
            ->with('variants') // Carrega variantes para o preço correto
            ->get();

        return view('shop.listing', [
            'title' => $item->name,
            'description' => null,
            'image_url' => $item->image_url,
            'products' => $products
        ]);
    }

    // Exibe produtos de uma Coleção
    public function collection($slug)
    {
        $item = Collection::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        $products = $item->products()
            ->where('is_active', true)
            ->with('variants')
            ->get();

        return view('shop.listing', [
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $item->image_url,
            'products' => $products
        ]);
    }

    // Exibe a página de um Produto Específico
 public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'collections', 'reviews.user', 'variants'])
            ->firstOrFail();

        // --- CORREÇÃO AQUI ---
        // Verifica se existe um ID de variante na URL (ex: ?variant=5)
        $preSelectedVariant = null;
        if (request()->has('variant')) {
            // [CORREÇÃO]: Usamos where('id', ...) para garantir a busca pelo ID real do banco
            // ao invés do índice da coleção em memória.
            $preSelectedVariant = $product->variants->where('id', request()->query('variant'))->first();
        }
        // ---------------------

        $relatedProducts = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                if ($product->category_id) {
                    $query->orWhere('category_id', $product->category_id);
                }
                
                if ($product->collections->isNotEmpty()) {
                    $collectionIds = $product->collections->pluck('id');
                    $query->orWhereHas('collections', function ($q) use ($collectionIds) {
                        $q->whereIn('collections.id', $collectionIds);
                    });
                }
            })
            ->with('variants')
            ->take(8)
            ->inRandomOrder()
            ->get();

        // Passamos a variável $preSelectedVariant para a view
        return view('shop.product', compact('product', 'relatedProducts', 'preSelectedVariant'));
    }
    
    // Busca
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
             return redirect()->route('home');
        }

        $terms = explode(' ', $query);

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('name', 'like', "%{$term}%")
                             ->orWhere('description', 'like', "%{$term}%")
                             ->orWhereHas('variants', function ($variantQ) use ($term) {
                                 $variantQ->where('name', 'like', "%{$term}%")
                                          ->orWhere('sku', 'like', "%{$term}%");
                             });
                    });
                }
            })
            ->with('variants')
            ->get();

        return view('shop.listing', [
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null,
            'products' => $products
        ]);
    }

    // Sugestões (Autocomplete)
    public function suggestions(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $terms = explode(' ', $query);

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('name', 'like', "%{$term}%")
                             ->orWhere('description', 'like', "%{$term}%")
                             ->orWhereHas('variants', function ($variantQ) use ($term) {
                                 $variantQ->where('name', 'like', "%{$term}%")
                                          ->orWhere('sku', 'like', "%{$term}%");
                             });
                    });
                }
            })
            ->with('variants')
            ->take(5)
            ->get(['id', 'name', 'slug', 'image_url']);

        // Mapeia para adicionar o preço calculado (Accessor)
        $results = $products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image_url' => $product->image_url,
                'base_price' => $product->base_price, 
            ];
        });

        return response()->json($results);
    }

    // Ofertas
    public function offers()
    {
        $products = Product::where('is_active', true)
            ->onSaleQuery()
            ->with('variants')
            ->latest()
            ->get();

        return view('shop.listing', [
            'products' => $products,
            'title' => 'Ofertas '
        ]);
    }

    // Simulação de Frete
    public function simulateShipping(Request $request, ShippingService $shippingService)
    {
        $request->validate(['zip_code' => 'required|size:8']);
        
        if ($request->has('product_id')) {
            $product = Product::findOrFail($request->product_id);
            $items = collect([$product]); 
        } 
        else {
            return response()->json(['error' => 'Nenhum produto selecionado'], 400);
        }

        $options = $shippingService->calculate($request->zip_code, $items);

        return response()->json($options);
    }
}