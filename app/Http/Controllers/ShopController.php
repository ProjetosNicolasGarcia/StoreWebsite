<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
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
            'description' => null, // Categorias geralmente não tem descrição, mas se tiver, adicione aqui
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
}