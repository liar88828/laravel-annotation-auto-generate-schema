<?php

namespace App\Attributes\Model;

use Attribute;

/**
 * Links an Eloquent Model to its schema class.
 *
 * Applied at the class level on the Model itself.
 * The HasSchema trait reads this to provide schema-aware helpers.
 *
 * Usage:
 *   #[UsesSchema(UserSchema::class)]
 *   class User extends Model
 *   {
 *       use HasSchema;
 *   }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UsesSchema
{
    public function __construct(
        public readonly string $schema,
    ) {}
}
