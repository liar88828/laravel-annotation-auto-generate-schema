<?php

namespace Database\Factories;

use App\Models\Expired;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * ExpiredFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Expired>
 */
class ExpiredFactory extends Factory
{
    protected $model = Expired::class;

    public function definition(): array
    {
        return [

        ];
    }
}
