<?php

namespace App\Traits;

use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignKey;
use App\Attributes\Migration\ForeignSchema;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use App\Support\ForeignSchemaResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionProperty;

/**
 * RunsSchemaMigration trait
 *
 * Mix into any Laravel migration anonymous class.
 * Provide a schema() method returning the schema FQCN — no named class needed,
 * no #[Attribute] on the migration, no redeclaration conflicts.
 *
 * Usage:
 *
 *   return new class extends Migration {
 *       use RunsSchemaMigration;
 *
 *       protected function schema(): string
 *       {
 *           return \App\Schema\ProductSchema::class;
 *       }
 *   };
 */
trait RunsSchemaMigration
{
    /**
     * Return the fully-qualified schema class name.
     * Override this in the migration anonymous class.
     */
    abstract protected function schema(): string;

    // -------------------------------------------------------------------------
    // up / down — called by Laravel's migrator
    // -------------------------------------------------------------------------

    public function up(): void
    {
        $schema = $this->resolveSchema();
        $table = $this->resolveTable($schema);

        Schema::create($table->name, function (Blueprint $blueprint) use ($schema, $table) {
            $this->applyColumns($blueprint, $schema);

            if ($table->timestamps) {
                $blueprint->timestamps();
            }

            if ($table->softDeletes) {
                $blueprint->softDeletes();
            }
        });
    }

    public function down(): void
    {
        $schema = $this->resolveSchema();
        $table = $this->resolveTable($schema);

        Schema::dropIfExists($table->name);
    }

    // -------------------------------------------------------------------------
    // Column application
    // -------------------------------------------------------------------------

    private function applyColumns(Blueprint $blueprint, ReflectionClass $ref): void
    {
        foreach ($ref->getProperties() as $property) {

            // ── Primary key ───────────────────────────────────────────────
            $pkAttrs = $property->getAttributes(PrimaryKey::class);
            if (! empty($pkAttrs)) {
                /** @var PrimaryKey $pk */
                $pk = $pkAttrs[0]->newInstance();
                $col = $pk->name ?? $property->getName();

                $column = $blueprint->{$pk->type}($col);

                // uuid/ulid need explicit ->primary() — they don't auto-increment
                if (in_array($pk->type, ['uuid', 'ulid'])) {
                    $column->primary();
                }

                continue;
            }

            // ── #[ForeignSchema] — expand into column + FK ────────────────
            $fsAttrs = $property->getAttributes(ForeignSchema::class);
            if (! empty($fsAttrs)) {
                $fs = $fsAttrs[0]->newInstance();
                $spec = ForeignSchemaResolver::resolve($fs);
                $name = $property->getName();

                $col = $blueprint->{$spec['colType']}($name);
                if ($spec['nullable']) {
                    $col->nullable();
                }
                if ($spec['index']) {
                    $col->index();
                }

                $blueprint->foreign($name)
                    ->references($spec['references'])
                    ->on($spec['table'])
                    ->onDelete($spec['onDelete'])
                    ->onUpdate($spec['onUpdate']);

                continue;
            }

            // ── Regular column ────────────────────────────────────────────
            $colAttrs = $property->getAttributes(Column::class);
            if (empty($colAttrs)) {
                continue;
            }

            /** @var Column $colDef */
            $colDef = $colAttrs[0]->newInstance();
            $name = $colDef->name ?? $property->getName();

            $this->applyColumn($blueprint, $name, $colDef, $property);
        }
    }

    private function applyColumn(
        Blueprint $blueprint,
        string $name,
        Column $col,
        ReflectionProperty $property,
    ): void {
        // rememberToken() is a Blueprint macro with no arguments
        if ($col->type === 'rememberToken') {
            $blueprint->rememberToken();

            return;
        }

        // Build the base column — priority: precision/scale > length > bare
        if ($col->precision !== null) {
            $scale = $col->scale ?? 2;
            $column = $blueprint->{$col->type}($name, $col->precision, $scale);
        } elseif ($col->length !== null) {
            $column = $blueprint->{$col->type}($name, $col->length);
        } else {
            $column = $blueprint->{$col->type}($name);
        }

        if ($col->nullable) {
            $column->nullable();
        }

        if ($col->default !== '__UNSET__') {
            $column->default($col->default);
        }

        if ($col->unique) {
            $column->unique();
        }

        if ($col->primary) {
            $column->primary();
        }

        if ($col->index && ! $col->unique && ! $col->primary) {
            $column->index();
        }

        if ($col->comment !== null) {
            $column->comment($col->comment);
        }

        // ── Foreign key constraint ────────────────────────────────────────
        $fkAttrs = $property->getAttributes(ForeignKey::class);
        if (! empty($fkAttrs)) {
            /** @var ForeignKey $fk */
            $fk = $fkAttrs[0]->newInstance();
            $blueprint->foreign($name)
                ->references($fk->references)
                ->on($fk->on)
                ->onDelete($fk->onDelete)
                ->onUpdate($fk->onUpdate);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveSchema(): ReflectionClass
    {
        // Calls the schema() method defined on the anonymous migration class
        $schemaClass = $this->schema();

        if (! class_exists($schemaClass)) {
            throw new \RuntimeException(
                "Schema class [{$schemaClass}] not found."
            );
        }

        return new ReflectionClass($schemaClass);
    }

    private function resolveTable(ReflectionClass $schemaRef): Table
    {
        $attrs = $schemaRef->getAttributes(Table::class);

        if (empty($attrs)) {
            throw new \RuntimeException(
                'Schema ['.$schemaRef->getName().'] is missing the #[Table] attribute.'
            );
        }

        return $attrs[0]->newInstance();
    }
}
