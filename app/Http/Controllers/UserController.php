<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * UserController
 *
 * All validation is driven by UserSchema annotations.
 * No manual rules arrays — the schema is the contract.
 *
 * Routes (api.php):
 *   Route::apiResource('users', UserController::class);
 *   Route::patch('users/{user}/restore',         [UserController::class, 'restore']);
 *   Route::get('users/{user}/roles',             [UserController::class, 'roles']);
 *   Route::post('users/{user}/roles',            [UserController::class, 'attachRoles']);
 *   Route::delete('users/{user}/roles/{roleId}', [UserController::class, 'detachRole']);
 */
class UserController extends Controller
{
    // -------------------------------------------------------------------------
    // GET /users
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['profile', 'roles'])        // eager-loaded via #[HasOne] #[BelongsToMany]
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
            )
            ->when(
                $request->filled('department_id'),  // filter by BelongsTo
                fn ($q) => $q->where('department_id', $request->department_id)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($users);
    }

    // -------------------------------------------------------------------------
    // POST /users
    // -------------------------------------------------------------------------

    public function store(Request $request): JsonResponse
    {
        // Delegates to UserSchema annotations via HasSchema trait
        User::schemaValidateOrFail($request->all());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,      // #[Cast('hashed')] handles bcrypt
            'status' => $request->input('status', 'active'),
            'born_at' => $request->born_at,
            'settings' => $request->settings,
            'department_id' => $request->department_id, // #[BelongsTo]
        ]);

        // Sync BelongsToMany → #[BelongsToMany(related: RoleSchema::class)]
        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        // Sync BelongsToMany with pivot column → #[BelongsToMany(pivotColumns: ['date:joined_at'])]
        if ($request->has('team_ids')) {
            $user->teams()->sync(
                collect($request->team_ids)
                    ->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])
                    ->all()
            );
        }

        return response()->json(
            $user->load(['profile', 'roles', 'teams', 'department']),
            Response::HTTP_CREATED
        );
    }

    // -------------------------------------------------------------------------
    // GET /users/{user}
    // -------------------------------------------------------------------------

    public function show(User $user): JsonResponse
    {
        // Load all relations declared in UserSchema
        return response()->json(
            $user->load(['profile', 'posts', 'roles', 'teams', 'department'])
        );
    }

    // -------------------------------------------------------------------------
    // PUT /users/{user}   — full update
    // -------------------------------------------------------------------------

    public function update(Request $request, User $user): JsonResponse
    {
        // Auto-ignores $user->id in all #[Unique] checks; skipMissing=false (PUT)
        $user->schemaValidateForUpdate($request->all());

        $user->update($request->only([
            'name', 'email', 'status', 'born_at', 'settings', 'department_id',
        ]));

        if ($request->filled('password')) {
            $user->update(['password' => $request->password]);
        }

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        if ($request->has('team_ids')) {
            $user->teams()->sync(
                collect($request->team_ids)
                    ->mapWithKeys(fn ($id) => [$id => ['joined_at' => now()]])
                    ->all()
            );
        }

        return response()->json(
            $user->fresh(['profile', 'roles', 'teams', 'department'])
        );
    }

    // -------------------------------------------------------------------------
    // PATCH /users/{user}  — partial update
    // -------------------------------------------------------------------------

    public function patch(Request $request, User $user): JsonResponse
    {
        // PATCH: only validates present fields, skips Required for absent ones
        $user->schemaValidateForUpdate($request->all());

        $user->update($request->only([
            'name', 'email', 'status', 'born_at', 'settings', 'department_id',
        ]));

        if ($request->filled('password')) {
            $user->update(['password' => $request->password]);
        }

        return response()->json(
            $user->fresh(['profile', 'roles', 'teams', 'department'])
        );
    }

    // -------------------------------------------------------------------------
    // DELETE /users/{user}
    // -------------------------------------------------------------------------

    public function destroy(User $user): JsonResponse
    {
        // Soft delete — enabled via #[Table(softDeletes: true)] on UserSchema
        $user->delete();

        return response()->json(['message' => 'User deleted.'], Response::HTTP_OK);
    }

    // -------------------------------------------------------------------------
    // PATCH /users/{user}/restore
    // -------------------------------------------------------------------------

    public function restore(int $id): JsonResponse
    {
        // withTrashed() works because SoftDeletes was added via #[Table(softDeletes: true)]
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'User restored.', 'user' => $user]);
    }

    // -------------------------------------------------------------------------
    // GET /users/{user}/roles
    // ─ Exposes the BelongsToMany relation declared with #[BelongsToMany]
    // -------------------------------------------------------------------------

    public function roles(User $user): JsonResponse
    {
        // ->roles comes from the method generated by schema:relations
        return response()->json($user->roles()->withTimestamps()->get());
    }

    // -------------------------------------------------------------------------
    // POST /users/{user}/roles   { role_ids: [1, 2, 3] }
    // -------------------------------------------------------------------------

    public function attachRoles(Request $request, User $user): JsonResponse
    {
        Validator::validateOrFail(RoleIdsSchema::class, $request->all());

        // syncWithoutDetaching keeps existing roles intact
        $user->roles()->syncWithoutDetaching($request->role_ids);

        return response()->json($user->load('roles'));
    }

    // -------------------------------------------------------------------------
    // DELETE /users/{user}/roles/{roleId}
    // -------------------------------------------------------------------------

    public function detachRole(User $user, int $roleId): JsonResponse
    {
        $user->roles()->detach($roleId);

        return response()->json($user->load('roles'));
    }
}
