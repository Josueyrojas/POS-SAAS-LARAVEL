<?php

namespace Tests\Feature;

use App\Enums\CashSessionStatus;
use App\Models\Business;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_computes_expected_cash_from_opening_plus_cash_sales(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 30, 'stock' => 100]);

        $session = CashSession::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'status' => CashSessionStatus::OPEN->value,
            'opening_amount' => 100,
            'opening_at' => now(),
        ]);

        $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CASH',
            'items' => [['product_id' => $product->id, 'quantity' => 2]], // $60 en efectivo
        ]);

        $response = $this->actingAs($user)->post(route('pos.cash-sessions.close'), [
            'closing_amount' => 155, // debería esperar 160 -> diferencia -5
        ]);

        $response->assertRedirect();
        $session->refresh();
        $this->assertEquals(CashSessionStatus::CLOSED, $session->status);
        $this->assertEquals(160, (float) $session->expected_amount); // 100 + 60
        $this->assertEquals(-5, (float) $session->difference);
    }
}
