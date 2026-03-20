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
use App\Services\HistoryService;

/**
 * HistoryController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 */
#[Prefix('api/histories')]
class HistoryController extends Controller
{
    // ── GET /histories ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $historys = HistoryService::index($request);

        return response()->json($historys);
    }

    // ── POST /histories ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        History::schemaValidateOrFail($request->all());
        $history = HistoryService::store($request->only(['action', 'description']));

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
        HistoryService::update($history, $request->only(['action', 'description']));

        if ($request->has('role_ids')) {
            $history->roles()->sync($request->role_ids);
        }

        return response()->json($history->fresh()->load(['roles']));
    }

    // ── DELETE /histories/{history} ──────────────────────────────────────────────

    #[Delete('/{history}')]
    public function destroy(History $history): JsonResponse
    {
        HistoryService::destroy($history);

        return response()->json(['message' => 'History deleted.']);
    }

}