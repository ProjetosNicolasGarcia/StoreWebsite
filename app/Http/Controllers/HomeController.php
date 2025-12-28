<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Banner;

class HomeController extends Controller
{
    public function index()
{
    // 1. Carrossel: Busca apenas banners marcados como 'hero'
    $heroBanners = Banner::where('is_active', true)
        ->where('location', 'hero')
        ->orderBy('position')
        ->get();

    // 2. Novidades: Os últimos 8 produtos criados
    $newArrivals = Product::where('is_active', true)
        ->latest() // Ordena por data de criação
        ->take(8)
        ->get();

    // 3. Coleções: Busca coleções marcadas para a home e carrega 4 produtos delas
    // ... dentro do index()
$collections = \App\Models\Collection::where('featured_on_home', true)
    ->where('is_active', true)
    ->with(['products' => function($query) {
        $query->where('is_active', true)->take(8); // <--- ALTERADO PARA 8
    }])
    ->get();

    return view('home', compact('heroBanners', 'newArrivals', 'collections'));
}
}