<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
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
            // Carrega o produto
            'product' => function($q) {
                // Selecionamos campos vitais. 
                // Se removeu base_price/sale_price da tabela products, o Model usa accessors, 
                // então trazemos tudo ou os campos remanescentes. Por segurança, trazemos tudo do produto.
                $q->select('*'); 
            },
            // [OTIMIZAÇÃO CRÍTICA] Carrega a categoria para exibir no "badge" do item no menu lateral
            'product.categories' => function($q) {
                $q->select('categories.id', 'categories.name', 'categories.slug');
            },
            // Carrega a variante de forma otimizada
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
     * Exibe a página do carrinho e calcula o total dinâmico.
     */
    public function index()
    {
        $items = $this->getCartItems();

        // Lógica de Cálculo do Total
        $total = $items->sum(function ($item) {
            // Prioridade: Variante
            if ($item->variant) {
                // Tenta usar accessors do Model ProductVariant se existirem (ex: getFinalPriceAttribute)
                // Caso contrário, calcula manual
                $price = $item->variant->sale_price ?? $item->variant->price;
                return $item->quantity * $price;
            }
            
            // Fallback: Produto Pai (Item legado ou sem variante)
            // Usa os métodos do Model Product que você já configurou
            return $item->quantity * ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
        });

        return view('shop.cart', compact('items', 'total'));
    }

    /**
     * Adiciona um item ao carrinho.
     */
    public function add(Request $request, $productId)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->input('quantity', 1);
        $variantId = $request->input('variant_id');

        // [SEGURANÇA 1] Integridade do Produto
        $product = Product::findOrFail($productId);
        if (!$product->is_active) {
             return redirect()->back()->with('error', 'Este produto não está mais disponível.');
        }

        // 2. Verificação de Estoque da Variante
        $variant = ProductVariant::findOrFail($variantId);

        // [SEGURANÇA 2] Integridade da Variante (Anti-Fraude)
        if ($variant->product_id !== $product->id) {
            abort(400, 'Inconsistência detectada: Variante inválida para este produto.');
        }

        if ($variant->quantity < $quantity) {
            return redirect()->back()->with('error', 'Estoque insuficiente para esta opção.');
        }

        // 3. Definição do Escopo (Usuário ou Sessão)
        $conditions = [
            'product_id' => $productId,
            'product_variant_id' => $variantId 
        ];

        $attributes = [
            'user_id' => Auth::id(),
            'session_id' => Session::getId()
        ];

        // Se logado, usa ID. Se não, usa Sessão.
        if (Auth::check()) {
            unset($attributes['session_id']); // Limpa sessão se tiver user
            $conditions['user_id'] = Auth::id();
        } else {
            unset($attributes['user_id']);
            $conditions['session_id'] = Session::getId();
        }

        // 4. Lógica de "Atualizar ou Criar" (Upsert)
        $item = CartItem::where($conditions)->first();

        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            CartItem::create(array_merge($conditions, ['quantity' => $quantity]));
        }

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
            ->with('variant') // Carrega variante para checar estoque
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