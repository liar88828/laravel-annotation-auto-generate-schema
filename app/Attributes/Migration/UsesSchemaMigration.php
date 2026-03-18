<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Links a standard Laravel migration class to a schema class.
 *
 * Instead of hand-writing Schema::create() calls, the migration reads the
 * #[Table], #[PrimaryKey], #[Column], and #[ForeignKey] annotations from the
 * linked schema at runtime via the RunsSchemaMigration trait.
 *
 * Usage:
 *
 *   #[UsesSchemaMigration(UserSchema::class)]
 *   return new class extends Migration {
 *       use RunsSchemaMigration;
 *   };
 *
 * The trait provides up() and down() automatically.
 * You do NOT need to write them yourself.
 *
 * Works with:
 *   - php artisan migrate
 *   - RefreshDatabase in unit tests (no generated file needed)
 *   - php artisan migrate:rollback
 *   - php artisan migrate:fresh
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UsesSchemaMigration
{
    public function __construct(
        public readonly string $schema,
    ) {}
}
