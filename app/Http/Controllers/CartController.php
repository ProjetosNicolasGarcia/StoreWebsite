<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order; // ✅ novo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Controller responsável pelo gerenciamento do Carrinho de Compras.
 * Otimizado para evitar N+1 queries no menu lateral e checkout.
 */
class CartController extends Controller
{
    /**
     * Helper privado para otimização de consultas SQL.
     * Seleciona apenas as colunas essenciais das variantes.
     */
    private function variantFields($query)
    {
        $query->select([
            'id',
            'product_id',
            'price',
            'sale_price',
            'image',     // Importante para a miniatura no carrinho
            'images',    // Fallback de imagem
            'options',   // Para mostrar "Cor: Azul", "Tamanho: M"
            'quantity',  // Para validação de estoque visual
            'sku',       // ✅ novo: Adicionado para evitar MissingAttributeException
        ]);
    }

    /**
     * Helper privado para buscar os itens do carrinho atual.
     */
    private function getCartItems()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        return CartItem::with([
            'product' => function($q) {
                $q->select('*'); 
            },
            'product.categories' => function($q) {
                $q->select('categories.id', 'categories.name', 'categories.slug');
            },
            'variant' => fn($q) => $this->variantFields($q)
        ])
        ->where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->get();
    }

    /**
     * Exibe a página do carrinho (atualmente obsoleta conforme sua descrição).
     */
    public function index()
    {
        $items = $this->getCartItems();

        $total = $items->sum(function ($item) {
            if ($item->variant) {
                $price = $item->variant->sale_price ?? $item->variant->price;
                return $item->quantity * $price;
            }
            return $item->quantity * ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
        });

        return view('shop.cart', compact('items', 'total'));
    }

    /**
     * ✅ NOVO: Repete uma compra, adicionando itens ao carrinho e abrindo a sidebar.
     */
    public function reorder(Request $request, $id)
    {
        // Busca o pedido garantindo que pertença ao usuário logado
        $order = Auth::user()->orders()->with('items')->findOrFail($id);
        
        $sessionId = Session::getId();
        $userId = Auth::id();

        foreach ($order->items as $orderItem) {
            $conditions = [
                'product_id' => $orderItem->product_id,
                'product_variant_id' => $orderItem->product_variant_id,
            ];

            // Define se o vínculo é por usuário ou por sessão
            if ($userId) {
                $conditions['user_id'] = $userId;
            } else {
                $conditions['session_id'] = $sessionId;
            }

            // Upsert: Se o item já existe no carrinho, incrementa a quantidade. Se não, cria.
            $cartItem = CartItem::where($conditions)->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $orderItem->quantity);
            } else {
                CartItem::create(array_merge($conditions, ['quantity' => $orderItem->quantity]));
            }
        }

        // ✏️ ALTERADO: Retorna para a página de pedidos/detalhes e sinaliza abertura do carrinho lateral
        return back()->with([
            'success' => 'Itens adicionados ao seu carrinho!',
            'open_cart' => true // Essa chave é o que seu layout/sidebar usa para abrir automaticamente
        ]);
    }

    /**
     * Adiciona um item ao carrinho via página de produto.
     */
   /**
     * Adiciona um item ao carrinho via página de produto.
     */
    public function add(Request $request, $productId)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->input('quantity', 1);
        $variantId = $request->input('variant_id');

        // Validação do Produto
        $product = Product::findOrFail($productId);
        if (!$product->is_active) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Este produto não está mais disponível.'], 400);
            }
            return redirect()->back()->with('error', 'Este produto não está mais disponível.');
        }

        // Validação da Variante
        $variant = ProductVariant::findOrFail($variantId);
        if ($variant->product_id !== $product->id) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Inconsistência detectada: Variante inválida.'], 400);
            }
            abort(400, 'Inconsistência detectada: Variante inválida.');
        }

        // Validação de Estoque
        if ($variant->quantity < $quantity) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Estoque insuficiente para esta opção.'], 400);
            }
            return redirect()->back()->with('error', 'Estoque insuficiente.');
        }

        // Definição do Escopo (Usuário ou Sessão)
        $conditions = [
            'product_id' => $productId,
            'product_variant_id' => $variantId 
        ];

        if (Auth::check()) {
            $conditions['user_id'] = Auth::id();
        } else {
            $conditions['session_id'] = Session::getId();
        }

        // Lógica de Upsert
        $item = CartItem::where($conditions)->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            CartItem::create(array_merge($conditions, ['quantity' => $quantity]));
        }

        // ✅ CORREÇÃO: Restauro do Retorno Assíncrono (AJAX)
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Adicionado ao carrinho']);
        }

        // Retorno Síncrono Tradicional
        if ($request->input('redirect_to_cart') === 'true') {
            return redirect()->route('cart.index');
        }

        return redirect()->back()->with('open_cart', true);
    }
    /**
     * Atualiza a quantidade de um item (+/-).
     */
    public function update(Request $request, $id)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        $item = CartItem::where('id', $id)
            ->with('variant')
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) $query->where('user_id', $userId);
                else $query->where('session_id', $sessionId);
            })->firstOrFail();

        if ($request->action === 'increase') {
            if ($item->variant && $item->quantity >= $item->variant->quantity) {
                return redirect()->back()->with('error', 'Máximo disponível em estoque atingido.');
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

    /**
     * Remove um item do carrinho.
     */
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