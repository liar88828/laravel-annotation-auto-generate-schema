<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * DepartmentFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'code' => fake()->text(20),
            'slug' => fake()->slug(),
            'description' => fake()->optional(0.8)->paragraph() ?? null,
            'status' => fake()->randomElement(['active', 'inactive']),
            'budget' => fake()->randomFloat(2, 0, 9999999999999),
        ];
    }
}