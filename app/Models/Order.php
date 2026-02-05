<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 * * Representa a entidade de fechamento de venda (Pedido).
 * * Diferente do Carrinho, o Pedido é um registro histórico e imutável.
 * * Arquitetura: Centraliza o status do ciclo de vida da venda (Pagamento, Envio, Conclusão).
 *
 * @package App\Models
 */
class Order extends Model
{
    use HasFactory;

    /**
     * Configuração de Mass Assignment.
     * * Define que todos os campos são preenchíveis.
     * * Nota: Campos sensíveis como 'status' e 'total_amount' devem ser controlados
     * por métodos específicos ou políticas de acesso (Gates/Policies) no Controller.
     */
    protected $guarded = [];

    /**
     * Conversão de tipos (Casting).
     * * 'address_json': Armazena o endereço completo no momento da compra.
     * MOTIVO: Se o usuário alterar o endereço no perfil meses depois, o registro 
     * deste pedido específico deve permanecer intacto para fins logísticos e fiscais.
     */
    protected $casts = [
        'address_json' => 'array',
    ];

    // =========================================================================
    // RELACIONAMENTOS (Eloquent Relations)
    // =========================================================================

    /**
     * Relacionamento com o Cliente.
     * * Permite identificar o proprietário do pedido e vincular ao histórico de compras.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com os Itens do Pedido.
     * * Diferente do 'CartItem', os 'OrderItems' contêm o snapshot do preço 
     * no momento exato da finalização da compra.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}