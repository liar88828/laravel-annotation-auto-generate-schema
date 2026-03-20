<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * RoleService
 *
 * Service layer for Role — handles all business logic.
 * Generated from RoleSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class RoleService
{
    // ── GET /roles ───────────────────────────────────────────────

    /**
     * Return paginated list of Role records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Role::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /roles ──────────────────────────────────────────────

    /**
     * Create and persist a new Role.
     */
    public static function store(array $data): Role
    {
        return Role::create($data);
    }

    // ── GET /roles/{role} ──────────────────────────────────────

    /**
     * Find a single Role by ID or fail with 404.
     */
    public static function show(int|string $id): Role
    {
        return Role::findOrFail($id);
    }

    // ── PUT /roles/{role} ──────────────────────────────────────

    /**
     * Update an existing Role with validated data.
     */
    public static function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->fresh();
    }

    // ── DELETE /roles/{role} ───────────────────────────────────

    /**
     * Soft delete the Role.
     */
    public static function destroy(Role $role): void
    {
        $role->delete();
    }


    // ── Restore ──────────────────────────────────────────────────────────────────

    /**
     * Restore a soft-deleted Role.
     */
    public static function restore(int|string $id): Role
    {
        $role = Role::withTrashed()->findOrFail($id);
        $role->restore();

        return $role;
    }
    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['public_id', 'name', 'status', 'age', 'born_at', 'department_id']));
    }
}