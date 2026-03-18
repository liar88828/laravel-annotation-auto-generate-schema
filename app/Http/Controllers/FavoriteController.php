<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * FavoriteController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('favorites')]
class FavoriteController extends Controller
{
    // ── GET /favorites ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($favorites);
    }

    // ── POST /favorites ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Favorite::schemaValidateOrFail($request->all());

        $favorite = Favorite::create($request->only([]));

        return response()->json($favorite, Response::HTTP_CREATED);
    }

    // ── GET /favorites/{favorite} ──────────────────────────────────────────────────

    #[Get('/{favorite}')]
    public function show(Favorite $favorite): JsonResponse
    {
        return response()->json($favorite);
    }

    // ── PUT /favorites/{favorite} ─────────────────────────────────────────────────

    #[Put('/{favorite}')]
    public function update(Request $request, Favorite $favorite): JsonResponse
    {
        $favorite->schemaValidateForUpdate($request->all());

        $favorite->update($request->only([]));

        return response()->json($favorite->fresh());
    }

    // ── DELETE /favorites/{favorite} ──────────────────────────────────────────────

    #[Delete('/{favorite}')]
    public function destroy(Favorite $favorite): JsonResponse
    {
        $favorite->delete();

        return response()->json(['message' => 'Favorite deleted.']);
    }

}