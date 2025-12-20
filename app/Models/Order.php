<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Converte o JSON do endereço automaticamente para Array (facilita leitura)
    protected $casts = [
        'address_json' => 'array',
    ];

    // Pedido pertence a um Usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Pedido tem muitos Itens
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}