<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * ProductService
 *
 * Service layer for Product — handles all business logic.
 * Generated from ProductSchema.
 *
 * Enabled options from #[Service]:
 *   static       = true
 *   findBySlug   = false
 *   findByStatus = false
 *   publish      = false
 */
class ProductService
{
    // ── GET /products ───────────────────────────────────────────────

    /**
     * Return paginated list of Product records.
     * Supports: ?search=, ?per_page=, ?status=
     */
    public static function index(Request $request): LengthAwarePaginator
    {
        return Product::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));
    }

    // ── POST /products ──────────────────────────────────────────────

    /**
     * Create and persist a new Product.
     */
    public static function store(array $data): Product
    {
        return Product::create($data);
    }

    // ── GET /products/{product} ──────────────────────────────────────

    /**
     * Find a single Product by ID or fail with 404.
     */
    public static function show(int|string $id): Product
    {
        return Product::findOrFail($id);
    }

    // ── PUT /products/{product} ──────────────────────────────────────

    /**
     * Update an existing Product with validated data.
     */
    public static function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    // ── DELETE /products/{product} ───────────────────────────────────

    /**
     * Permanently delete the Product.
     */
    public static function destroy(Product $product): void
    {
        $product->delete();
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