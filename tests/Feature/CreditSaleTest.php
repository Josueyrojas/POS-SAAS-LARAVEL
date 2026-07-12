<?php

namespace Tests\Feature;

use App\Enums\CashSessionStatus;
use App\Models\Business;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditSaleTest extends TestCase
{
    use RefreshDatabase;

    private function openSessionFor(User $user): void
    {
        CashSession::create([
            'business_id' => $user->business_id,
            'user_id' => $user->id,
            'status' => CashSessionStatus::OPEN->value,
            'opening_amount' => 0,
            'opening_at' => now(),
        ]);
    }

    public function test_credit_sale_requires_a_real_customer(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 50, 'stock' => 10]);
        $this->openSessionFor($user);

        $response = $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CREDIT',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_balance_decreases_when_a_payment_is_registered(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $customer = Customer::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 50, 'stock' => 10]);
        $this->openSessionFor($user);

        $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CREDIT',
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]], // $50 a crédito
        ]);

        $this->assertEquals(50, $customer->creditBalance());

        $this->actingAs($user)->post(route('pos.customer-payments.store', $customer->id), [
            'amount' => 20,
            'payment_method' => 'CASH',
        ]);

        $this->assertEquals(30, $customer->creditBalance());
    }
}
