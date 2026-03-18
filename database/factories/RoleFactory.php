<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * RoleFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'name' => fake()->name(),
            'status' => fake()->optional(0.8)->randomElement(['active', 'inactive', 'suspended']),
            'age' => fake()->optional(0.8)->numberBetween(18, 80) ?? null,
            'born_at' => fake()->optional(0.8)->dateTime() ?? null,
            'department_id' => fake()->boolean(80) ? \App\Models\Department::factory()->create()->id : null,
        ];
    }
}