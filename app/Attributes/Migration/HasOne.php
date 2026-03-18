<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Declares a one-to-one (hasOne) relationship.
 *
 * Usage:
 *   #[HasOne(related: ProfileSchema::class, foreignKey: 'user_id')]
 *   public ProfileSchema $profile;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasOne
{
    public function __construct(
        public readonly string $related,
        public readonly ?string $foreignKey = null,
        public readonly ?string $localKey = null,
        public readonly bool $eager = false,
    ) {}
}
