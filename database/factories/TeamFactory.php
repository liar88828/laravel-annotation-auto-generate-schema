<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * TeamFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'slug' => fake()->slug(),
            'description' => fake()->boolean(80) ? fake()->paragraph() : null,
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * (e.g. user_id) are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Team::unguarded(fn () => parent::store($results));
    }
}