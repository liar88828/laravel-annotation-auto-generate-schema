<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Declares a one-to-many (hasMany) relationship.
 *
 * This is a schema-level annotation for documentation and
 * MigrationGenerator awareness. Laravel Eloquent relations
 * are generated automatically via RelationGenerator.
 *
 * Usage:
 *   #[HasMany(related: PostSchema::class, foreignKey: 'user_id')]
 *   public array $posts;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany
{
    public function __construct(
        public readonly string $related,
        public readonly ?string $foreignKey = null,   // defaults to {ownerKey}_id
        public readonly ?string $localKey = null,   // defaults to 'id'
        public readonly bool $eager = false,  // add to $with automatically
    ) {}
}
