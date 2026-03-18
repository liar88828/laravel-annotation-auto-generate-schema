<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Declares a many-to-many (belongsToMany) relationship and
 * describes the pivot table so MigrationGenerator can create it.
 *
 * Usage:
 *   #[BelongsToMany(
 *       related:         RoleSchema::class,
 *       pivotTable:      'role_user',
 *       foreignPivotKey: 'user_id',
 *       relatedPivotKey: 'role_id',
 *       withTimestamps:  true,
 *   )]
 *   public array $roles;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class BelongsToMany
{
    public function __construct(
        public readonly string $related,
        public readonly ?string $pivotTable = null,   // auto-derived if null
        public readonly ?string $foreignPivotKey = null,
        public readonly ?string $relatedPivotKey = null,
        public readonly bool $withTimestamps = false,
        public readonly bool $eager = false,
        /** Extra pivot columns beyond the two FK keys */
        public readonly array $pivotColumns = [],
    ) {}
}
