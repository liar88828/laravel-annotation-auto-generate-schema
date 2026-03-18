<?php

namespace App\Attributes\Migration;

use Attribute;

/**
 * Declares a foreign key column that is fully derived from a schema class.
 *
 * Replaces the verbose combination of:
 *   #[Column(type: 'unsignedBigInteger', nullable: false, index: true)]
 *   #[ForeignKey(references: 'id', on: 'products', onDelete: 'cascade')]
 *   #[BelongsTo(related: ProductSchema::class)]
 *
 * With the compact:
 *   #[ForeignSchema(schema: ProductSchema::class, onDelete: 'cascade')]
 *
 * The table name, column type, and referenced column are all resolved
 * automatically from the related schema's #[Table] and #[PrimaryKey] attributes.
 *
 * Usage:
 *   #[ForeignSchema(schema: ProductSchema::class)]
 *   public int $product_id;
 *
 *   #[ForeignSchema(schema: ProductSchema::class, onDelete: 'cascade', nullable: true)]
 *   public ?int $product_id = null;
 *
 * Equivalent to:
 *   #[Column(type: 'unsignedBigInteger', nullable: false, index: true)]
 *   #[ForeignKey(references: 'id', on: 'products', onDelete: 'cascade')]
 *   #[BelongsTo(related: ProductSchema::class)]
 *   public int $product_id;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignSchema
{
    public function __construct(
        /** The related schema class — table name + PK type are auto-resolved from it */
        public readonly string $schema,

        /** ON DELETE action: restrict | cascade | set null | no action */
        public readonly string $onDelete = 'restrict',

        /** ON UPDATE action */
        public readonly string $onUpdate = 'restrict',

        /** Allow NULL (e.g. optional relation) */
        public readonly bool $nullable = false,

        /** Override the referenced column name (default: resolved from #[PrimaryKey]) */
        public readonly ?string $references = null,

        /** Override the foreign table name (default: resolved from #[Table]) */
        public readonly ?string $on = null,

        /** Add an index on this column (default: true) */
        public readonly bool $index = true,
    ) {}
}
