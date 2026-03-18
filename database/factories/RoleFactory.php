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
            'age' => fake()->boolean(80) ? fake()->numberBetween(18, 80) : null,
            'born_at' => fake()->boolean(80) ? fake()->dateTime()->format('Y-m-d H:i:s') : null,
            'department_id' => fake()->boolean(80) ? \App\Models\Department::factory()->create()->getKey() : null,
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * (e.g. user_id) are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Role::unguarded(fn () => parent::store($results));
    }
}