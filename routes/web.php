<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShopController;

// Rota principal (Home)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/collections/{slug}', [ShopController::class, 'collection'])->name('shop.collection');