<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
 * OrderController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('orders')]
class OrderController extends Controller
{
    // ── GET /orders ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($orders);
    }

    // ── POST /orders ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Order::schemaValidateOrFail($request->all());

        $order = Order::create($request->only(['name']));

        return response()->json($order, Response::HTTP_CREATED);
    }

    // ── GET /orders/{order} ──────────────────────────────────────────────────

    #[Get('/{order}')]
    public function show(Order $order): JsonResponse
    {
        return response()->json($order);
    }

    // ── PUT /orders/{order} ─────────────────────────────────────────────────

    #[Put('/{order}')]
    public function update(Request $request, Order $order): JsonResponse
    {
        $order->schemaValidateForUpdate($request->all());

        $order->update($request->only(['name']));

        return response()->json($order->fresh());
    }

    // ── DELETE /orders/{order} ──────────────────────────────────────────────

    #[Delete('/{order}')]
    public function destroy(Order $order): JsonResponse
    {
        $order->delete();

        return response()->json(['message' => 'Order deleted.']);
    }

}