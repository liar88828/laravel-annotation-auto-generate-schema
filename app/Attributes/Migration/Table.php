<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Defines the database table this model maps to.
 * Applied at the class level.
 *
 * Usage:
 *   #[Table(name: 'users', timestamps: true, softDeletes: false)]
 *   class UserSchema { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public readonly string $name,
        public readonly bool $timestamps = true,
        public readonly bool $softDeletes = false,
    ) {}
}
