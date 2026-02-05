<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CartItem
 * * Representa um item individual no carrinho de compras persistido em banco.
 * * Arquitetura: Atua como o elo entre o Cliente e o Estoque (Variante).
 * * Diferencial: O item está vinculado estritamente a um SKU (Variante), 
 * não apenas ao produto genérico, garantindo integridade de preço e estoque.
 *
 * @package App\Models
 */
class CartItem extends Model
{
    /**
     * Configuração de Mass Assignment.
     * * Define que todos os campos podem ser preenchidos em massa.
     * * Segurança: Como não há $fillable explícito, a validação dos dados de entrada
     * (preço, quantidade) DEVE ser garantida rigorosamente no Controller/Service
     * antes de chamar o método create/update.
     */
    protected $guarded = [];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Relacionamento com o Produto Pai.
     * * * Finalidade: Camada de Apresentação.
     * Embora a venda seja técnica (SKU), precisamos do Pai para mostrar ao usuário:
     * 1. O Nome comercial do produto.
     * 2. O Slug para links de navegação.
     * 3. A Imagem de capa (caso a variante não tenha foto específica).
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relacionamento com a Variante Específica (SKU).
     * * * Finalidade: Regra de Negócio e Transação.
     * Este é o relacionamento "Financeiro/Logístico". É usado para:
     * 1. Recuperar o Preço Real no momento do checkout (evita fraude de preço no front).
     * 2. Verificar e Baixar o Estoque específico (ex: Tênis 40, não o genérico).
     * 3. Validar se a variação ainda está ativa/disponível.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        // Define a FK explícita 'product_variant_id' caso não siga a convenção padrão
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Relacionamento com o Proprietário do Carrinho.
     * * Útil para:
     * - Retomada de carrinho abandonado (via e-mail).
     * - Sincronização entre dispositivos (Login merge).
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}