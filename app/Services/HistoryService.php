<?php

namespace App\Services;

use App\Models\History;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * HistoryService
 *
 * Service layer for History — handles all business logic.
 * Generated from HistorySchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class HistoryService
{
    // ── GET /histories ───────────────────────────────────────────────

    /**
     * Return paginated list of History records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return History::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /histories ──────────────────────────────────────────────

    /**
     * Create and persist a new History.
     */
    public static function store(array $data): History
    {
        return History::create($data);
    }

    // ── GET /histories/{history} ──────────────────────────────────────

    /**
     * Find a single History by ID or fail with 404.
     */
    public static function show(int|string $id): History
    {
        return History::findOrFail($id);
    }

    // ── PUT /histories/{history} ──────────────────────────────────────

    /**
     * Update an existing History with validated data.
     */
    public static function update(History $history, array $data): History
    {
        $history->update($data);

        return $history->fresh();
    }

    // ── DELETE /histories/{history} ───────────────────────────────────

    /**
     * Permanently delete the History.
     */
    public static function destroy(History $history): void
    {
        $history->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip(['action', 'description']));
    }
}