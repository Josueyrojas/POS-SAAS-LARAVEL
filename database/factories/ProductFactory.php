<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->unique()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            // 'Pieza' se siembra directamente en la migración de units_of_measure,
            // existe siempre tras `migrate` sin depender del seeder de demo.
            'unit_of_measure_id' => fn () => UnitOfMeasure::where('name', 'Pieza')->firstOrFail()->id,
            'retail_price' => fake()->randomFloat(2, 10, 500),
            'stock' => 100,
            'stock_minimo' => 5,
            'is_active' => true,
        ];
    }
}
