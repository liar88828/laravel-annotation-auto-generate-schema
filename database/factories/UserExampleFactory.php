<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\UserExample;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * UserExampleFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<UserExample>
 */
class UserExampleFactory extends Factory
{
    protected $model = UserExample::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'status' => fake()->optional(0.8)->randomElement(['active', 'inactive', 'suspended']),
            'age' => fake()->optional(0.8)->numberBetween(18, 80) ?? null,
            'born_at' => fake()->optional(0.8)->dateTime() ?? null,
            'department_id' => fake()->boolean(80) ? Department::factory()->create()->id : null,
        ];
    }
}
