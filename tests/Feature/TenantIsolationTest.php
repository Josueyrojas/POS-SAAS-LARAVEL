<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El aislamiento multi-tenant (BusinessScope + TenantContext) es el
 * mecanismo del que depende toda la app para no filtrar datos entre
 * negocios. Si esto se rompe, se rompe todo lo demás en silencio.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_only_sees_products_from_their_own_business(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        Product::factory()->for($businessA)->create(['name' => 'Producto de A']);
        Product::factory()->for($businessB)->create(['name' => 'Producto de B']);

        $employeeB = User::factory()->for($businessB)->create();

        $response = $this->actingAs($employeeB)->get(route('pos.products.index'));

        $response->assertOk();
        $response->assertSee('Producto de B');
        $response->assertDontSee('Producto de A');
    }

    public function test_user_cannot_view_a_sale_belonging_to_another_business(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $sellerA = User::factory()->for($businessA)->create();
        $sale = Sale::factory()->for($businessA)->create(['seller_id' => $sellerA->id]);

        $employeeB = User::factory()->for($businessB)->create();

        // El global scope filtra por business_id: para el usuario de B, esa
        // venta simplemente no existe -> 404, nunca un 200 con datos ajenos.
        $response = $this->actingAs($employeeB)->get(route('pos.sales.show', $sale->id));

        $response->assertNotFound();
    }
}
