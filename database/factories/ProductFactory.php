<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ProductFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->optional(0.8)->paragraph() ?? null,
            'price' => fake()->randomFloat(2, 1, 9999),
            'stock' => fake()->numberBetween(1, 100),
            'sku' => fake()->optional(0.8)->text(100) ?? null,
            'is_active' => fake()->boolean(),
        ];
    }
}