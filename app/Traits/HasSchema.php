<?php

namespace App\Traits;

use App\Attributes\Migration\BelongsTo as BelongsToAttr;
use App\Attributes\Migration\BelongsToMany as BelongsToManyAttr;
use App\Attributes\Migration\ForeignSchema as ForeignSchemaAttr;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\HasOne;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use App\Attributes\Model\Appended;
use App\Attributes\Model\Cast;
use App\Attributes\Model\Fillable;
use App\Attributes\Model\Hidden;
use App\Attributes\Model\UsesSchema;
use App\Support\ForeignSchemaResolver;
use App\Support\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use ReflectionClass;

/**
 * HasSchema trait
 *
 * Attach to any Eloquent Model that carries #[UsesSchema(...)].
 * Everything — $table, $primaryKey, $fillable, $hidden, $casts, $appends,
 * SoftDeletes, and all Eloquent relation methods — is resolved automatically
 * from the linked schema class at boot time.
 *
 * Minimal usage:
 *
 *   #[UsesSchema(UserSchema::class)]
 *   class User extends Model
 *   {
 *       use HasSchema;
 *   }
 *
 * That is the entire model. No $fillable, no $casts, no relation methods needed.
 * Add custom scopes, accessors, or boot hooks only when business logic requires it.
 */
trait HasSchema
{
    // -------------------------------------------------------------------------
    // ── Static registry — persists across all instances of all model classes ──
    private static array $pkConfigRegistry = [];

    // -------------------------------------------------------------------------
    // Boot — runs once per model class
    // -------------------------------------------------------------------------

    public static function bootHasSchema(): void
    {
        $schema = static::resolveSchemaClass();

        if ($schema === null) {
            return;
        }

        $schemaRef = new ReflectionClass($schema);
        $modelRef = new ReflectionClass(static::class);

        static::applyTableFromSchema($schemaRef);
        static::applyPrimaryKeyFromSchema($schemaRef);
        static::applySoftDeletesFromSchema($schemaRef);
        static::applyFillableFromSchema($schemaRef, $modelRef);
        static::applyHiddenFromSchema($schemaRef, $modelRef);
        static::applyCastsFromSchema($schemaRef);
        static::applyAppendsFromSchema($schemaRef);

        // Auto-generate UUID/ULID primary keys on creating
        $pkConfig = static::$pkConfigRegistry[static::class] ?? null;
        if ($pkConfig && ! $pkConfig['incrementing']) {
            $pkName = $pkConfig['primaryKey'];
            $pkType = $pkConfig['type'];

            static::creating(function ($model) use ($pkName, $pkType) {
                if (empty($model->{$pkName})) {
                    $model->{$pkName} = match ($pkType) {
                        'ulid' => (string) Str::ulid(),
                        default => (string) Str::uuid(),
                    };
                }
            });
        }
    }

    /**
     * initializeHasSchema runs on every new model instance.
     * Applies primary key config so $primaryKey, $incrementing, $keyType
     * are always correct regardless of when the instance is created.
     */
    public function initializeHasSchema(): void
    {
        $config = static::$pkConfigRegistry[static::class] ?? null;

        if ($config !== null) {
            $this->primaryKey = $config['primaryKey'];
            $this->incrementing = $config['incrementing'];
            $this->keyType = $config['keyType'];
        }

        $this->applyFillableToInstance();
        $this->applyCastsToInstance();  // ← add this
    }

    private function applyCastsToInstance(): void
    {
        $schemaClass = static::resolveSchemaClass();
        if ($schemaClass === null) {
            return;
        }

        $ref = new ReflectionClass($schemaClass);
        $casts = [];

        foreach ($ref->getProperties() as $prop) {
            $attrs = $prop->getAttributes(Cast::class);
            if ($attrs) {
                $casts[$prop->getName()] = $attrs[0]->newInstance()->as;
            }
        }

        if (! empty($casts)) {
            // Merge schema casts with any casts already on the instance
            // (model-level $casts take precedence via array_merge order)
            $this->casts = array_merge($casts, $this->casts ?? []);
        }
    }

    private function applyFillableToInstance(): void
    {
        $schemaClass = static::resolveSchemaClass();
        if ($schemaClass === null) {
            return;
        }

        $schemaRef = new ReflectionClass($schemaClass);
        $modelRef = new ReflectionClass(static::class);
        $fillable = [];

        foreach ($schemaRef->getProperties() as $prop) {
            if ($prop->getAttributes(Fillable::class)) {
                $fillable[] = $prop->getName();
            }
        }

        foreach ($modelRef->getProperties() as $prop) {
            if ($prop->getAttributes(Fillable::class)) {
                $fillable[] = $prop->getName();
            }
        }

        if (! empty($fillable)) {
            $this->fillable = array_values(array_unique($fillable));
        }
    }
    // -------------------------------------------------------------------------
    // Magic relation resolver
    // Intercepts calls to relation method names declared via schema annotations
    // and returns the correct Eloquent relation — no explicit method needed.
    // -------------------------------------------------------------------------

    public function __call($method, $parameters)
    {
        $relation = $this->resolveSchemaRelation($method);

        if ($relation !== null) {
            return $relation;
        }

        // If this is an accessor call (getXxxAttribute) triggered by $appends
        // but the method doesn't exist on the model, return null silently
        // instead of throwing BadMethodCallException.
        if (str_starts_with($method, 'get') && str_ends_with($method, 'Attribute')) {
            return null;
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Attempt to resolve $method as a relation defined in the schema.
     * Returns the Eloquent relation instance or null if not found.
     */
    private function resolveSchemaRelation(string $method): mixed
    {
        $schemaClass = static::resolveSchemaClass();
        if ($schemaClass === null) {
            return null;
        }

        $ref = new ReflectionClass($schemaClass);

        foreach ($ref->getProperties() as $prop) {
            $propName = $prop->getName();

            // ── HasOne ────────────────────────────────────────────────────
            foreach ($prop->getAttributes(HasOne::class) as $attr) {
                if ($propName !== $method) {
                    continue;
                }
                /** @var HasOne $rel */
                $rel = $attr->newInstance();
                $model = $this->schemaRelatedModel($rel->related);

                return $rel->foreignKey
                    ? $this->hasOne($model, $rel->foreignKey, $rel->localKey ?? 'id')
                    : $this->hasOne($model);
            }

            // ── HasMany ───────────────────────────────────────────────────
            foreach ($prop->getAttributes(HasMany::class) as $attr) {
                if ($propName !== $method) {
                    continue;
                }
                /** @var HasMany $rel */
                $rel = $attr->newInstance();
                $model = $this->schemaRelatedModel($rel->related);

                return $rel->foreignKey
                    ? $this->hasMany($model, $rel->foreignKey, $rel->localKey ?? 'id')
                    : $this->hasMany($model);
            }

            // ── BelongsTo ─────────────────────────────────────────────────
            // Convention: property name is the FK column (e.g. department_id),
            // the relation method name is that without _id (e.g. department).
            foreach ($prop->getAttributes(BelongsToAttr::class) as $attr) {
                $methodName = preg_replace('/_id$/', '', $propName);
                if ($methodName !== $method) {
                    continue;
                }
                /** @var BelongsToAttr $rel */
                $rel = $attr->newInstance();
                $model = $this->schemaRelatedModel($rel->related);

                return $rel->foreignKey
                    ? $this->belongsTo($model, $rel->foreignKey, $rel->ownerKey ?? 'id')
                    : $this->belongsTo($model);
            }

            // ── ForeignSchema → implicit BelongsTo ────────────────────────
            // #[ForeignSchema(schema: ProductSchema::class)] on $product_id
            // → method name 'product' → belongsTo(Product::class)
            foreach ($prop->getAttributes(ForeignSchemaAttr::class) as $attr) {
                $methodName = preg_replace('/_id$/', '', $propName);
                if ($methodName !== $method) {
                    continue;
                }
                $fs = $attr->newInstance();
                $model = $this->schemaRelatedModel($fs->schema);
                $spec = ForeignSchemaResolver::resolve($fs);

                return $this->belongsTo($model, $propName, $spec['references']);
            }

            // ── BelongsToMany ─────────────────────────────────────────────
            foreach ($prop->getAttributes(BelongsToManyAttr::class) as $attr) {
                if ($propName !== $method) {
                    continue;
                }
                /** @var BelongsToManyAttr $rel */
                $rel = $attr->newInstance();
                $model = $this->schemaRelatedModel($rel->related);
                $pivotTable = $rel->pivotTable ?? $this->derivePivotTable(
                    static::resolveSchemaClass(), $rel->related
                );

                $query = $this->belongsToMany(
                    $model,
                    $pivotTable,
                    $rel->foreignPivotKey,
                    $rel->relatedPivotKey,
                );

                if ($rel->withTimestamps) {
                    $query = $query->withTimestamps();
                }

                if (! empty($rel->pivotColumns)) {
                    $cols = array_map(
                        fn ($c) => explode(':', $c)[1] ?? $c,
                        $rel->pivotColumns
                    );
                    $query = $query->withPivot($cols);
                }

                return $query;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Schema resolution
    // -------------------------------------------------------------------------

    public static function resolveSchemaClass(): ?string
    {
        $ref = new ReflectionClass(static::class);
        $attrs = $ref->getAttributes(UsesSchema::class);

        return $attrs ? $attrs[0]->newInstance()->schema : null;
    }

    public static function schemaReflection(): ?ReflectionClass
    {
        $class = static::resolveSchemaClass();

        return $class ? new ReflectionClass($class) : null;
    }

    // -------------------------------------------------------------------------
    // Boot helpers
    // -------------------------------------------------------------------------

    private static function applyTableFromSchema(ReflectionClass $ref): void
    {
        $attrs = $ref->getAttributes(Table::class);
        if (! $attrs) {
            return;
        }

        /** @var Table $table */
        $table = $attrs[0]->newInstance();
        $instance = new static;
        // Always apply — don't skip if already set on the model
        $instance->table = $table->name;
    }

    private static function applyPrimaryKeyFromSchema(ReflectionClass $ref): void
    {
        foreach ($ref->getProperties() as $prop) {
            $pkAttrs = $prop->getAttributes(PrimaryKey::class);
            if (! $pkAttrs) {
                continue;
            }

            /** @var PrimaryKey $pk */
            $pk = $pkAttrs[0]->newInstance();
            $isUuidLike = in_array($pk->type, ['uuid', 'ulid']);

            // Store in the static registry — keyed by model class (late static binding)
            static::$pkConfigRegistry[static::class] = [
                'primaryKey' => $pk->name ?? $prop->getName(),
                'incrementing' => ! $isUuidLike,
                'keyType' => $isUuidLike ? 'string' : 'int',
                'type' => $pk->type,
            ];
            break;
        }
    }

    /**
     * If #[Table(softDeletes: true)] is set on the schema, mix SoftDeletes
     * into the model class at runtime so the model never needs to declare it.
     */
    private static function applySoftDeletesFromSchema(ReflectionClass $ref): void
    {
        $attrs = $ref->getAttributes(Table::class);
        if (! $attrs) {
            return;
        }

        /** @var Table $table */
        $table = $attrs[0]->newInstance();

        if (! $table->softDeletes) {
            return;
        }

        if (! in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::bootSoftDeletesIfNeeded();
        }
    }

    private static function bootSoftDeletesIfNeeded(): void
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::deleting(function ($model) {
            if (! $model->isForceDeleting()) {
                $model->{$model->getDeletedAtColumn()} = $model->freshTimestamp();
                $model->save();

                return false;
            }
        });

        if (! method_exists(static::class, 'restore')) {
            Macroable::mixin(new class
            {
                public function restore()
                {
                    return function () {
                        $this->{$this->getDeletedAtColumn()} = null;

                        return $this->save();
                    };
                }

                public function trashed()
                {
                    return function () {
                        return ! is_null($this->{$this->getDeletedAtColumn()});
                    };
                }

                public function getDeletedAtColumn()
                {
                    return function () {
                        return 'deleted_at';
                    };
                }
            });
        }
    }

    private static function applyFillableFromSchema(
        ReflectionClass $schemaRef,
        ?ReflectionClass $modelRef = null,
    ): void {
        // Fillable is now applied per-instance in initializeHasSchema()
        // to ensure it's set before Builder::newModelInstance() calls fill().
        // This boot-time method is kept as a no-op for compatibility.
        //        $fillable = [];
        //
        //        foreach ($schemaRef->getProperties() as $prop) {
        //            if ($prop->getAttributes(Fillable::class)) {
        //                $fillable[] = $prop->getName();
        //            }
        //        }
        //
        //        // Only merge model-level @Fillable annotations, NOT the model's $fillable array.
        //        // The schema is the single source of truth for fillable fields.
        //        if ($modelRef) {
        //            foreach ($modelRef->getProperties() as $prop) {
        //                if ($prop->getAttributes(Fillable::class)) {
        //                    $fillable[] = $prop->getName();
        //                }
        //            }
        //        }
        //
        //        if (! empty($fillable)) {
        //            $instance = new static;
        //            // Override completely — don't merge with model's hardcoded $fillable
        //            $instance->fillable = array_values(array_unique($fillable));
        //        }
    }

    private static function applyHiddenFromSchema(
        ReflectionClass $schemaRef,
        ?ReflectionClass $modelRef = null,
    ): void {
        $hidden = [];

        foreach ($schemaRef->getProperties() as $prop) {
            if ($prop->getAttributes(Hidden::class)) {
                $hidden[] = $prop->getName();
            }
        }

        if ($modelRef) {
            foreach ($modelRef->getProperties() as $prop) {
                if ($prop->getAttributes(Hidden::class)) {
                    $hidden[] = $prop->getName();
                }
            }
        }

        if (! empty($hidden)) {
            $instance = new static;
            $instance->hidden = array_values(array_unique(
                array_merge($instance->hidden ?? [], $hidden)
            ));
        }
    }

    private static function applyCastsFromSchema(ReflectionClass $ref): void
    {
        // Casts are now applied per-instance in initializeHasSchema()
        // via applyCastsToInstance() to ensure they are set before any
        // fill() call, consistent with how fillable is handled.
        //        $casts = [];
        //
        //        foreach ($ref->getProperties() as $prop) {
        //            $attrs = $prop->getAttributes(Cast::class);
        //            if ($attrs) {
        //                $casts[$prop->getName()] = $attrs[0]->newInstance()->as;
        //            }
        //        }
        //
        //        if (! empty($casts)) {
        //            $instance = new static;
        //            $instance->casts = array_merge($casts, $instance->casts ?? []);
        //        }
    }

    private static function applyAppendsFromSchema(ReflectionClass $ref): void
    {
        $appends = [];

        foreach ($ref->getProperties() as $prop) {
            if ($prop->getAttributes(Appended::class)) {
                $appends[] = $prop->getName();
            }
        }

        if (! empty($appends)) {
            // Filter to only those whose accessor actually exists on the model.
            // Check both accessor styles:
            //   Old: getFullNameAttribute()
            //   New: fullName() returning Attribute::get()
            $appends = array_values(array_filter($appends, function (string $key) {
                // snake_case key → StudlyCase method name
                $studly = str_replace('_', '', ucwords($key, '_'));
                $oldStyle = 'get'.$studly.'Attribute';  // getFullNameAttribute
                $newStyle = lcfirst($studly);               // fullName

                return method_exists(static::class, $oldStyle)
                    || method_exists(static::class, $newStyle);
            }));

            if (! empty($appends)) {
                $instance = new static;
                $instance->appends = array_values(array_unique(
                    array_merge($instance->appends ?? [], $appends)
                ));
            }
        }
    }

    // -------------------------------------------------------------------------
    // Public validation helpers
    // -------------------------------------------------------------------------

    public static function schemaValidate(
        array $data,
        array $ignoreUniqueFor = [],
        bool $skipMissing = false,
    ): MessageBag {
        $schema = static::resolveSchemaClass();

        if ($schema === null) {
            throw new \RuntimeException(static::class.' has no #[UsesSchema] attribute.');
        }

        return Validator::validate($schema, $data, $ignoreUniqueFor, $skipMissing);
    }

    public static function schemaValidateOrFail(
        array $data,
        array $ignoreUniqueFor = [],
        bool $skipMissing = false,
    ): void {
        $schema = static::resolveSchemaClass();

        if ($schema === null) {
            throw new \RuntimeException(static::class.' has no #[UsesSchema] attribute.');
        }

        Validator::validateOrFail($schema, $data, $ignoreUniqueFor, $skipMissing);
    }

    public function schemaValidateForUpdate(array $data): void
    {
        $schema = static::resolveSchemaClass();

        if ($schema === null) {
            throw new \RuntimeException(static::class.' has no #[UsesSchema] attribute.');
        }

        Validator::validateForUpdate($schema, $data, $this);
    }

    public static function schemaFillable(): array
    {
        $ref = static::schemaReflection();
        if (! $ref) {
            return [];
        }

        return collect($ref->getProperties())
            ->filter(fn ($p) => ! empty($p->getAttributes(Fillable::class)))
            ->map(fn ($p) => $p->getName())
            ->values()
            ->all();
    }

    public static function schemaCasts(): array
    {
        $ref = static::schemaReflection();
        if (! $ref) {
            return [];
        }

        $casts = [];
        foreach ($ref->getProperties() as $prop) {
            $attrs = $prop->getAttributes(Cast::class);
            if ($attrs) {
                $casts[$prop->getName()] = $attrs[0]->newInstance()->as;
            }
        }

        return $casts;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function schemaRelatedModel(string $schemaClass): string
    {
        $base = preg_replace('/Schema$/', '', class_basename($schemaClass));

        return "\\App\\Models\\{$base}";
    }

    private function derivePivotTable(string $schemaA, string $schemaB): string
    {
        $parts = [
            strtolower(preg_replace('/Schema$/', '', class_basename($schemaA))),
            strtolower(preg_replace('/Schema$/', '', class_basename($schemaB))),
        ];
        sort($parts);

        return implode('_', $parts);
    }
}
