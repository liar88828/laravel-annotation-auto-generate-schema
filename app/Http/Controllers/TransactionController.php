<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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
 * TransactionController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('transactions')]
class TransactionController extends Controller
{
    // ── GET /transactions ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->shop_id))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($transactions);
    }

    // ── POST /transactions ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Transaction::schemaValidateOrFail($request->all());

        $transaction = Transaction::create($request->only(['product_id', 'shop_id', 'quantity', 'price', 'total', 'status', 'notes']));

        return response()->json($transaction->load(['product', 'shop']), Response::HTTP_CREATED);
    }

    // ── GET /transactions/{transaction} ──────────────────────────────────────────────────

    #[Get('/{transaction}')]
    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction->load(['product', 'shop']));
    }

    // ── PUT /transactions/{transaction} ─────────────────────────────────────────────────

    #[Put('/{transaction}')]
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $transaction->schemaValidateForUpdate($request->all());

        $transaction->update($request->only(['product_id', 'shop_id', 'quantity', 'price', 'total', 'status', 'notes']));

        return response()->json($transaction->fresh()->load(['product', 'shop']));
    }

    // ── DELETE /transactions/{transaction} ──────────────────────────────────────────────

    #[Delete('/{transaction}')]
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json(['message' => 'Transaction deleted.']);
    }

}