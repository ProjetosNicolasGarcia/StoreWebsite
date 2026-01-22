<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant; // Importante
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    private function getCartItems()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        // Carrega 'product' E 'variant' para usar na tela
        return CartItem::with(['product', 'variant'])
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

        // Calcula o total considerando o preço da VARIANTE
        $total = $items->sum(function ($item) {
            if ($item->variant) {
                return $item->quantity * $item->variant->final_price;
            }
            // Fallback: se não tiver variante (carrinho antigo), usa preço do produto
            return $item->quantity * ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
        });

        return view('shop.cart', compact('items', 'total'));
    }

    public function add(Request $request, $productId)
    {
        // 1. Validação: Obriga a ter um variant_id válido
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->input('quantity', 1);
        $variantId = $request->input('variant_id');

        // [ROBUSTEZ 1] Verifica se o produto existe e está ativo
        // Impede a compra de itens desativados pelo painel admin
        $product = Product::findOrFail($productId);
        if (!$product->is_active) {
             return redirect()->back()->with('error', 'Este produto não está mais disponível.');
        }

        // 2. Verifica estoque da VARIANTE específica
        $variant = ProductVariant::findOrFail($variantId);

        // [ROBUSTEZ 2] Segurança: Garante que a variante pertence ao produto da URL
        // Evita manipulação de formulário (ex: comprar variante barata em produto caro)
        if ($variant->product_id !== $product->id) {
            abort(400, 'Variante inválida para este produto.');
        }

        if ($variant->quantity < $quantity) {
            return redirect()->back()->with('error', 'Estoque insuficiente para esta opção.');
        }

        // 3. Prepara dados de busca (agora buscamos pela VARIANTE)
        $conditions = [
            'product_id' => $productId,
            'product_variant_id' => $variantId 
        ];

        if (Auth::check()) {
            $conditions['user_id'] = Auth::id();
        } else {
            $conditions['session_id'] = Session::getId();
        }

        // 4. Busca ou Cria
        $item = CartItem::where($conditions)->first();

        if ($item) {
            // Se já existe essa variante no carrinho, aumenta a quantidade
            $item->increment('quantity', $quantity);
        } else {
            // Se não, cria um novo item salvando a variante
            $data = array_merge($conditions, ['quantity' => $quantity]);
            CartItem::create($data);
        }

        if ($request->input('redirect_to_cart') === 'true') {
            return redirect()->route('cart.index');
        }

        return redirect()->back()->with('open_cart', true);
    }

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
            // Verifica estoque da variante antes de aumentar
            if ($item->variant && $item->quantity >= $item->variant->quantity) {
                return redirect()->back()->with('error', 'Máximo disponível em estoque.');
            }
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