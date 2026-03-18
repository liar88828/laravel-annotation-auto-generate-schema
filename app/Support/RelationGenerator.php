<?php

namespace App\Support;

use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\BelongsToMany;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\HasOne;
use App\Attributes\Migration\Table;
use ReflectionClass;

/**
 * Reads relationship attributes on schema classes and:
 *
 *  1. Generates Eloquent model relationship methods (as PHP source strings)
 *  2. Generates pivot-table migrations for BelongsToMany
 *
 * Usage:
 *   // Get Eloquent method stubs to paste into your Model:
 *   $methods = RelationGenerator::eloquentMethods(UserSchema::class);
 *
 *   // Get a pivot migration file content:
 *   $migrations = RelationGenerator::pivotMigrations(UserSchema::class);
 *
 *   // Write pivot migration to disk:
 *   RelationGenerator::writePivotMigrations(UserSchema::class);
 */
class RelationGenerator
{
    private ReflectionClass $ref;

    private function __construct(private readonly string $schemaClass) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Return an array of Eloquent relationship method source strings.
     * Keys are method names, values are the full method body.
     */
    public static function eloquentMethods(string $schemaClass): array
    {
        return (new self($schemaClass))->buildEloquentMethods();
    }

    /**
     * Return pivot migration source strings keyed by pivot table name.
     */
    public static function pivotMigrations(string $schemaClass): array
    {
        return (new self($schemaClass))->buildPivotMigrations();
    }

    /**
     * Write all pivot migrations for a schema class to disk.
     * Returns list of written paths.
     */
    public static function writePivotMigrations(
        string $schemaClass,
        ?string $outputDir = null
    ): array {
        $migrations = static::pivotMigrations($schemaClass);
        $dir = $outputDir ?? database_path('migrations');
        $written = [];

        foreach ($migrations as $tableName => $content) {
            $timestamp = date('Y_m_d_His');
            $filename = "{$timestamp}_create_{$tableName}_table.php";
            $path = $dir.'/'.$filename;
            file_put_contents($path, $content);
            $written[] = $path;
            // small sleep to guarantee unique timestamps if multiple pivots
            usleep(1000);
        }

        return $written;
    }

    /**
     * Print a readable summary of all relations found in the schema class.
     */
    public static function summary(string $schemaClass): string
    {
        $ref = new ReflectionClass($schemaClass);
        $lines = ["Relations for [{$schemaClass}]:", str_repeat('─', 60)];

        foreach ($ref->getProperties() as $property) {
            foreach ([HasOne::class, HasMany::class, BelongsTo::class, BelongsToMany::class] as $attrClass) {
                $attrs = $property->getAttributes($attrClass);
                if (empty($attrs)) {
                    continue;
                }

                $rel = $attrs[0]->newInstance();
                $type = class_basename($attrClass);
                $related = class_basename($rel->related);
                $prop = $property->getName();
                $eager = $rel->eager ? ' [eager]' : '';

                $lines[] = "  {$prop}: {$type} → {$related}{$eager}";
            }
        }

        return implode(PHP_EOL, $lines);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function buildEloquentMethods(): array
    {
        $this->ref = new ReflectionClass($this->schemaClass);
        $methods = [];

        foreach ($this->ref->getProperties() as $property) {
            $name = $property->getName();

            // hasOne
            foreach ($property->getAttributes(HasOne::class) as $attr) {
                /** @var HasOne $rel */
                $rel = $attr->newInstance();
                $methods[$name] = $this->renderHasOne($name, $rel);
            }

            // hasMany
            foreach ($property->getAttributes(HasMany::class) as $attr) {
                /** @var HasMany $rel */
                $rel = $attr->newInstance();
                $methods[$name] = $this->renderHasMany($name, $rel);
            }

            // belongsTo
            foreach ($property->getAttributes(BelongsTo::class) as $attr) {
                /** @var BelongsTo $rel */
                $rel = $attr->newInstance();
                // Strip trailing _id from the method name: user_id → user
                $methodName = preg_replace('/_id$/', '', $name);
                $methods[$methodName] = $this->renderBelongsTo($methodName, $rel);
            }

            // belongsToMany
            foreach ($property->getAttributes(BelongsToMany::class) as $attr) {
                /** @var BelongsToMany $rel */
                $rel = $attr->newInstance();
                $methods[$name] = $this->renderBelongsToMany($name, $rel);
            }
        }

        return $methods;
    }

    private function renderHasOne(string $property, HasOne $rel): string
    {
        $relatedModel = $this->schemaToModel($rel->related);
        $fkArg = $rel->foreignKey ? ", '{$rel->foreignKey}'" : '';
        $lkArg = $rel->localKey ? ", '{$rel->localKey}'" : '';

        // Only append localKey arg if foreignKey is also set (positional)
        $args = $rel->foreignKey
            ? "'{$rel->foreignKey}'".($rel->localKey ? ", '{$rel->localKey}'" : '')
            : '';

        $argsStr = $args ? ", {$args}" : '';

        return <<<PHP
    public function {$property}(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return \$this->hasOne({$relatedModel}::class{$argsStr});
    }
PHP;
    }

    private function renderHasMany(string $property, HasMany $rel): string
    {
        $relatedModel = $this->schemaToModel($rel->related);
        $args = $this->buildArgs($rel->foreignKey, $rel->localKey);

        return <<<PHP
    public function {$property}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany({$relatedModel}::class{$args});
    }
PHP;
    }

    private function renderBelongsTo(string $methodName, BelongsTo $rel): string
    {
        $relatedModel = $this->schemaToModel($rel->related);
        $args = $this->buildArgs($rel->foreignKey, $rel->ownerKey);

        return <<<PHP
    public function {$methodName}(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return \$this->belongsTo({$relatedModel}::class{$args});
    }
PHP;
    }

    private function renderBelongsToMany(string $property, BelongsToMany $rel): string
    {
        $relatedModel = $this->schemaToModel($rel->related);
        $pivotTable = $rel->pivotTable ?? $this->derivePivotTable($this->schemaClass, $rel->related);
        $fpk = $rel->foreignPivotKey;
        $rpk = $rel->relatedPivotKey;

        // Build argument list only as deep as needed
        $argParts = ["'{$pivotTable}'"];
        if ($fpk || $rpk) {
            $argParts[] = $fpk ? "'{$fpk}'" : 'null';
            if ($rpk) {
                $argParts[] = "'{$rpk}'";
            }
        }

        $args = ', '.implode(', ', $argParts);

        $extra = '';
        if ($rel->withTimestamps) {
            $extra .= PHP_EOL.'            ->withTimestamps()';
        }
        if (! empty($rel->pivotColumns)) {
            $cols = implode("', '", $rel->pivotColumns);
            $extra .= PHP_EOL."            ->withPivot('{$cols}')";
        }

        return <<<PHP
    public function {$property}(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return \$this->belongsToMany({$relatedModel}::class{$args}){$extra};
    }
PHP;
    }

    // -------------------------------------------------------------------------
    // Pivot migration builder
    // -------------------------------------------------------------------------

    private function buildPivotMigrations(): array
    {
        $this->ref = new ReflectionClass($this->schemaClass);
        $result = [];

        foreach ($this->ref->getProperties() as $property) {
            foreach ($property->getAttributes(BelongsToMany::class) as $attr) {
                /** @var BelongsToMany $rel */
                $rel = $attr->newInstance();
                $pivotTable = $rel->pivotTable ?? $this->derivePivotTable($this->schemaClass, $rel->related);
                $fpk = $rel->foreignPivotKey ?? $this->deriveKey($this->schemaClass);
                $rpk = $rel->relatedPivotKey ?? $this->deriveKey($rel->related);

                $result[$pivotTable] = $this->renderPivotMigration(
                    $pivotTable, $fpk, $rpk,
                    $rel->withTimestamps, $rel->pivotColumns,
                    $this->resolveTableName($this->schemaClass),
                    $this->resolveTableName($rel->related),
                );
            }
        }

        return $result;
    }

    private function renderPivotMigration(
        string $pivotTable,
        string $fpk,
        string $rpk,
        bool $withTimestamps,
        array $extraColumns,
        string $ownerTable,
        string $relatedTable,
    ): string {
        $className = 'Create'.str_replace('_', '', ucwords($pivotTable, '_')).'Table';
        $timestamps = $withTimestamps ? PHP_EOL.'            $table->timestamps();' : '';

        $extraCols = '';
        foreach ($extraColumns as $colDef) {
            // Simple string columns for pivot extras; pass 'type:name' format
            [$type, $colName] = str_contains($colDef, ':')
                ? explode(':', $colDef, 2)
                : ['string', $colDef];
            $extraCols .= PHP_EOL."            \$table->{$type}('{$colName}')->nullable();";
        }

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$pivotTable}', function (Blueprint \$table) {
            \$table->unsignedBigInteger('{$fpk}');
            \$table->unsignedBigInteger('{$rpk}');
            \$table->primary(['{$fpk}', '{$rpk}']);
            \$table->foreign('{$fpk}')->references('id')->on('{$ownerTable}')->onDelete('cascade');
            \$table->foreign('{$rpk}')->references('id')->on('{$relatedTable}')->onDelete('cascade');{$extraCols}{$timestamps}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$pivotTable}');
    }
};
PHP;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Convert a Schema class FQCN to a Model class FQCN.
     * e.g. App\Example\UserSchema → App\Models\User
     */
    private function schemaToModel(string $schemaClass): string
    {
        $base = class_basename($schemaClass);
        $base = preg_replace('/Schema$/', '', $base);

        return "\\App\\Models\\{$base}";
    }

    private function buildArgs(?string $first, ?string $second): string
    {
        if (! $first && ! $second) {
            return '';
        }

        $parts = [$first ? "'{$first}'" : 'null'];

        if ($second) {
            $parts[] = "'{$second}'";
        }

        return ', '.implode(', ', $parts);
    }

    /**
     * Derive pivot table name: alphabetically-sorted singular table names joined by _.
     * e.g. users + roles → role_user
     */
    private function derivePivotTable(string $classA, string $classB): string
    {
        $a = strtolower(preg_replace('/Schema$/', '', class_basename($classA)));
        $b = strtolower(preg_replace('/Schema$/', '', class_basename($classB)));

        $parts = [$a, $b];
        sort($parts);

        return implode('_', $parts);
    }

    /** Derive FK key name from schema class: UserSchema → user_id */
    private function deriveKey(string $schemaClass): string
    {
        $base = strtolower(preg_replace('/Schema$/', '', class_basename($schemaClass)));

        return "{$base}_id";
    }

    private function resolveTableName(string $schemaClass): string
    {
        if (! class_exists($schemaClass)) {
            return strtolower(preg_replace('/Schema$/', '', class_basename($schemaClass))).'s';
        }

        $ref = new ReflectionClass($schemaClass);
        $attrs = $ref->getAttributes(Table::class);

        return $attrs ? $attrs[0]->newInstance()->name
            : strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename($schemaClass)));
    }
}
