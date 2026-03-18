<?php

namespace App\Support;

use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\BelongsToMany;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\HasOne;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use App\Attributes\Model\Appended;
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Model\Hidden;
use App\Attributes\Model\UsesSchema;
use ReflectionClass;
use ReflectionProperty;

/**
 * Generates a complete Eloquent Model PHP file from a schema class.
 *
 * Reads:
 *   #[EloquentModel]  → model class name + connection
 *   #[Table]          → $table, $timestamps, soft deletes
 *   #[PrimaryKey]     → $primaryKey, $incrementing, $keyType
 *   #[Fillable]       → $fillable[]
 *   #[Hidden]         → $hidden[]
 *   #[Cast]           → $casts[]
 *   #[Appended]       → $appends[]
 *   #[HasOne]         → hasOne() method
 *   #[HasMany]        → hasMany() method
 *   #[BelongsTo]      → belongsTo() method
 *   #[BelongsToMany]  → belongsToMany() method
 *
 * Usage:
 *   $source = ModelGenerator::generate(UserSchema::class);
 *
 *   // Write to app/Models/User.php
 *   ModelGenerator::write(UserSchema::class);
 */
class ModelGenerator
{
    private ReflectionClass $ref;

    private function __construct(private readonly string $schemaClass) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public static function generate(string $schemaClass): string
    {
        return (new self($schemaClass))->build();
    }

    /**
     * Write the generated model to disk.
     * Returns the path written.
     */
    public static function write(string $schemaClass, ?string $outputDir = null): string
    {
        $source = static::generate($schemaClass);
        $ref = new ReflectionClass($schemaClass);
        $modelFq = static::resolveModelFqcn($ref);
        $base = class_basename($modelFq);
        $dir = $outputDir ?? app_path('Models');
        $path = $dir.'/'.$base.'.php';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $source);

        return $path;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function build(): string
    {
        $this->ref = new ReflectionClass($this->schemaClass);

        // ── Class-level attributes ────────────────────────────────────────

        $modelAttr = $this->firstAttr(EloquentModel::class);
        $tableAttr = $this->firstAttr(Table::class);

        if ($modelAttr === null) {
            throw new \RuntimeException(
                "Schema [{$this->schemaClass}] is missing the #[EloquentModel] attribute."
            );
        }

        $modelFqcn = $modelAttr->model;

        // ── Guard: model FQCN must not resolve inside the schema namespace ─
        // This happens when the schema file is missing `use App\Models\Xyz`
        // and PHP resolves Xyz::class relative to the schema's own namespace.
        $schemaNamespace = $this->ref->getNamespaceName();
        $modelNamespace = implode('\\', array_slice(explode('\\', $modelFqcn), 0, -1));

        if ($modelNamespace === $schemaNamespace) {
            $modelBase = class_basename($modelFqcn);
            // Strip trailing 'Schema' suffix: ProductSchema → Product
            $modelBaseName = preg_replace('/Schema$/', '', $modelBase);
            $expectedModel = "App\\Models\\{$modelBaseName}";
            $schemaFile = $this->ref->getFileName();

            throw new \RuntimeException(
                implode(PHP_EOL, [
                    "The model class [{$modelFqcn}] resolved inside the schema namespace.",
                    "This means your schema file is missing the correct 'use' import.",
                    '',
                    "In [{$schemaFile}] add:",
                    "  use {$expectedModel};",
                    '',
                    'Then update the attribute:',
                    "  #[EloquentModel(model: {$modelBaseName}::class)]",
                ])
            );
        }
        $modelBase = class_basename($modelFqcn);
        $namespace = implode('\\', array_slice(explode('\\', $modelFqcn), 0, -1));

        // ── Collect property-level data ───────────────────────────────────

        $fillable = [];
        $hidden = [];
        $casts = [];
        $appends = [];
        $relations = [];
        $primaryKey = 'id';
        $keyType = 'int';
        $incrementing = true;
        $usesSoftDeletes = $tableAttr?->softDeletes ?? false;

        foreach ($this->ref->getProperties() as $prop) {
            $name = $prop->getName();

            // Primary key detection
            $pkAttr = $this->firstPropAttr($prop, PrimaryKey::class);
            if ($pkAttr !== null) {
                $primaryKey = $pkAttr->name ?? $name;
                if (in_array($pkAttr->type, ['uuid', 'ulid'])) {
                    $keyType = 'string';
                    $incrementing = false;
                }

                continue; // PK is never fillable
            }

            // Fillable
            if ($this->hasPropAttr($prop, Fillable::class)) {
                $fillable[] = $name;
            }

            // Hidden
            if ($this->hasPropAttr($prop, Hidden::class)) {
                $hidden[] = $name;
            }

            // Cast
            $castAttr = $this->firstPropAttr($prop, Cast::class);
            if ($castAttr !== null) {
                $casts[$name] = $castAttr->as;
            }

            // Appended
            if ($this->hasPropAttr($prop, Appended::class)) {
                $appends[] = $name;
            }

            // Relations
            foreach ([HasOne::class, HasMany::class, BelongsTo::class, BelongsToMany::class] as $relClass) {
                $relAttr = $this->firstPropAttr($prop, $relClass);
                if ($relAttr !== null) {
                    $relations[] = [$name, $relClass, $relAttr];
                }
            }
        }

        // ── Build source ──────────────────────────────────────────────────

        $uses = $this->buildUses($usesSoftDeletes, $relations);
        $classProps = $this->buildClassProperties(
            $tableAttr, $primaryKey, $keyType, $incrementing,
            $fillable, $hidden, $casts, $appends,
            $modelAttr->connection
        );
        $relationMethods = $this->buildRelationMethods($relations);

        $traits = array_filter(['HasFactory', 'HasSchema', $usesSoftDeletes ? 'SoftDeletes' : null]);
        $traitLine = '    use '.implode(', ', $traits).';';
        $schemaShort = class_basename($this->schemaClass);

        return "<?php\n\nnamespace {$namespace};\n\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse App\\Attributes\\Model\\UsesSchema;\nuse App\\Traits\\HasSchema;\n{$uses}\n#[UsesSchema({$schemaShort}::class)]\nclass {$modelBase} extends Model\n{\n{$traitLine}\n\n{$classProps}\n{$relationMethods}\n}\n";
    }

    // ── Class property block ──────────────────────────────────────────────

    private function buildClassProperties(
        ?object $tableAttr,
        string $primaryKey,
        string $keyType,
        bool $incrementing,
        array $fillable,
        array $hidden,
        array $casts,
        array $appends,
        ?string $connection,
    ): string {
        $lines = [];

        if ($connection !== null) {
            $lines[] = "    protected \$connection = '{$connection}';";
        }

        if ($tableAttr !== null) {
            $lines[] = "    protected \$table = '{$tableAttr->name}';";
        }

        if ($primaryKey !== 'id') {
            $lines[] = "    protected \$primaryKey = '{$primaryKey}';";
        }

        if ($keyType !== 'int') {
            $lines[] = "    protected \$keyType = '{$keyType}';";
        }

        if (! $incrementing) {
            $lines[] = '    public $incrementing = false;';
        }

        if ($tableAttr !== null && ! $tableAttr->timestamps) {
            $lines[] = '    public $timestamps = false;';
        }

        if (! empty($fillable)) {
            $lines[] = $this->renderArrayProp('fillable', $fillable);
        }

        if (! empty($hidden)) {
            $lines[] = $this->renderArrayProp('hidden', $hidden);
        }

        if (! empty($appends)) {
            $lines[] = $this->renderArrayProp('appends', $appends);
        }

        if (! empty($casts)) {
            $lines[] = $this->renderCasts($casts);
        }

        return implode(PHP_EOL.PHP_EOL, $lines);
    }

    private function renderArrayProp(string $prop, array $items): string
    {
        $entries = implode(','.PHP_EOL.'        ', array_map(
            fn ($v) => "'{$v}'",
            $items
        ));

        return "    protected \${$prop} = [".PHP_EOL
            ."        {$entries},".PHP_EOL
            .'    ];';
    }

    private function renderCasts(array $casts): string
    {
        $lines = [];
        foreach ($casts as $field => $type) {
            // Distinguish class-based casts from string casts
            $value = class_exists($type)
                ? "{$type}::class"
                : "'{$type}'";
            $lines[] = "        '{$field}' => {$value},";
        }
        $body = implode(PHP_EOL, $lines);

        return '    protected $casts = ['.PHP_EOL.$body.PHP_EOL.'    ];';
    }

    // ── Relation methods ──────────────────────────────────────────────────

    private function buildRelationMethods(array $relations): string
    {
        $methods = [];

        foreach ($relations as [$propName, $relClass, $rel]) {
            $methods[] = match ($relClass) {
                HasOne::class => $this->renderHasOne($propName, $rel),
                HasMany::class => $this->renderHasMany($propName, $rel),
                BelongsTo::class => $this->renderBelongsTo($propName, $rel),
                BelongsToMany::class => $this->renderBelongsToMany($propName, $rel),
                default => '',
            };
        }

        return implode(PHP_EOL.PHP_EOL, array_filter($methods));
    }

    private function renderHasOne(string $prop, HasOne $rel): string
    {
        $model = $this->schemaToModel($rel->related);
        $args = $this->args($rel->foreignKey, $rel->localKey);
        $ret = '\Illuminate\Database\Eloquent\Relations\HasOne';

        return "    public function {$prop}(): {$ret}\n    {\n        return \$this->hasOne({$model}::class{$args});\n    }";
    }

    private function renderHasMany(string $prop, HasMany $rel): string
    {
        $model = $this->schemaToModel($rel->related);
        $args = $this->args($rel->foreignKey, $rel->localKey);
        $ret = '\Illuminate\Database\Eloquent\Relations\HasMany';

        return "    public function {$prop}(): {$ret}\n    {\n        return \$this->hasMany({$model}::class{$args});\n    }";
    }

    private function renderBelongsTo(string $prop, BelongsTo $rel): string
    {
        // strip _id suffix for the method name: department_id → department
        $method = preg_replace('/_id$/', '', $prop);
        $model = $this->schemaToModel($rel->related);
        $args = $this->args($rel->foreignKey, $rel->ownerKey);
        $ret = '\Illuminate\Database\Eloquent\Relations\BelongsTo';

        return "    public function {$method}(): {$ret}\n    {\n        return \$this->belongsTo({$model}::class{$args});\n    }";
    }

    private function renderBelongsToMany(string $prop, BelongsToMany $rel): string
    {
        $model = $this->schemaToModel($rel->related);
        $pivotTable = $rel->pivotTable ?? $this->derivePivot($this->schemaClass, $rel->related);
        $fpk = $rel->foreignPivotKey;
        $rpk = $rel->relatedPivotKey;

        $argParts = ["'{$pivotTable}'"];
        if ($fpk || $rpk) {
            $argParts[] = $fpk ? "'{$fpk}'" : 'null';
            if ($rpk) {
                $argParts[] = "'{$rpk}'";
            }
        }
        $args = ', '.implode(', ', $argParts);

        $chain = '';
        if ($rel->withTimestamps) {
            $chain .= "\n            ->withTimestamps()";
        }
        if (! empty($rel->pivotColumns)) {
            $cols = implode("', '", array_map(
                fn ($c) => explode(':', $c)[1] ?? $c,
                $rel->pivotColumns
            ));
            $chain .= "\n            ->withPivot('{$cols}')";
        }

        $ret = '\Illuminate\Database\Eloquent\Relations\BelongsToMany';

        return "    public function {$prop}(): {$ret}\n    {\n        return \$this->belongsToMany({$model}::class{$args}){$chain};\n    }";
    }

    // ── use statements ────────────────────────────────────────────────────

    private function buildUses(bool $softDeletes, array $relations): string
    {
        $uses = [];

        if ($softDeletes) {
            $uses[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
        }

        // Import the schema class itself (needed for #[UsesSchema(UserSchema::class)])
        $uses[] = 'use '.ltrim($this->schemaClass, '\\').';';

        // Collect related model classes
        $models = [];
        foreach ($relations as [, $relClass, $rel]) {
            $modelFqcn = ltrim($this->schemaToModel($rel->related), '\\');
            if (! in_array($modelFqcn, $models)) {
                $models[] = $modelFqcn;
            }
        }

        foreach ($models as $fqcn) {
            $uses[] = "use {$fqcn};";
        }

        return implode(PHP_EOL, array_unique($uses));
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function firstAttr(string $attrClass): ?object
    {
        $attrs = $this->ref->getAttributes($attrClass);

        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private function firstPropAttr(ReflectionProperty $prop, string $attrClass): ?object
    {
        $attrs = $prop->getAttributes($attrClass);

        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private function hasPropAttr(ReflectionProperty $prop, string $attrClass): bool
    {
        return ! empty($prop->getAttributes($attrClass));
    }

    private function args(?string $first, ?string $second = null): string
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

    private function schemaToModel(string $schemaClass): string
    {
        $base = preg_replace('/Schema$/', '', class_basename($schemaClass));

        return "\\App\\Models\\{$base}";
    }

    private function derivePivot(string $a, string $b): string
    {
        $parts = [
            strtolower(preg_replace('/Schema$/', '', class_basename($a))),
            strtolower(preg_replace('/Schema$/', '', class_basename($b))),
        ];
        sort($parts);

        return implode('_', $parts);
    }

    private static function resolveModelFqcn(ReflectionClass $ref): string
    {
        $attrs = $ref->getAttributes(EloquentModel::class);
        if ($attrs) {
            return $attrs[0]->newInstance()->model;
        }
        $base = preg_replace('/Schema$/', '', $ref->getShortName());

        return "App\\Models\\{$base}";
    }
}
