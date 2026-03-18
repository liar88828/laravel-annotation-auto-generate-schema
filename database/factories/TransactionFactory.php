<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * TransactionFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory()->create()->id,
            'shop_id' => \App\Models\Shop::factory()->create()->id,
            'quantity' => fake()->numberBetween(1, 100),
            'price' => fake()->randomFloat(2, 1, 9999),
            'total' => fake()->randomFloat(2, 0, 999999999999),
            'status' => fake()->randomElement(['pending', 'paid', 'cancelled']),
            'notes' => fake()->optional(0.8)->paragraph() ?? null,
        ];
    }
}