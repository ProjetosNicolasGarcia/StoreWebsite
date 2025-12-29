<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;

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

        // Busca produtos ativos dessa coleção
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
        // Busca o produto e carrega as relações
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'collections', 'reviews.user'])
            ->firstOrFail();

        // Lógica para Sugestões de Produtos
        $relatedProducts = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                // 1. Pela Categoria
                if ($product->category_id) {
                    $query->orWhere('category_id', $product->category_id);
                }
                
                // 2. Pelas Coleções
                // Verifica se o produto tem coleções antes de tentar buscar
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

    public function search(Request $request)
    {
        $query = $request->input('q'); // Pega o termo digitado

        // Busca produtos ativos que tenham o termo no nome OU na descrição
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->get();

        return view('shop.listing', [
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null, // Opcional: colocar uma imagem padrão de busca
            'products' => $products
        ]);
    }

    // Método para Sugestões em Tempo Real (API)
   public function suggestions(Request $request)
    {
        $query = $request->input('q');

        // ALTERAÇÃO AQUI: Mudamos de < 2 para < 1 (ou ! $query)
        // Isso permite pesquisar "4", "X", "P", etc.
        if (! $query) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->where(function($q) use ($query) {
                // Adicionei busca na descrição também para garantir
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'slug', 'image_url', 'base_price']);

        return response()->json($products);
    }
}