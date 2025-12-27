<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Banner;

class HomeController extends Controller
{
    public function index()
    {
        // Busca os banners ativos ordenados pela posição
        $banners = Banner::where('is_active', true)
            ->orderBy('position')
            ->get();

        // Busca 8 produtos ativos para mostrar na vitrine
        $products = Product::where('is_active', true)
            ->take(8)
            ->get();

        return view('home', compact('banners', 'products'));
    }
}