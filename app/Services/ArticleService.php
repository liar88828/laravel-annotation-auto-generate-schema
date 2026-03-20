<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * ArticleService
 *
 * Service layer for Article — handles all business logic.
 * Generated from ArticleSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class ArticleService
{
    // ── GET /articles ───────────────────────────────────────────────

    /**
     * Return paginated list of Article records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Article::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /articles ──────────────────────────────────────────────

    /**
     * Create and persist a new Article.
     */
    public static function store(array $data): Article
    {
        return Article::create($data);
    }

    // ── GET /articles/{article} ──────────────────────────────────────

    /**
     * Find a single Article by ID or fail with 404.
     */
    public static function show(int|string $id): Article
    {
        return Article::findOrFail($id);
    }

    // ── PUT /articles/{article} ──────────────────────────────────────

    /**
     * Update an existing Article with validated data.
     */
    public static function update(Article $article, array $data): Article
    {
        $article->update($data);

        return $article->fresh();
    }

    // ── DELETE /articles/{article} ───────────────────────────────────

    /**
     * Soft delete the Article.
     */
    public static function destroy(Article $article): void
    {
        $article->delete();
    }


    // ── Restore ──────────────────────────────────────────────────────────────────

    /**
     * Restore a soft-deleted Article.
     */
    public static function restore(int|string $id): Article
    {
        $article = Article::withTrashed()->findOrFail($id);
        $article->restore();

        return $article;
    }
    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['role_id', 'title', 'slug', 'content', 'excerpt', 'status', 'published_at']));
    }
}