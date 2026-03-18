<?php

namespace App\Support;

use App\Attributes\Migration\ForeignSchema;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use ReflectionClass;

/**
 * Resolves a #[ForeignSchema] attribute into concrete column specs.
 *
 * Given:
 *   #[ForeignSchema(schema: ProductSchema::class, onDelete: 'cascade')]
 *   public int $product_id;
 *
 * Returns:
 *   colType    = 'unsignedBigInteger'   (from ProductSchema's #[PrimaryKey] type)
 *   table      = 'products'             (from ProductSchema's #[Table])
 *   references = 'id'                   (from ProductSchema's #[PrimaryKey] name)
 *   onDelete   = 'cascade'
 *   nullable   = false
 *   index      = true
 */
class ForeignSchemaResolver
{
    /**
     * Resolve a ForeignSchema attribute into a concrete spec array.
     *
     * Returns null if the schema has no #[Table] or #[PrimaryKey].
     */
    public static function resolve(ForeignSchema $attr): array
    {
        $ref = new ReflectionClass($attr->schema);

        // ── Table name ─────────────────────────────────────────────────────
        $table = $attr->on;
        if ($table === null) {
            $tableAttrs = $ref->getAttributes(Table::class);
            $table = $tableAttrs
                ? $tableAttrs[0]->newInstance()->name
                : strtolower(preg_replace('/Schema$/', '', $ref->getShortName())).'s';
        }

        // ── Primary key column + type ──────────────────────────────────────
        $pkName = 'id';
        $pkBpType = 'bigIncrements'; // blueprint type
        $colType = 'unsignedBigInteger'; // column type for FK

        foreach ($ref->getProperties() as $prop) {
            $pkAttrs = $prop->getAttributes(PrimaryKey::class);
            if (! $pkAttrs) {
                continue;
            }

            /** @var PrimaryKey $pk */
            $pk = $pkAttrs[0]->newInstance();
            $pkName = $pk->name ?? $prop->getName();

            // Map the PK blueprint type to the corresponding FK column type
            $colType = self::pkTypeToFkColType($pk->type);
            break;
        }

        $references = $attr->references ?? $pkName;

        return [
            'colType' => $colType,
            'table' => $table,
            'references' => $references,
            'onDelete' => $attr->onDelete,
            'onUpdate' => $attr->onUpdate,
            'nullable' => $attr->nullable,
            'index' => $attr->index,
        ];
    }

    /**
     * Maps a #[PrimaryKey] blueprint type to the appropriate FK column type.
     *
     *   bigIncrements  → unsignedBigInteger
     *   increments     → unsignedInteger
     *   uuid           → uuid
     *   ulid           → string (ulid stored as char(26))
     */
    private static function pkTypeToFkColType(string $pkType): string
    {
        return match ($pkType) {
            'increments', 'smallIncrements' => 'unsignedInteger',
            'mediumIncrements' => 'unsignedMediumInteger',
            'tinyIncrements' => 'unsignedTinyInteger',
            'uuid' => 'uuid',
            'ulid' => 'char',   // 26 chars
            default => 'unsignedBigInteger',
        };
    }
}
