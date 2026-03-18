<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
 * SettingController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('settings')]
class SettingController extends Controller
{
    // ── GET /settings ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $settings = Setting::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($settings);
    }

    // ── POST /settings ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Setting::schemaValidateOrFail($request->all());

        $setting = Setting::create($request->only([]));

        return response()->json($setting, Response::HTTP_CREATED);
    }

    // ── GET /settings/{setting} ──────────────────────────────────────────────────

    #[Get('/{setting}')]
    public function show(Setting $setting): JsonResponse
    {
        return response()->json($setting);
    }

    // ── PUT /settings/{setting} ─────────────────────────────────────────────────

    #[Put('/{setting}')]
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $setting->schemaValidateForUpdate($request->all());

        $setting->update($request->only([]));

        return response()->json($setting->fresh());
    }

    // ── DELETE /settings/{setting} ──────────────────────────────────────────────

    #[Delete('/{setting}')]
    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json(['message' => 'Setting deleted.']);
    }

}