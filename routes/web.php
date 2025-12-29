<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// Rota da Página Inicial
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/search/suggestions', [ShopController::class, 'suggestions'])->name('shop.suggestions'); // Rota da API
Route::get('/search', [ShopController::class, 'search'])->name('shop.search');

// Rotas da Loja
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/collections/{slug}', [ShopController::class, 'collection'])->name('shop.collection');

// Rota do Produto (A que criamos agora)
Route::get('/product/{slug}', [ShopController::class, 'show'])->name('shop.product');

// --- Observação ---
// Removi as rotas de 'dashboard', 'profile' e o 'require auth.php' 
// pois elas dependem de pacotes de autenticação (Breeze/Jetstream) 
// que não estão instalados no seu projeto atual.