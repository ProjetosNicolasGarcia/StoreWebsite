<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

// Rota principal (Home)
Route::get('/', [HomeController::class, 'index'])->name('home');