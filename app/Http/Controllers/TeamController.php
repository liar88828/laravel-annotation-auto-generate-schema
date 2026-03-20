<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
use App\Services\TeamService;

/**
 * TeamController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 */
#[Prefix('api/teams')]
class TeamController extends Controller
{
    // ── GET /teams ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $teams = TeamService::index($request);

        return response()->json($teams);
    }

    // ── POST /teams ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Team::schemaValidateOrFail($request->all());
        $team = TeamService::store($request->only(['name', 'slug', 'description']));

        return response()->json($team, Response::HTTP_CREATED);
    }

    // ── GET /teams/{team} ──────────────────────────────────────────────────

    #[Get('/{team}')]
    public function show(Team $team): JsonResponse
    {
        return response()->json($team);
    }

    // ── PUT /teams/{team} ─────────────────────────────────────────────────

    #[Put('/{team}')]
    public function update(Request $request, Team $team): JsonResponse
    {
        $team->schemaValidateForUpdate($request->all());
        TeamService::update($team, $request->only(['name', 'slug', 'description']));

        return response()->json($team->fresh());
    }

    // ── DELETE /teams/{team} ──────────────────────────────────────────────

    #[Delete('/{team}')]
    public function destroy(Team $team): JsonResponse
    {
        TeamService::destroy($team);

        return response()->json(['message' => 'Team deleted.']);
    }

}