<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
    {
        // Bloqueia usuários não logados
        if (!auth()->check()) {
            return response()->json(['success' => false, 'error' => 'Não autenticado'], 401);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $user = auth()->user();
        
        // Verifica se já é favorito
        $favorite = $user->favorites()->where('product_id', $request->product_id)->first();

        // Se já existe, remove
        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'success' => true, 
                'message' => 'Removido dos favoritos.'
            ]);
        }

        // Se não existe, cria
        $user->favorites()->create(['product_id' => $request->product_id]);
        return response()->json([
            'success' => true, 
            'message' => 'Adicionado aos favoritos!'
        ]);
    }
}

