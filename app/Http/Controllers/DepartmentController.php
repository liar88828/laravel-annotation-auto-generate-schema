<?php

namespace App\Http\Controllers;

use App\Models\Department;
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
 * DepartmentController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('departments')]
class DepartmentController extends Controller
{
    // ── GET /departments ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($departments);
    }

    // ── POST /departments ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Department::schemaValidateOrFail($request->all());
        $department = Department::create($request->only(['name', 'code', 'slug', 'description', 'status', 'budget']));

        return response()->json($department->load(['roles']), Response::HTTP_CREATED);
    }

    // ── GET /departments/{department} ──────────────────────────────────────────────────

    #[Get('/{department}')]
    public function show(Department $department): JsonResponse
    {
        return response()->json($department->load(['roles']));
    }

    // ── PUT /departments/{department} ─────────────────────────────────────────────────

    #[Put('/{department}')]
    public function update(Request $request, Department $department): JsonResponse
    {
        $department->schemaValidateForUpdate($request->all());
        $department->update($request->only(['name', 'code', 'slug', 'description', 'status', 'budget']));

        return response()->json($department->fresh()->load(['roles']));
    }

    // ── DELETE /departments/{department} ──────────────────────────────────────────────

    #[Delete('/{department}')]
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json(['message' => 'Department deleted.']);
    }

}