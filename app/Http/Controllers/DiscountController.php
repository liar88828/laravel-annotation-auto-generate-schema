<?php

namespace App\Http\Controllers;

use App\Models\Discount;
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
 * DiscountController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('discounts')]
class DiscountController extends Controller
{
    // ── GET /discounts ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $discounts = Discount::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($discounts);
    }

    // ── POST /discounts ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Discount::schemaValidateOrFail($request->all());

        $discount = Discount::create($request->only([]));

        return response()->json($discount, Response::HTTP_CREATED);
    }

    // ── GET /discounts/{discount} ──────────────────────────────────────────────────

    #[Get('/{discount}')]
    public function show(Discount $discount): JsonResponse
    {
        return response()->json($discount);
    }

    // ── PUT /discounts/{discount} ─────────────────────────────────────────────────

    #[Put('/{discount}')]
    public function update(Request $request, Discount $discount): JsonResponse
    {
        $discount->schemaValidateForUpdate($request->all());

        $discount->update($request->only([]));

        return response()->json($discount->fresh());
    }

    // ── DELETE /discounts/{discount} ──────────────────────────────────────────────

    #[Delete('/{discount}')]
    public function destroy(Discount $discount): JsonResponse
    {
        $discount->delete();

        return response()->json(['message' => 'Discount deleted.']);
    }

}