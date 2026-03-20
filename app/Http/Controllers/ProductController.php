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
use App\Services\ProductService;

/**
 * ProductController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 */
#[Prefix('api/products')]
class ProductController extends Controller
{
    // ── GET /products ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $products = ProductService::index($request);

        return response()->json($products);
    }

    // ── POST /products ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Product::schemaValidateOrFail($request->all());
        $product = ProductService::store($request->only(['name', 'description', 'price', 'stock', 'sku', 'status']));

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
        ProductService::update($product, $request->only(['name', 'description', 'price', 'stock', 'sku', 'status']));

        return response()->json($product->fresh());
    }

    // ── DELETE /products/{product} ──────────────────────────────────────────────

    #[Delete('/{product}')]
    public function destroy(Product $product): JsonResponse
    {
        ProductService::destroy($product);

        return response()->json(['message' => 'Product deleted.']);
    }

}