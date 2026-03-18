<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * ArticleController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('articles')]
class ArticleController extends Controller
{
    // ── GET /articles ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $articles = Article::query()
            ->when($request->filled('search'), fn ($q) => $q->where('id', 'like', "%{$request->search}%")
            )
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->role_id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($articles);
    }

    // ── POST /articles ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Article::schemaValidateOrFail($request->all());
        $article = Article::create($request->only(['role_id', 'title', 'slug', 'content', 'excerpt', 'status', 'published_at']));

        return response()->json($article->load(['role']), Response::HTTP_CREATED);
    }

    // ── GET /articles/{article} ──────────────────────────────────────────────────

    #[Get('/{article}')]
    public function show(Article $article): JsonResponse
    {
        return response()->json($article->load(['role']));
    }

    // ── PUT /articles/{article} ─────────────────────────────────────────────────

    #[Put('/{article}')]
    public function update(Request $request, Article $article): JsonResponse
    {
        $article->schemaValidateForUpdate($request->all());
        $article->update($request->only(['role_id', 'title', 'slug', 'content', 'excerpt', 'status', 'published_at']));

        return response()->json($article->fresh()->load(['role']));
    }

    // ── DELETE /articles/{article} ──────────────────────────────────────────────

    #[Delete('/{article}')]
    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(['message' => 'Article deleted.']);
    }

    // ── PATCH /articles/restore/{id} ───────────────────────────────────────────

    #[Patch('/restore/{id}')]
    public function restore(int $id): JsonResponse
    {
        $article = Article::withTrashed()->findOrFail($id);
        $article->restore();

        return response()->json(['message' => 'Article restored.', 'article' => $article]);
    }
}
