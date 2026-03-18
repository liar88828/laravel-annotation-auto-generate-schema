<?php

namespace App\Http\Controllers;

use App\Models\Role;
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
 * RoleController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('roles')]
class RoleController extends Controller
{
    // ── GET /roles ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($roles);
    }

    // ── POST /roles ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Role::schemaValidateOrFail($request->all());

        $role = Role::create($request->only([]));

        return response()->json($role, Response::HTTP_CREATED);
    }

    // ── GET /roles/{role} ──────────────────────────────────────────────────

    #[Get('/{role}')]
    public function show(Role $role): JsonResponse
    {
        return response()->json($role);
    }

    // ── PUT /roles/{role} ─────────────────────────────────────────────────

    #[Put('/{role}')]
    public function update(Request $request, Role $role): JsonResponse
    {
        $role->schemaValidateForUpdate($request->all());

        $role->update($request->only([]));

        return response()->json($role->fresh());
    }

    // ── DELETE /roles/{role} ──────────────────────────────────────────────

    #[Delete('/{role}')]
    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

}