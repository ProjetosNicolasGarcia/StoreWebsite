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
 * * Lógica Principal:
 * - Suporta Carrinho Persistente (Banco de Dados).
 * - Funciona para Visitantes (via Session ID) e Logados (via User ID).
 * - Gerencia estoque de Variantes (SKUs) e valida consistência de preços.
 */
class CartController extends Controller
{
    /**
     * Helper privado para buscar os itens do carrinho atual.
     * Centraliza a lógica de decisão entre "Usuário Logado" vs "Visitante".
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCartItems()
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        // Utiliza Eager Loading ('with') para carregar dados do Produto e Variante
        // Isso evita o problema de N+1 queries na renderização da view.
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

    /**
     * Exibe a página do carrinho e calcula o total dinâmico.
     */
    public function index()
    {
        $items = $this->getCartItems();

        // Lógica de Cálculo do Total:
        // 1. Prioridade Absoluta: Preço final da VARIANTE (se existir).
        // 2. Fallback: Preço de oferta ou base do produto pai (para itens sem variante/legados).
        $total = $items->sum(function ($item) {
            if ($item->variant) {
                return $item->quantity * $item->variant->final_price;
            }
            
            // Caso de borda: Item adicionado antes do sistema de variantes existir
            return $item->quantity * ($item->product->isOnSale() ? $item->product->sale_price : $item->product->base_price);
        });

        return view('shop.cart', compact('items', 'total'));
    }

    /**
     * Adiciona um item ao carrinho.
     * Contém as principais validações de segurança e estoque.
     */
    public function add(Request $request, $productId)
    {
        // 1. Validação Básica dos Inputs
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->input('quantity', 1);
        $variantId = $request->input('variant_id');

        // [SEGURANÇA 1] Integridade do Produto
        // Impede adicionar itens que foram desativados pelo administrador.
        $product = Product::findOrFail($productId);
        if (!$product->is_active) {
             return redirect()->back()->with('error', 'Este produto não está mais disponível.');
        }

        // 2. Verificação de Estoque da Variante
        $variant = ProductVariant::findOrFail($variantId);

        // [SEGURANÇA 2] Integridade da Variante (Anti-Fraude)
        // Garante que a variante enviada realmente pertence ao produto da URL.
        // Evita que um usuário mal-intencionado injete o ID de uma variante barata em um produto caro.
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

        if (Auth::check()) {
            $conditions['user_id'] = Auth::id();
        } else {
            $conditions['session_id'] = Session::getId();
        }

        // 4. Lógica de "Atualizar ou Criar" (Upsert)
        $item = CartItem::where($conditions)->first();

        if ($item) {
            // Se o item já existe, apenas incrementa a quantidade
            $item->increment('quantity', $quantity);
        } else {
            // Se é novo, cria o registro
            $data = array_merge($conditions, ['quantity' => $quantity]);
            CartItem::create($data);
        }

        // Redirecionamento condicional (ex: "Comprar Agora" vs "Adicionar ao Carrinho")
        if ($request->input('redirect_to_cart') === 'true') {
            return redirect()->route('cart.index');
        }

        return redirect()->back()->with('open_cart', true);
    }

    /**
     * Atualiza a quantidade de um item (+/-).
     * Revalida o estoque antes de incrementar.
     */
    public function update(Request $request, $id)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        // Busca item garantindo que pertence ao usuário/sessão atual (Segurança)
        $item = CartItem::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) $query->where('user_id', $userId);
                else $query->where('session_id', $sessionId);
            })->firstOrFail();

        if ($request->action === 'increase') {
            // Validação de Estoque em tempo real
            if ($item->variant && $item->quantity >= $item->variant->quantity) {
                return redirect()->back()->with('error', 'Máximo disponível em estoque atingido.');
            }
            $item->increment('quantity');
        } elseif ($request->action === 'decrease') {
            if ($item->quantity > 1) {
                $item->decrement('quantity');
            } else {
                // Se quantidade for 1 e diminuir, remove o item
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

        // Deleta garantindo o escopo do dono do carrinho
        CartItem::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) $query->where('user_id', $userId);
                else $query->where('session_id', $sessionId);
            })->delete();

        return redirect()->back()->with('open_cart', true);
    }
}