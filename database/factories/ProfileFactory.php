<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ProfileFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'bio' => fake()->optional(0.8)->paragraph() ?? null,
            'avatar' => fake()->optional(0.8)->imageUrl() ?? null,
            'phone' => fake()->optional(0.8)->phoneNumber() ?? null,
            'address' => fake()->optional(0.8)->streetAddress() ?? null,
            'birth_date' => fake()->optional(0.8)->date() ?? null,
        ];
    }
}