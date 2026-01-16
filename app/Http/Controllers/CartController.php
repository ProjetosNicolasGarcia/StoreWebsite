<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    // ... (Mantenha os métodos getCartItems e index como estão) ...
    private function getCartItems()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        return CartItem::with(['product'])
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->get();
    }

    public function index()
    {
        $items = $this->getCartItems();

        $total = $items->sum(function ($item) {
            $price = $item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price;
            return $item->quantity * $price;
        });

        return view('shop.cart', compact('items', 'total'));
    }

    public function add(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $quantity = $request->input('quantity', 1);

        $data = ['product_id' => $product->id];
        
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
            $conditions = ['user_id' => Auth::id(), 'product_id' => $product->id];
        } else {
            $data['session_id'] = Session::getId();
            $conditions = ['session_id' => Session::getId(), 'product_id' => $product->id];
        }

        $item = CartItem::where($conditions)->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $data['quantity'] = $quantity;
            CartItem::create($data);
        }

        // --- ALTERAÇÃO AQUI ---
        // Se o request vier com 'redirect_to_cart' verdadeiro, redireciona para o carrinho
        if ($request->input('redirect_to_cart') === 'true') {
            return redirect()->route('cart.index');
        }

        return redirect()->back()->with('open_cart', true);
    }

    // ... (Mantenha o restante dos métodos update e remove) ...
    public function update(Request $request, $id)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        $item = CartItem::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) $query->where('user_id', $userId);
                else $query->where('session_id', $sessionId);
            })->firstOrFail();

        if ($request->action === 'increase') {
            $item->increment('quantity');
        } elseif ($request->action === 'decrease') {
            if ($item->quantity > 1) {
                $item->decrement('quantity');
            } else {
                $item->delete();
            }
        }

        return redirect()->back()->with('open_cart', true);
    }

    public function remove($id)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        CartItem::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) $query->where('user_id', $userId);
                else $query->where('session_id', $sessionId);
            })->delete();

        return redirect()->back()->with('open_cart', true);
    }
}