<?php

namespace App\Http\Controllers;

use App\Models\Shop;
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
 * ShopController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('shops')]
class ShopController extends Controller
{
    // ── GET /shops ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $shops = Shop::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($shops);
    }

    // ── POST /shops ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Shop::schemaValidateOrFail($request->all());

        $shop = Shop::create($request->only(['name', 'address', 'is_active']));

        return response()->json($shop->load(['products']), Response::HTTP_CREATED);
    }

    // ── GET /shops/{shop} ──────────────────────────────────────────────────

    #[Get('/{shop}')]
    public function show(Shop $shop): JsonResponse
    {
        return response()->json($shop->load(['products']));
    }

    // ── PUT /shops/{shop} ─────────────────────────────────────────────────

    #[Put('/{shop}')]
    public function update(Request $request, Shop $shop): JsonResponse
    {
        $shop->schemaValidateForUpdate($request->all());

        $shop->update($request->only(['name', 'address', 'is_active']));

        return response()->json($shop->fresh()->load(['products']));
    }

    // ── DELETE /shops/{shop} ──────────────────────────────────────────────

    #[Delete('/{shop}')]
    public function destroy(Shop $shop): JsonResponse
    {
        $shop->delete();

        return response()->json(['message' => 'Shop deleted.']);
    }


    // ── PATCH /shops/restore/{id} ───────────────────────────────────────────

    #[Patch('/restore/{id}')]
    public function restore(int $id): JsonResponse
    {
        $shop = Shop::withTrashed()->findOrFail($id);
        $shop->restore();

        return response()->json(['message' => 'Shop restored.', 'shop' => $shop]);
    }
}