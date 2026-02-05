<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderItem
 * * Representa um item específico contido em um Pedido (Order).
 * * Arquitetura: Funciona como um "Snapshot" (registro histórico).
 * * Diferente do CartItem, este model armazena o estado do produto no momento exato da compra 
 * (preço pago, impostos, descontos aplicados), garantindo a integridade financeira do histórico.
 *
 * @package App\Models
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * Configuração de Mass Assignment.
     * * Define que todos os campos são preenchíveis.
     * * Nota: Este model é populado apenas uma vez durante o fechamento do pedido (Checkout),
     * não devendo ser editado manualmente para evitar inconsistências no faturamento.
     */
    protected $guarded = [];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Relacionamento com o Pedido Pai.
     * * Vincula este item ao cabeçalho da transação.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relacionamento com o Produto de Origem.
     * * Utilidade: Permite que o administrador navegue do histórico de vendas para 
     * a página atual do produto no catálogo.
     * * Atenção: Se um produto for excluído (Soft Delete ou Hard Delete), este 
     * relacionamento pode retornar nulo, reforçando a necessidade de gravar 
     * dados críticos (nome, SKU, preço) diretamente na tabela 'order_items'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}