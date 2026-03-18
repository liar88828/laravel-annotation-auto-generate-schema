<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * ArticleFactory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'role_id' => \App\Models\Role::factory()->create()->getKey(),
            'title' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'content' => fake()->paragraphs(3, true),
            'excerpt' => fake()->boolean(80) ? fake()->text(255) : null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => fake()->boolean(80) ? fake()->dateTime()->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Store the model bypassing mass assignment so FK columns not in $fillable
     * (e.g. user_id) are still persisted correctly.
     */
    protected function store(iterable $results): void
    {
        Article::unguarded(fn () => parent::store($results));
    }
}