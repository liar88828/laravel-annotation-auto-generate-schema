<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * DepartmentService
 *
 * Service layer for Department — handles all business logic.
 * Generated from DepartmentSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class DepartmentService
{
    // ── GET /departments ───────────────────────────────────────────────

    /**
     * Return paginated list of Department records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Department::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /departments ──────────────────────────────────────────────

    /**
     * Create and persist a new Department.
     */
    public static function store(array $data): Department
    {
        return Department::create($data);
    }

    // ── GET /departments/{department} ──────────────────────────────────────

    /**
     * Find a single Department by ID or fail with 404.
     */
    public static function show(int|string $id): Department
    {
        return Department::findOrFail($id);
    }

    // ── PUT /departments/{department} ──────────────────────────────────────

    /**
     * Update an existing Department with validated data.
     */
    public static function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }

    // ── DELETE /departments/{department} ───────────────────────────────────

    /**
     * Permanently delete the Department.
     */
    public static function destroy(Department $department): void
    {
        $department->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['name', 'code', 'slug', 'description', 'status', 'budget']));
    }
}