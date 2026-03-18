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
            'role_id' => \App\Models\Role::factory()->create()->getKey(),
            'bio' => fake()->boolean(80) ? fake()->paragraph() : null,
            'avatar' => fake()->boolean(80) ? fake()->imageUrl() : null,
            'phone' => fake()->boolean(80) ? fake()->phoneNumber() : null,
            'address' => fake()->boolean(80) ? fake()->streetAddress() : null,
            'birth_date' => fake()->boolean(80) ? fake()->date() : null,
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * (e.g. user_id) are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Profile::unguarded(fn () => parent::store($results));
    }
}