<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Defines a foreign key constraint on a column.
 *
 * Usage:
 *   #[Column(type: 'unsignedBigInteger')]
 *   #[ForeignKey(references: 'id', on: 'users', onDelete: 'cascade')]
 *   public int $user_id;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKey
{
    public function __construct(
        public readonly string $references = 'id',
        public readonly string $on = '',
        public readonly string $onDelete = 'restrict',  // restrict | cascade | set null | no action
        public readonly string $onUpdate = 'restrict',
    ) {}
}
