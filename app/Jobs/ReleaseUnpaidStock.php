<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ReleaseUnpaidStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        DB::transaction(function () {
            // Busca o pedido travando a linha para não conflitar com o Webhook
            $order = Order::where('id', $this->orderId)->lockForUpdate()->first();

            // Se o pedido não existir mais ou já estiver pago, ignora a Job
            if (!$order || $order->status !== Order::STATUS_PENDING) {
                return;
            }

            // O tempo expirou e ainda está pendente. Cancela o pedido!
            $order->update(['status' => 'canceled']);

            // DEVOLVE O ESTOQUE
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::where('id', $item->product_variant_id)
                        ->increment('quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)
                        ->increment('quantity', $item->quantity);
                }
            }
        });
    }
}