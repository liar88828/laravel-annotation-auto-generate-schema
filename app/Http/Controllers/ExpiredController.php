<?php

namespace App\Http\Controllers;

use App\Models\Expired;
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
 * ExpiredController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('expireds')]
class ExpiredController extends Controller
{
    // ── GET /expireds ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $expireds = Expired::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($expireds);
    }

    // ── POST /expireds ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Expired::schemaValidateOrFail($request->all());

        $expired = Expired::create($request->only([]));

        return response()->json($expired, Response::HTTP_CREATED);
    }

    // ── GET /expireds/{expired} ──────────────────────────────────────────────────

    #[Get('/{expired}')]
    public function show(Expired $expired): JsonResponse
    {
        return response()->json($expired);
    }

    // ── PUT /expireds/{expired} ─────────────────────────────────────────────────

    #[Put('/{expired}')]
    public function update(Request $request, Expired $expired): JsonResponse
    {
        $expired->schemaValidateForUpdate($request->all());

        $expired->update($request->only([]));

        return response()->json($expired->fresh());
    }

    // ── DELETE /expireds/{expired} ──────────────────────────────────────────────

    #[Delete('/{expired}')]
    public function destroy(Expired $expired): JsonResponse
    {
        $expired->delete();

        return response()->json(['message' => 'Expired deleted.']);
    }

}