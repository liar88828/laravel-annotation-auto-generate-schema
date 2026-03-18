<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Marks a schema class as the source of truth for an Eloquent Model.
 * Controls $table, $primaryKey, $keyType, $incrementing, and $connection.
 *
 * Usage:
 *   #[EloquentModel(
 *       model:       User::class,
 *       connection:  'mysql',        // optional, uses default if omitted
 *   )]
 *   class UserSchema { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class EloquentModel
{
    public function __construct(
        public readonly string $model,
        public readonly ?string $connection = null,
    ) {}
}
