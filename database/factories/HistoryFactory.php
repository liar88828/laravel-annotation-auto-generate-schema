<?php

namespace Database\Factories;

use App\Models\History;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * HistoryFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[CMigration]/#[Column] type, field name heuristics,
 * and validation attributes (#[CValidation], #[In], #[Email], #[Uuid], #[Min], #[Max]).
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
            'description' => fake()->boolean(80) ? fake()->paragraph() : null,
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        History::unguarded(fn () => parent::store($results));
    }
}