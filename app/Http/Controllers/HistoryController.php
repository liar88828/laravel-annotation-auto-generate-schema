<?php

namespace App\Http\Controllers;

use App\Models\History;
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
 * HistoryController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('histories')]
class HistoryController extends Controller
{
    // ── GET /histories ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $historys = History::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($historys);
    }

    // ── POST /histories ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        History::schemaValidateOrFail($request->all());
        $history = History::create($request->only(['action', 'description']));

        if ($request->has('role_ids')) {
            $history->roles()->sync($request->role_ids);
        }

        return response()->json($history->load(['roles']), Response::HTTP_CREATED);
    }

    // ── GET /histories/{history} ──────────────────────────────────────────────────

    #[Get('/{history}')]
    public function show(History $history): JsonResponse
    {
        return response()->json($history->load(['roles']));
    }

    // ── PUT /histories/{history} ─────────────────────────────────────────────────

    #[Put('/{history}')]
    public function update(Request $request, History $history): JsonResponse
    {
        $history->schemaValidateForUpdate($request->all());
        $history->update($request->only(['action', 'description']));

        if ($request->has('role_ids')) {
            $history->roles()->sync($request->role_ids);
        }

        return response()->json($history->fresh()->load(['roles']));
    }

    // ── DELETE /histories/{history} ──────────────────────────────────────────────

    #[Delete('/{history}')]
    public function destroy(History $history): JsonResponse
    {
        $history->delete();

        return response()->json(['message' => 'History deleted.']);
    }

}