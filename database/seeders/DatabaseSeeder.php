<?php

namespace Database\Seeders;

use App\Enums\BusinessPlan;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin de plataforma (business_id null).
        // No usamos updateOrCreate por email global: garantizamos unicidad a mano
        // porque con business_id NULL el UNIQUE compuesto no aplica en Postgres.
        if (! User::where('email', 'root@platform.dev')->where('role', UserRole::SUPER_ADMIN->value)->exists()) {
            User::create([
                'name' => 'Platform Root',
                'email' => 'root@platform.dev',
                'password' => '***REMOVED***', // cast 'hashed' lo encripta
                'role' => UserRole::SUPER_ADMIN->value,
                'business_id' => null,
            ]);
        }

        // 2. Negocio demo + su administrador + un empleado.
        $business = Business::firstOrCreate(
            ['slug' => 'demo-store'],
            [
                'name' => 'Demo Store',
                'status' => BusinessStatus::ACTIVE->value,
                'plan' => BusinessPlan::PRO->value,
            ]
        );

        User::firstOrCreate(
            ['business_id' => $business->id, 'email' => 'admin@demo.dev'],
            [
                'name' => 'Demo Admin',
                'password' => '***REMOVED***',
                'role' => UserRole::BUSINESS_ADMIN->value,
            ]
        );

        User::firstOrCreate(
            ['business_id' => $business->id, 'email' => 'empleado@demo.dev'],
            [
                'name' => 'Demo Empleado',
                'password' => '***REMOVED***',
                'role' => UserRole::EMPLOYEE->value,
            ]
        );

        // 3. Categoría, proveedor y cliente demo (business_id explícito: en
        // seeding no hay contexto de inquilino activo).
        $abarrotes = Category::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Abarrotes'],
            ['business_id' => $business->id],
        );
        $electrico = Category::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Eléctrico'],
            ['business_id' => $business->id],
        );

        Supplier::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Distribuidora Central'],
            ['business_id' => $business->id, 'contact_name' => 'Juan Pérez', 'phone' => '555-0100'],
        );

        Customer::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Cliente mostrador'],
            ['business_id' => $business->id],
        );

        $pieza = UnitOfMeasure::where('name', 'Pieza')->firstOrFail();
        $metro = UnitOfMeasure::where('name', 'Metro')->firstOrFail();

        foreach ([
            [
                'sku' => 'CAF-250', 'name' => 'Café 250g', 'category_id' => $abarrotes->id, 'unit_of_measure_id' => $pieza->id,
                'retail_price' => 89.90, 'stock' => 40, 'stock_minimo' => 5,
            ],
            [
                'sku' => 'TE-VRD', 'name' => 'Té verde', 'category_id' => $abarrotes->id, 'unit_of_measure_id' => $pieza->id,
                'retail_price' => 55.00, 'stock' => 25, 'stock_minimo' => 5,
            ],
            [
                'sku' => 'CAB-12AWG', 'name' => 'Cable eléctrico 12 AWG', 'category_id' => $electrico->id, 'unit_of_measure_id' => $metro->id,
                'retail_price' => 15.00, 'wholesale_price' => 12.00, 'wholesale_min_qty' => 50,
                'cost_price' => 9.00, 'stock' => 200.500, 'stock_minimo' => 20,
            ],
        ] as $p) {
            Product::firstOrCreate(
                ['business_id' => $business->id, 'sku' => $p['sku']],
                $p + ['business_id' => $business->id],
            );
        }
    }
}
