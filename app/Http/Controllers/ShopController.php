<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\ShippingService;

/**
 * Controller responsável pela experiência de navegação do cliente na loja (Frontend).
 * Gerencia listagens (Categorias/Coleções), página de produto, busca e serviços auxiliares (Frete).
 */
class ShopController extends Controller
{
    /**
     * Helper privado para otimização de consultas SQL.
     * Seleciona apenas as colunas essenciais das variantes para exibição em listas (Vitrine).
     * Isso reduz drasticamente o consumo de memória ao carregar centenas de produtos.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation  $query
     * @return void
     */
    private function variantFields($query)
    {
        $query->select([
            'id',
            'product_id',
            'price',
            'sale_price',
            'sale_start_date',
            'sale_end_date',
            'image',     // Apenas a capa da variante
            'images',    // Galeria da variante (caso precise de hover)
            'options',   // JSON com atributos (Cor, Tamanho)
            'quantity',  // Para verificar estoque visualmente (esgotado)
            'is_default' // Essencial para definir qual variante mostrar primeiro
        ]);
    }

    /**
     * Exibe a página de listagem de uma Categoria.
     * Ex: /categoria/eletronicos
     */
    public function category($slug)
    {
        // Busca a categoria ou retorna 404 se não existir
        $item = Category::where('slug', $slug)->firstOrFail();

        $products = $item->products()
            ->where('is_active', true)
            // [PERFORMANCE] Eager Loading otimizado: carrega variantes filtrando colunas
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->get();

        return view('shop.listing', [
            'title' => $item->name,
            'description' => null, // Categorias geralmente não têm descrição longa na listagem
            'image_url' => $item->image_url, // Capa da categoria (se houver)
            'products' => $products
        ]);
    }

    /**
     * Exibe a página de listagem de uma Coleção.
     * Coleções podem ter produtos de várias categorias (Ex: "Dia dos Pais").
     */
    public function collection($slug)
    {
        // Garante que a coleção exista e esteja ativa
        $item = Collection::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $item->products()
            ->where('is_active', true)
            // [PERFORMANCE] Reutiliza a lógica de otimização de variantes
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->get();

        return view('shop.listing', [
            'title' => $item->title,
            'description' => $item->description,
            'image_url' => $item->image_url,
            'products' => $products
        ]);
    }

    /**
     * Página de Detalhes do Produto (PDP).
     * Lógica complexa que inclui: carregamento completo, deep linking de variante e recomendação de relacionados.
     */
    public function show($slug)
    {
        // Busca produto ativo pelo slug
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            // Carrega todos os relacionamentos necessários para a página completa
            ->with(['category', 'collections', 'reviews.user', 'variants'])
            ->firstOrFail();

        // [DEEP LINKING] Lógica para pré-selecionar uma variante via URL (ex: ?variant=123)
        // Útil quando o cliente clica em um anúncio de uma cor específica no Google/Facebook.
        $preSelectedVariant = null;
        if (request()->has('variant')) {
            $preSelectedVariant = $product->variants
                ->where('id', request()->query('variant'))
                ->first();
        }

        // [ALGORITMO DE RECOMENDAÇÃO] Busca produtos Relacionados
        // Critério: Mesma Categoria OU Mesma Coleção, excluindo o produto atual.
        $relatedProducts = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->where(function ($query) use ($product) {
                // 1. Tenta combinar por categoria
                if ($product->category_id) {
                    $query->orWhere('category_id', $product->category_id);
                }

                // 2. Tenta combinar por coleções compartilhadas
                if ($product->collections->isNotEmpty()) {
                    $collectionIds = $product->collections->pluck('id');
                    $query->orWhereHas('collections', function ($q) use ($collectionIds) {
                        $q->whereIn('collections.id', $collectionIds);
                    });
                }
            })
            // Otimização: Carrega variants leve para o card de produto relacionado
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->take(8) // Limita a 8 recomendações
            ->inRandomOrder() // Randomiza para dar frescor à página
            ->get();

        return view('shop.product', compact('product', 'relatedProducts', 'preSelectedVariant'));
    }

    /**
     * Processa a Busca Full-text.
     * Implementa lógica "E" (AND) entre termos: "Camiseta Azul" busca produtos que tenham "Camiseta" E "Azul".
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return redirect()->route('home');
        }

        // Explode a busca em termos para refinar os resultados
        $terms = explode(' ', $query);

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    // Para cada termo digitado, ele deve existir em ALGUM dos campos (Nome, Descrição ou Variante/SKU)
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhereHas('variants', function ($variantQ) use ($term) {
                                $variantQ->where('name', 'like', "%{$term}%")
                                         ->orWhere('sku', 'like', "%{$term}%");
                            });
                    });
                }
            })
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->get();

        return view('shop.listing', [
            'title' => "Resultados para: \"{$query}\"",
            'description' => null,
            'image_url' => null,
            'products' => $products
        ]);
    }

    /**
     * API Endpoint: Autocomplete / Sugestões de Busca.
     * Retorna JSON para ser consumido via AJAX/Alpine.js no frontend.
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $terms = explode(' ', $query);

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($terms) {
                // Reaproveita a mesma lógica robusta da busca principal
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhereHas('variants', function ($variantQ) use ($term) {
                                $variantQ->where('name', 'like', "%{$term}%")
                                         ->orWhere('sku', 'like', "%{$term}%");
                            });
                    });
                }
            })
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->take(5) // Limita a 5 sugestões para não poluir a UI
            ->get(['id', 'name', 'slug', 'image_url']); // Select otimizado, traz apenas o necessário para o dropdown

        // Formata o retorno para o padrão esperado pelo frontend
        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image_url' => $product->image_url,
                'base_price' => $product->base_price,
            ];
        });

        return response()->json($results);
    }

    /**
     * Página de Ofertas.
     * Utiliza um Scope do Model (onSaleQuery) para filtrar produtos em promoção.
     */
    public function offers()
    {
        $products = Product::where('is_active', true)
            ->onSaleQuery() // Scope definido no Model Product (provavelmente filtra datas e preços)
            ->with(['variants' => fn($q) => $this->variantFields($q)])
            ->latest()
            ->get();

        return view('shop.listing', [
            'products' => $products,
            'title' => 'Ofertas '
        ]);
    }

    /**
     * API Endpoint: Simulação de Frete na Página do Produto.
     * Recebe um CEP e ID do produto, retorna as opções de envio (SEDEX, PAC, etc).
     */
    public function simulateShipping(Request $request, ShippingService $shippingService)
    {
        $request->validate(['zip_code' => 'required|size:8']);

        if ($request->has('product_id')) {
            $product = Product::findOrFail($request->product_id);
            // Cria uma coleção para o serviço de frete (que geralmente espera múltiplos itens)
            $items = collect([$product]);
        } else {
            return response()->json(['error' => 'Nenhum produto selecionado'], 400);
        }

        // Delega o cálculo complexo para o Service dedicado
        $options = $shippingService->calculate($request->zip_code, $items);

        return response()->json($options);
    }
}