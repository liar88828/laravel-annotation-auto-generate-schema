<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Declares a many-to-one (belongsTo) relationship.
 *
 * The property should also carry a #[Column] + #[ForeignKey] attribute
 * for the actual FK column definition. BelongsTo purely expresses
 * the Eloquent relationship side.
 *
 * Usage:
 *   #[Column(type: 'unsignedBigInteger', nullable: false)]
 *   #[ForeignKey(references: 'id', on: 'users', onDelete: 'cascade')]
 *   #[BelongsTo(related: UserSchema::class)]
 *   public int $user_id;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsTo
{
    public function __construct(
        public readonly string $related,
        public readonly ?string $foreignKey = null,   // defaults to {relation}_id
        public readonly ?string $ownerKey = null,   // defaults to 'id'
        public readonly bool $eager = false,
    ) {}
}
