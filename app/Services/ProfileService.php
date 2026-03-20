<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * ProfileService
 *
 * Service layer for Profile — handles all business logic.
 * Generated from ProfileSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class ProfileService
{
    // ── GET /profiles ───────────────────────────────────────────────

    /**
     * Return paginated list of Profile records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Profile::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /profiles ──────────────────────────────────────────────

    /**
     * Create and persist a new Profile.
     */
    public static function store(array $data): Profile
    {
        return Profile::create($data);
    }

    // ── GET /profiles/{profile} ──────────────────────────────────────

    /**
     * Find a single Profile by ID or fail with 404.
     */
    public static function show(int|string $id): Profile
    {
        return Profile::findOrFail($id);
    }

    // ── PUT /profiles/{profile} ──────────────────────────────────────

    /**
     * Update an existing Profile with validated data.
     */
    public static function update(Profile $profile, array $data): Profile
    {
        $profile->update($data);

        return $profile->fresh();
    }

    // ── DELETE /profiles/{profile} ───────────────────────────────────

    /**
     * Permanently delete the Profile.
     */
    public static function destroy(Profile $profile): void
    {
        $profile->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['role_id', 'bio', 'avatar', 'phone', 'address', 'birth_date']));
    }
}