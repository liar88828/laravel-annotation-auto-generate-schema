<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * TransactionService
 *
 * Service layer for Transaction — handles all business logic.
 * Generated from TransactionSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class TransactionService
{
    // ── GET /transactions ───────────────────────────────────────────────

    /**
     * Return paginated list of Transaction records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Transaction::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /transactions ──────────────────────────────────────────────

    /**
     * Create and persist a new Transaction.
     */
    public static function store(array $data): Transaction
    {
        return Transaction::create($data);
    }

    // ── GET /transactions/{transaction} ──────────────────────────────────────

    /**
     * Find a single Transaction by ID or fail with 404.
     */
    public static function show(int|string $id): Transaction
    {
        return Transaction::findOrFail($id);
    }

    // ── PUT /transactions/{transaction} ──────────────────────────────────────

    /**
     * Update an existing Transaction with validated data.
     */
    public static function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);

        return $transaction->fresh();
    }

    // ── DELETE /transactions/{transaction} ───────────────────────────────────

    /**
     * Permanently delete the Transaction.
     */
    public static function destroy(Transaction $transaction): void
    {
        $transaction->delete();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Return only the fillable fields from an array.
     */
    public static function only(array $data): array
    {
        return array_intersect_key($data, array_flip([]));
    }
}