<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User routes
|--------------------------------------------------------------------------
|
| Standard apiResource covers: index, store, show, update, destroy.
| Extra routes are added manually below.
|
*/

Route::apiResource('users', UserController::class);

// Partial update (PATCH semantics — only validates present fields)
Route::patch('users/{user}/patch', [UserController::class, 'patch']);

// Soft-delete restore (model binding skips soft-deleted records by default,
// so we accept a raw $id here and use withTrashed() inside)
Route::patch('users/{id}/restore', [UserController::class, 'restore']);

// Relation sub-resources
Route::prefix('users/{user}')->group(function () {

    // BelongsToMany → roles
    Route::get('roles', [UserController::class, 'roles']);
    Route::post('roles', [UserController::class, 'attachRoles']);
    Route::delete('roles/{roleId}', [UserController::class, 'detachRole']);

});
