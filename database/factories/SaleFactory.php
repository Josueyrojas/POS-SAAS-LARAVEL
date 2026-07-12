<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Business;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'status' => SaleStatus::COMPLETED->value,
            'payment_method' => PaymentMethod::CASH->value,
            'items_subtotal' => 100,
            'total' => 100,
        ];
    }
}
