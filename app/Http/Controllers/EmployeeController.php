<?php

namespace App\Http\Controllers;

use App\Models\Employee;
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
 * EmployeeController
 *
 * Routes registered automatically via spatie/laravel-route-attributes.
 * Requires: composer require spatie/laravel-route-attributes
 */
#[Prefix('employees')]
class EmployeeController extends Controller
{
    // ── GET /employees ──────────────────────────────────────────────────────────

    #[Get('/')]
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::query()
            ->when($request->filled('search'), fn ($q) =>
                $q->where('id', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($employees);
    }

    // ── POST /employees ─────────────────────────────────────────────────────────

    #[Post('/')]
    public function store(Request $request): JsonResponse
    {
        Employee::schemaValidateOrFail($request->all());

        $employee = Employee::create($request->only([]));

        return response()->json($employee, Response::HTTP_CREATED);
    }

    // ── GET /employees/{employee} ──────────────────────────────────────────────────

    #[Get('/{employee}')]
    public function show(Employee $employee): JsonResponse
    {
        return response()->json($employee);
    }

    // ── PUT /employees/{employee} ─────────────────────────────────────────────────

    #[Put('/{employee}')]
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $employee->schemaValidateForUpdate($request->all());

        $employee->update($request->only([]));

        return response()->json($employee->fresh());
    }

    // ── DELETE /employees/{employee} ──────────────────────────────────────────────

    #[Delete('/{employee}')]
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(['message' => 'Employee deleted.']);
    }

}