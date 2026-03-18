<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
 * ProductController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('products')]
class ProductController extends Controller
{
    // ── GET /products ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($products);
    }

    // ── POST /products ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Product::schemaValidateOrFail($request->all());

        $product = Product::create($request->only(['name', 'description', 'price', 'stock', 'sku', 'is_active']));

        return response()->json($product, Response::HTTP_CREATED);
    }

    // ── GET /products/{product} ──────────────────────────────────────────────────

    #[Get('/{product}')]
    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    // ── PUT /products/{product} ─────────────────────────────────────────────────

    #[Put('/{product}')]
    public function update(Request $request, Product $product): JsonResponse
    {
        $product->schemaValidateForUpdate($request->all());

        $product->update($request->only(['name', 'description', 'price', 'stock', 'sku', 'is_active']));

        return response()->json($product->fresh());
    }

    // ── DELETE /products/{product} ──────────────────────────────────────────────

    #[Delete('/{product}')]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }


    // ── PATCH /products/restore/{id} ───────────────────────────────────────────

    #[Patch('/restore/{id}')]
    public function restore(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return response()->json(['message' => 'Product restored.', 'product' => $product]);
    }
}