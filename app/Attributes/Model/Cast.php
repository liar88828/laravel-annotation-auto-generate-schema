<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Defines the Eloquent cast for a property ($casts).
 *
 * Built-in cast strings: 'integer', 'float', 'double', 'decimal:<digits>',
 * 'boolean', 'array', 'object', 'collection', 'date', 'datetime',
 * 'immutable_date', 'immutable_datetime', 'encrypted', 'hashed', etc.
 *
 * You can also pass a fully-qualified cast class:
 *   #[Cast(AsStringable::class)]
 *   #[Cast('encrypted:array')]
 *
 * Usage:
 *   #[Cast('boolean')]
 *   public bool $is_active;
 *
 *   #[Cast('datetime:Y-m-d')]
 *   public Carbon $published_at;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Cast
{
    public function __construct(
        public readonly string $as,
    ) {}
}
