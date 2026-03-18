<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Marks a property as the primary key column.
 *
 * Usage:
 *   #[PrimaryKey(type: 'bigIncrements')]
 *   public int $id;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PrimaryKey
{
    public function __construct(
        public readonly string $type = 'bigIncrements',  // bigIncrements | increments | uuid
        public readonly ?string $name = null,
    ) {}
}
