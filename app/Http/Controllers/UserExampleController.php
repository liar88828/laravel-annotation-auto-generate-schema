<?php

namespace App\Http\Controllers;

use App\Models\UserExample;
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
 * UserExampleController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('user_examples')]
class UserExampleController extends Controller
{
    // ── GET /user_examples ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $userExamples = UserExample::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($userExamples);
    }

    // ── POST /user_examples ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        UserExample::schemaValidateOrFail($request->all());

        $userExample = UserExample::create($request->only(['public_id', 'name', 'email', 'password', 'status', 'age', 'born_at', 'department_id']));

        if ($request->has('role_ids')) {
            $userExample->roles()->sync($request->role_ids);
        }
        if ($request->has('team_ids')) {
            $userExample->teams()->sync($request->team_ids);
        }

        return response()->json($userExample->load(['profile', 'posts', 'department', 'roles', 'teams']), Response::HTTP_CREATED);
    }

    // ── GET /user_examples/{userExample} ──────────────────────────────────────────────────

    #[Get('/{userExample}')]
    public function show(UserExample $userExample): JsonResponse
    {
        return response()->json($userExample->load(['profile', 'posts', 'department', 'roles', 'teams']));
    }

    // ── PUT /user_examples/{userExample} ─────────────────────────────────────────────────

    #[Put('/{userExample}')]
    public function update(Request $request, UserExample $userExample): JsonResponse
    {
        $userExample->schemaValidateForUpdate($request->all());

        $userExample->update($request->only(['public_id', 'name', 'email', 'password', 'status', 'age', 'born_at', 'department_id']));

        if ($request->has('role_ids')) {
            $userExample->roles()->sync($request->role_ids);
        }
        if ($request->has('team_ids')) {
            $userExample->teams()->sync($request->team_ids);
        }

        return response()->json($userExample->fresh()->load(['profile', 'posts', 'department', 'roles', 'teams']));
    }

    // ── DELETE /user_examples/{userExample} ──────────────────────────────────────────────

    #[Delete('/{userExample}')]
    public function destroy(UserExample $userExample): JsonResponse
    {
        $userExample->delete();

        return response()->json(['message' => 'UserExample deleted.']);
    }


    // ── PATCH /user_examples/restore/{id} ───────────────────────────────────────────

    #[Patch('/restore/{id}')]
    public function restore(int $id): JsonResponse
    {
        $userExample = UserExample::withTrashed()->findOrFail($id);
        $userExample->restore();

        return response()->json(['message' => 'UserExample restored.', 'userExample' => $userExample]);
    }
}