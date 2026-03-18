<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
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
 * WarehouseController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('warehouses')]
class WarehouseController extends Controller
{
    // ── GET /warehouses ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $warehouses = Warehouse::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($warehouses);
    }

    // ── POST /warehouses ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Warehouse::schemaValidateOrFail($request->all());

        $warehouse = Warehouse::create($request->only([]));

        return response()->json($warehouse, Response::HTTP_CREATED);
    }

    // ── GET /warehouses/{warehouse} ──────────────────────────────────────────────────

    #[Get('/{warehouse}')]
    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json($warehouse);
    }

    // ── PUT /warehouses/{warehouse} ─────────────────────────────────────────────────

    #[Put('/{warehouse}')]
    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->schemaValidateForUpdate($request->all());

        $warehouse->update($request->only([]));

        return response()->json($warehouse->fresh());
    }

    // ── DELETE /warehouses/{warehouse} ──────────────────────────────────────────────

    #[Delete('/{warehouse}')]
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return response()->json(['message' => 'Warehouse deleted.']);
    }

}