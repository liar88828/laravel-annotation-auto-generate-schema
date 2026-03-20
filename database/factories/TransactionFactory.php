<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * TransactionFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[CMigration]/#[Column] type, field name heuristics,
 * and validation attributes (#[CValidation], #[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [

        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Transaction::unguarded(fn () => parent::store($results));
    }
}