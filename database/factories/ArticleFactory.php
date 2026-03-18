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
            'user_id' => \App\Models\User::factory()->create()->id,
            'title' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'content' => fake()->paragraphs(3, true),
            'excerpt' => fake()->optional(0.8)->text(255) ?? null,
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => fake()->optional(0.8)->dateTime() ?? null,
        ];
    }
}