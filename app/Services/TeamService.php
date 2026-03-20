<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * TeamService
 *
 * Service layer for Team — handles all business logic.
 * Generated from TeamSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class TeamService
{
    // ── GET /teams ───────────────────────────────────────────────

    /**
     * Return paginated list of Team records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Team::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /teams ──────────────────────────────────────────────

    /**
     * Create and persist a new Team.
     */
    public static function store(array $data): Team
    {
        return Team::create($data);
    }

    // ── GET /teams/{team} ──────────────────────────────────────

    /**
     * Find a single Team by ID or fail with 404.
     */
    public static function show(int|string $id): Team
    {
        return Team::findOrFail($id);
    }

    // ── PUT /teams/{team} ──────────────────────────────────────

    /**
     * Update an existing Team with validated data.
     */
    public static function update(Team $team, array $data): Team
    {
        $team->update($data);

        return $team->fresh();
    }

    // ── DELETE /teams/{team} ───────────────────────────────────

    /**
     * Permanently delete the Team.
     */
    public static function destroy(Team $team): void
    {
        $team->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['name', 'slug', 'description']));
    }
}