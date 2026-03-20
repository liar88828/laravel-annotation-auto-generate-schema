<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ProductFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[CMigration]/#[Column] type, field name heuristics,
 * and validation attributes (#[CValidation], #[In], #[Email], #[Uuid], #[Min], #[Max]).
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
            'description' => fake()->boolean(80) ? fake()->paragraph() : null,
            'price' => fake()->randomFloat(2, 1, 9999),
            'stock' => fake()->numberBetween(1, 100),
            'sku' => fake()->boolean(80) ? fake()->text(100) : null,
            'status' => fake()->text(20),
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Product::unguarded(fn () => parent::store($results));
    }
}