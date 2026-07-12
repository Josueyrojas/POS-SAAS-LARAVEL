<?php

namespace Tests\Feature;

use App\Enums\CashSessionStatus;
use App\Models\Business;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function openSessionFor(User $user, float $opening = 100): CashSession
    {
        return CashSession::create([
            'business_id' => $user->business_id,
            'user_id' => $user->id,
            'status' => CashSessionStatus::OPEN->value,
            'opening_amount' => $opening,
            'opening_at' => now(),
        ]);
    }

    public function test_selling_requires_an_open_cash_session(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();

        $response = $this->actingAs($user)->get(route('pos.sales.create'));

        $response->assertRedirect(route('pos.cash-sessions.create'));
    }

    public function test_sale_deducts_stock_and_applies_retail_price(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 10, 'stock' => 100]);
        $this->openSessionFor($user);

        $response = $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CASH',
            'amount_tendered' => 50,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $response->assertRedirect();
        $product->refresh();
        $this->assertEquals(95, (float) $product->stock);

        $sale = $product->business->sales()->latest()->first();
        $this->assertEquals(50, (float) $sale->total);
        $this->assertEquals(0, (float) $sale->change_due);
    }

    public function test_wholesale_price_applies_when_quantity_reaches_minimum(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create([
            'retail_price' => 10, 'wholesale_price' => 7, 'wholesale_min_qty' => 10, 'stock' => 100,
        ]);
        $this->openSessionFor($user);

        $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CASH',
            'items' => [['product_id' => $product->id, 'quantity' => 10]],
        ]);

        $sale = $business->sales()->latest()->first();
        $this->assertEquals(70, (float) $sale->total); // 10 * 7, no 10 * 10
        $this->assertEquals('WHOLESALE', $sale->items->first()->price_type->value);
    }

    public function test_sale_rejects_insufficient_stock_without_touching_it(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 10, 'stock' => 3]);
        $this->openSessionFor($user);

        $response = $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CASH',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $response->assertStatus(422);
        $product->refresh();
        $this->assertEquals(3, (float) $product->stock); // sin cambios: la transacción se revirtió
    }

    public function test_discount_and_tax_are_computed_correctly(): void
    {
        $business = Business::factory()->create(['tax_rate' => 16]);
        $user = User::factory()->for($business)->create();
        $product = Product::factory()->for($business)->create(['retail_price' => 100, 'stock' => 100]);
        $this->openSessionFor($user);

        $this->actingAs($user)->post(route('pos.sales.store'), [
            'payment_method' => 'CASH',
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 2]], // subtotal 200
        ]);

        $sale = $business->sales()->latest()->first();
        $this->assertEquals(200, (float) $sale->items_subtotal);
        $this->assertEquals(20, (float) $sale->discount_amount); // 10% de 200
        $this->assertEquals(180, (float) $sale->total); // 200 - 20
        $this->assertEqualsWithDelta(24.83, (float) $sale->tax_amount, 0.01); // 180 - 180/1.16
    }
}
