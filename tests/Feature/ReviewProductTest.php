<?php
// tests/Feature/ReviewProductTest.php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test; // ✅ novo
use Tests\TestCase;

class ReviewProductTest extends TestCase
{
    use RefreshDatabase;

    #[Test] // ✏️ alterado: uso de Atributo do PHP 8+
    public function an_authenticated_user_can_review_a_delivered_product()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'delivered',
            'total_amount' => 100.00,
            'address_json' => ['street' => 'Rua Exemplo']
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00
        ]);

        $response = $this->actingAs($user)
            ->post(route('profile.product.review', $product->id), [
                'rating' => 5,
                'comment' => 'Excelente qualidade de construção.'
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Excelente qualidade de construção.'
        ]);
    }
}