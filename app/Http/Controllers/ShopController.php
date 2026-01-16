<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ShippingService; // [ADICIONADO] Importante para o cálculo

class ShopController extends Controller
{
    // Exibe produtos de uma Categoria
    public function category($slug)
    {
        $item = Category::where('slug', $slug)->firstOrFail();
        // Busca produtos ativos dessa categoria
        $products = $item->products()->where('is_active', true)->get();

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
        $products = $item->products()->where('is_active', true)->get();

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
            ->with(['category', 'collections', 'reviews.user'])
            ->firstOrFail();

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
            ->take(8)
            ->inRandomOrder()
            ->get();

        return view('shop.product', compact('product', 'relatedProducts'));
    }

    // --- BUSCA CORRIGIDA ---
    public function search(Request $request)
    {
        $query = $request->input('q');

        // Se a busca for vazia, retorna array vazio ou redireciona
        if (!$query) {
             return redirect()->route('home');
        }

        // Quebra a frase em palavras
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
            ->get();

        return view('shop.listing', [
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null,
            'products' => $products
        ]);
    }

    // --- SUGESTÕES (AUTOCOMPLETE) ---
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
            ->take(5)
            ->get(['id', 'name', 'slug', 'image_url', 'base_price']);

        return response()->json($products);
    }

    // ofertas //

    public function offers()
    {
        $products = Product::where('is_active', true)
            ->onSaleQuery() // Usa o filtro criado no Model
            ->latest()
            ->get(); // Ou ->paginate(12) se tiver muitos produtos

        return view('shop.listing', [
            'products' => $products,
            'title' => 'Ofertas '
        ]);
    }

    // [ADICIONADO] Simulação de Frete
    public function simulateShipping(Request $request, ShippingService $shippingService)
    {
        $request->validate(['zip_code' => 'required|size:8']); // Valida CEP sem traço
        
        // Cenário A: Simulando na página de um Produto Específico
        if ($request->has('product_id')) {
            $product = Product::findOrFail($request->product_id);
            // Simula uma coleção com 1 item (o produto) com quantidade 1
            // Usamos 'collect' para o ShippingService tratar igual a um carrinho
            $items = collect([$product]); 
        } 
        // Cenário B: Simulando para o Carrinho Inteiro (Futuro)
        else {
            return response()->json(['error' => 'Nenhum produto selecionado'], 400);
        }

        // Chama o serviço que criamos
        $options = $shippingService->calculate($request->zip_code, $items);

        return response()->json($options);
    }
}