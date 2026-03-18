<?php

namespace App\Support;

use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Unique;
use App\Contracts\ValidationRule;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use ReflectionClass;

/**
 * Attribute-driven validator.
 *
 * Reads PHP 8 #[Attribute] annotations on class properties and runs
 * each rule's passes() method against the provided data.
 *
 * Usage (create):
 *   Validator::validateOrFail(UserSchema::class, $request->all());
 *
 * Usage (update — skip Unique check for current record, skip Required on missing fields):
 *   Validator::validateOrFail(
 *       UserSchema::class,
 *       $request->all(),
 *       ignoreUniqueFor: ['email' => $user->id],
 *       skipMissing: true,
 *   );
 */
class Validator
{
    private function __construct(
        private readonly string $schemaClass,
        private readonly array $data,
        /** field → id to ignore in Unique checks (for update scenarios) */
        private readonly array $ignoreUniqueFor = [],
        /** When true, Required is skipped for fields absent from $data (PATCH semantics) */
        private readonly bool $skipMissing = false,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Validate and return a MessageBag of errors (empty = passes).
     *
     * @param  array  $ignoreUniqueFor  ['fieldName' => $ignoreId, ...]
     * @param  bool  $skipMissing  Skip Required for fields not present in $data
     */
    public static function validate(
        string $schemaClass,
        array $data,
        array $ignoreUniqueFor = [],
        bool $skipMissing = false,
    ): MessageBag {
        return (new self($schemaClass, $data, $ignoreUniqueFor, $skipMissing))->run();
    }

    /**
     * Validate and throw a Laravel ValidationException on failure.
     *
     * @throws ValidationException
     */
    public static function validateOrFail(
        string $schemaClass,
        array $data,
        array $ignoreUniqueFor = [],
        bool $skipMissing = false,
    ): void {
        $errors = static::validate($schemaClass, $data, $ignoreUniqueFor, $skipMissing);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }
    }

    /**
     * Validate for a PATCH/update request.
     * - Only validates fields that are present in $data
     * - Skips Required for absent fields
     * - Applies ignoreUniqueFor automatically if $model has a getKey() method
     *
     * @throws ValidationException
     */
    public static function validateForUpdate(
        string $schemaClass,
        array $data,
        mixed $modelOrId,
        array $uniqueFields = [],   // which fields need unique-ignore, e.g. ['email', 'username']
    ): void {
        // Resolve the ignore ID
        $ignoreId = is_object($modelOrId) && method_exists($modelOrId, 'getKey')
            ? $modelOrId->getKey()
            : $modelOrId;

        // Build ignoreUniqueFor map from the schema's Unique attributes
        $ignoreMap = [];

        if (empty($uniqueFields)) {
            // Auto-detect all Unique-annotated fields
            $ref = new ReflectionClass($schemaClass);
            foreach ($ref->getProperties() as $prop) {
                foreach ($prop->getAttributes(Unique::class) as $_) {
                    $ignoreMap[$prop->getName()] = $ignoreId;
                }
            }
        } else {
            foreach ($uniqueFields as $field) {
                $ignoreMap[$field] = $ignoreId;
            }
        }

        static::validateOrFail($schemaClass, $data, $ignoreMap, skipMissing: true);
    }

    /**
     * Validate and return a hydrated instance of $schemaClass on success.
     *
     * @throws ValidationException
     */
    public static function validateAndHydrate(
        string $schemaClass,
        array $data,
        array $ignoreUniqueFor = [],
        bool $skipMissing = false,
    ): object {
        static::validateOrFail($schemaClass, $data, $ignoreUniqueFor, $skipMissing);

        return self::hydrate($schemaClass, $data);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function run(): MessageBag
    {
        $ref = new ReflectionClass($this->schemaClass);
        $bag = new MessageBag;

        foreach ($ref->getProperties() as $property) {
            // Primary key is never validated — it is generated server-side
            if (! empty($property->getAttributes(PrimaryKey::class))) {
                continue;
            }

            $field = $property->getName();
            $value = $this->data[$field] ?? null;
            $isPresent = array_key_exists($field, $this->data);

            foreach ($property->getAttributes() as $attrRef) {
                $instance = $attrRef->newInstance();

                if (! ($instance instanceof ValidationRule)) {
                    continue;
                }

                // PATCH semantics: skip Required for absent fields
                if ($this->skipMissing && ! $isPresent && $instance instanceof Required) {
                    continue;
                }

                // Skip validation entirely for absent fields in PATCH mode
                if ($this->skipMissing && ! $isPresent) {
                    continue;
                }

                // Inject ignoreId into Unique rules for update scenarios
                if ($instance instanceof Unique && isset($this->ignoreUniqueFor[$field])) {
                    $instance = new Unique(
                        table: (fn () => $this->table)->bindTo($instance, $instance)(),
                        column: (fn () => $this->column)->bindTo($instance, $instance)(),
                        ignoreId: $this->ignoreUniqueFor[$field],
                        idColumn: (fn () => $this->idColumn)->bindTo($instance, $instance)(),
                        message: (fn () => $this->message)->bindTo($instance, $instance)(),
                    );
                }

                if (! $instance->passes($field, $value, $this->data)) {
                    $bag->add($field, $instance->message($field));
                }
            }
        }

        return $bag;
    }

    private static function hydrate(string $class, array $data): object
    {
        $ref = new ReflectionClass($class);
        $object = $ref->newInstanceWithoutConstructor();

        foreach ($ref->getProperties() as $property) {
            $name = $property->getName();

            if (array_key_exists($name, $data)) {
                $property->setAccessible(true);
                $property->setValue($object, $data[$name]);
            }
        }

        return $object;
    }
}
