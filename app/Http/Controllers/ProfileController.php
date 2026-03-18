<?php

namespace App\Http\Controllers;

use App\Models\Profile;
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
 * ProfileController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('profiles')]
class ProfileController extends Controller
{
    // ── GET /profiles ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $profiles = Profile::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->when($request->filled('role_id'), fn ($q) => $q->where('role_id', $request->role_id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($profiles);
    }

    // ── POST /profiles ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Profile::schemaValidateOrFail($request->all());
        $profile = Profile::create($request->only(['bio', 'avatar', 'phone', 'address', 'birth_date']));

        return response()->json($profile->load(['role']), Response::HTTP_CREATED);
    }

    // ── GET /profiles/{profile} ──────────────────────────────────────────────────

    #[Get('/{profile}')]
    public function show(Profile $profile): JsonResponse
    {
        return response()->json($profile->load(['role']));
    }

    // ── PUT /profiles/{profile} ─────────────────────────────────────────────────

    #[Put('/{profile}')]
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $profile->schemaValidateForUpdate($request->all());
        $profile->update($request->only(['bio', 'avatar', 'phone', 'address', 'birth_date']));

        return response()->json($profile->fresh()->load(['role']));
    }

    // ── DELETE /profiles/{profile} ──────────────────────────────────────────────

    #[Delete('/{profile}')]
    public function destroy(Profile $profile): JsonResponse
    {
        $profile->delete();

        return response()->json(['message' => 'Profile deleted.']);
    }

}