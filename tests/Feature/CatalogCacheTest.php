<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Garante que o driver de cache para os testes suporte tags (Array ou Redis)
        config(['cache.default' => 'array']); 
    }

    /**
     * Valida se a alteração direta em um produto invalida as tags corretas.
     */
    public function test_product_update_flushes_catalog_cache(): void
    {
        $product = Product::factory()->create(['name' => 'Produto Original']);

        // Simula o cacheamento de uma query na aplicação
        Cache::tags(['catalog', 'products'])->put('home_products', 'dados_cacheados', 3600);

        // Confirma que o cache existe
        $this->assertEquals('dados_cacheados', Cache::tags(['catalog', 'products'])->get('home_products'));

        // Modifica o produto (Dispara o evento saved -> CatalogCacheObserver)
        $product->update(['name' => 'Produto Alterado']);

        // Assertiva factual: O cache DEVE ser nulo após o update
        $this->assertNull(Cache::tags(['catalog', 'products'])->get('home_products'));
    }

    /**
     * Valida criticamente a invalidação através da tabela pivô N:N.
     */
    public function test_pivot_sync_flushes_catalog_cache(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();

        // Injeta dados no cache simulando uma listagem de categorias
        Cache::tags(['catalog', 'category_product'])->put('category_listing', 'dados_cacheados', 3600);

        $this->assertEquals('dados_cacheados', Cache::tags(['catalog', 'category_product'])->get('category_listing'));

        // Executa a operação pivô (Anexa a categoria ao produto)
        $product->categories()->attach($category->id);

        // Assertiva factual: A operação na tabela pivô deve ter engatilhado a invalidação
        $this->assertNull(Cache::tags(['catalog', 'category_product'])->get('category_listing'));
    }

    /**
     * Valida se as rotas públicas respondem corretamente reconstruindo o cache.
     */
    public function test_public_routes_can_rebuild_cache_via_locks(): void
    {
        // Cria os pré-requisitos do banco para a página inicial não retornar vazio
        Product::factory()->count(3)->create(['is_active' => true]);

        // Limpa o cache para forçar o controlador a utilizar o lock e reconstruir
        Cache::flush();

        // Dispara requisição HTTP GET para a raiz
        $response = $this->get('/');

        // Verifica se a página renderizou sem erro de sintaxe 500
        $response->assertStatus(200);

        // Valida se a chave foi recriada mecanicamente na memória
        $this->assertNotNull(Cache::tags(['catalog', 'products'])->get('home_new_arrivals'));
    }
}