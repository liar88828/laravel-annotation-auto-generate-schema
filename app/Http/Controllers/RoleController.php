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
use App\Services\RoleService;

/**
 * RoleController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 */
#[Prefix('api/roles')]
class RoleController extends Controller
{
    // ── GET /roles ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $roles = RoleService::index($request);

        return response()->json($roles);
    }

    // ── POST /roles ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Role::schemaValidateOrFail($request->all());
        $role = RoleService::store($request->only(['public_id', 'name', 'status', 'age', 'born_at', 'department_id']));

        if ($request->has('history_ids')) {
            $role->history()->sync($request->history_ids);
        }
        if ($request->has('team_ids')) {
            $role->teams()->sync($request->team_ids);
        }

        return response()->json($role->load(['profile', 'articles', 'department', 'history', 'teams']), Response::HTTP_CREATED);
    }

    // ── GET /roles/{role} ──────────────────────────────────────────────────

    #[Get('/{role}')]
    public function show(Role $role): JsonResponse
    {
        return response()->json($role->load(['profile', 'articles', 'department', 'history', 'teams']));
    }

    // ── PUT /roles/{role} ─────────────────────────────────────────────────

    #[Put('/{role}')]
    public function update(Request $request, Role $role): JsonResponse
    {
        $role->schemaValidateForUpdate($request->all());
        RoleService::update($role, $request->only(['public_id', 'name', 'status', 'age', 'born_at', 'department_id']));

        if ($request->has('history_ids')) {
            $role->history()->sync($request->history_ids);
        }
        if ($request->has('team_ids')) {
            $role->teams()->sync($request->team_ids);
        }

        return response()->json($role->fresh()->load(['profile', 'articles', 'department', 'history', 'teams']));
    }

    // ── DELETE /roles/{role} ──────────────────────────────────────────────

    #[Delete('/{role}')]
    public function destroy(Role $role): JsonResponse
    {
        RoleService::destroy($role);

        return response()->json(['message' => 'Role deleted.']);
    }


    // ── PATCH /roles/restore/{id} ───────────────────────────────────────────

    #[Patch('/restore/{id}')]
    public function restore(int $id): JsonResponse
    {
        $role = Role::withTrashed()->findOrFail($id);
        $role->restore();

        return response()->json(['message' => 'Role restored.', 'role' => $role]);
    }
}