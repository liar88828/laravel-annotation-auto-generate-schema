<?php

namespace Database\Factories;

use App\Models\History;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * HistoryFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<History>
 */
class HistoryFactory extends Factory
{
    protected $model = History::class;

    public function definition(): array
    {
        return [
            'action' => fake()->text(100),
            'description' => fake()->optional(0.8)->paragraph() ?? null,
        ];
    }
}