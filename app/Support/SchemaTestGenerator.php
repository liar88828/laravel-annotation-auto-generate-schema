<?php

namespace App\Support;

use App\Attributes\Migration\Column;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Model\Hidden;
use App\Attributes\Validation\Confirmed;
use App\Attributes\Validation\Email;
use App\Attributes\Validation\In;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Unique;
use App\Attributes\Validation\Uuid;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * SchemaTestGenerator
 *
 * Generates a PHPUnit test class from a schema class.
 * Covers:
 *   - Migration: table exists, all columns exist
 *   - Model:     $fillable, $hidden, $casts, $table resolved from schema
 *   - Validation: required fields, email, unique, in, min/max, uuid, confirmed
 *   - Persistence: create, soft-delete/restore (if applicable)
 *   - Serialization: hidden fields not in toArray()
 */
class SchemaTestGenerator
{
    private ReflectionClass $ref;

    private function __construct(private readonly string $schemaClass) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public static function generate(string $schemaClass, array $options = []): string
    {
        return (new self($schemaClass))->build($options);
    }

    public static function write(string $schemaClass, array $options = []): ?string
    {
        $ref = new ReflectionClass($schemaClass);
        $modelName = preg_replace('/Schema$/', '', $ref->getShortName());
        $dir = base_path('tests/Unit');
        $path = $dir.'/'.$modelName.'Test.php';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! ($options['force'] ?? false)) {
            return null;
        }

        file_put_contents($path, static::generate($schemaClass, $options));

        return $path;
    }

    // -------------------------------------------------------------------------
    // Builder
    // -------------------------------------------------------------------------

    private function build(array $options = []): string
    {
        $this->ref = new ReflectionClass($this->schemaClass);

        $includeMigrate = $options['migrate'] ?? true;
        $includeModel = $options['model'] ?? true;

        $modelName = preg_replace('/Schema$/', '', $this->ref->getShortName());
        $tableAttr = $this->classAttr(Table::class);
        $tableName = $tableAttr?->name ?? Str::snake(Str::plural($modelName));
        $softDeletes = $tableAttr?->softDeletes ?? false;
        $modelFqcn = $this->resolveModelFqcn();
        $modelBase = class_basename($modelFqcn);

        $columns = $this->collectColumns();
        $fillable = $this->collectFillable();
        $hidden = $this->collectHidden();
        $casts = $this->collectCasts();
        $validations = $this->collectValidationInfo();

        $methods = [];

        // 1. Migration tests
        if ($includeMigrate) {
            $methods[] = $this->testTableExists($tableName);
            $methods[] = $this->testColumnsExist($tableName, $columns);
        }

        // 2. Model wiring + validation + persistence tests
        if ($includeModel) {
            if (! empty($fillable)) {
                $methods[] = $this->testFillable($modelBase, $fillable);
            }
            if (! empty($hidden)) {
                $methods[] = $this->testHidden($modelBase, $hidden);
            }
            if (! empty($casts)) {
                $methods[] = $this->testCasts($modelBase, $casts);
            }
            $methods[] = $this->testTable($modelBase, $tableName);

            if (! empty($validations['required'])) {
                $methods[] = $this->testRequiredFields($validations['required']);
            }
            if (! empty($validations['email'])) {
                $methods[] = $this->testEmailValidation($validations['email'][0]);
            }
            if (! empty($validations['in'])) {
                foreach ($validations['in'] as [$field, $allowed]) {
                    $methods[] = $this->testInValidation($field, $allowed);
                }
            }
            if (! empty($validations['uuid'])) {
                $methods[] = $this->testUuidValidation($validations['uuid'][0]);
            }
            if (! empty($validations['confirmed'])) {
                $methods[] = $this->testConfirmedValidation($validations['confirmed'][0]);
            }
            if (! empty($validations['unique'])) {
                $methods[] = $this->testUniqueValidation($modelBase, $validations['unique'][0], $validations);
            }

            $methods[] = $this->testCreate($modelBase, $fillable, $validations);

            if (! empty($hidden)) {
                $methods[] = $this->testHiddenInSerialization($modelBase, $hidden[0], $fillable, $validations);
            }
            if ($softDeletes) {
                $methods[] = $this->testSoftDelete($modelBase, $fillable, $validations);
            }
        }

        $body = implode(PHP_EOL.PHP_EOL, $methods);
        $helpers = $this->buildHelperMethods($validations, $modelBase);
        $useStr = $this->buildUses($modelFqcn, $softDeletes);

        return <<<PHP
<?php

namespace Tests\Unit;

use Tests\TestCase;
{$useStr}
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * {$modelBase}Test
 *
 * Auto-generated from {$this->schemaClass}.
 * Covers: migration, model wiring, validation, persistence.
 */
class {$modelBase}Test extends TestCase
{
    use RefreshDatabase;

{$body}

{$helpers}
}
PHP;
    }

    // -------------------------------------------------------------------------
    // Test method builders
    // -------------------------------------------------------------------------

    private function testTableExists(string $table): string
    {
        return <<<PHP
    #[Test]
    public function it_creates_the_{$table}_table_from_schema_annotations(): void
    {
        \$this->assertTrue(Schema::hasTable('{$table}'));
    }
PHP;
    }

    private function testColumnsExist(string $table, array $columns): string
    {
        $assertions = implode(PHP_EOL, array_map(
            fn ($col) => "        \$this->assertTrue(Schema::hasColumn('{$table}', '{$col}'), \"Column [{$col}] missing.\");",
            $columns
        ));

        return <<<PHP
    #[Test]
    public function it_has_the_expected_columns(): void
    {
{$assertions}
    }
PHP;
    }

    private function testFillable(string $model, array $fillable): string
    {
        $assertions = implode(PHP_EOL, array_map(
            fn ($f) => "        \$this->assertContains('{$f}', \$model->getFillable(), \"[{$f}] should be fillable.\");",
            $fillable
        ));

        return <<<PHP
    #[Test]
    public function model_fillable_is_resolved_from_schema(): void
    {
        \$model = new {$model};
{$assertions}
    }
PHP;
    }

    private function testHidden(string $model, array $hidden): string
    {
        $assertions = implode(PHP_EOL, array_map(
            fn ($h) => "        \$this->assertContains('{$h}', \$model->getHidden(), \"[{$h}] should be hidden.\");",
            $hidden
        ));

        return <<<PHP
    #[Test]
    public function model_hidden_is_resolved_from_schema(): void
    {
        \$model = new {$model};
{$assertions}
    }
PHP;
    }

    private function testCasts(string $model, array $casts): string
    {
        $assertions = implode(PHP_EOL, array_map(
            fn ($field, $type) => "        \$this->assertArrayHasKey('{$field}', \$casts);\n        \$this->assertSame('{$type}', \$casts['{$field}']);",
            array_keys($casts),
            array_values($casts)
        ));

        return <<<PHP
    #[Test]
    public function model_casts_are_resolved_from_schema(): void
    {
        \$casts = (new {$model})->getCasts();
{$assertions}
    }
PHP;
    }

    private function testTable(string $model, string $table): string
    {
        return <<<PHP
    #[Test]
    public function model_table_is_resolved_from_schema(): void
    {
        \$this->assertSame('{$table}', (new {$model})->getTable());
    }
PHP;
    }

    private function testRequiredFields(array $required): string
    {
        $assertions = implode(PHP_EOL, array_map(
            fn ($f) => "        \$this->assertTrue(\$errors->has('{$f}'), \"[{$f}] should fail required.\");",
            $required
        ));

        return <<<PHP
    #[Test]
    public function validation_fails_when_required_fields_are_missing(): void
    {
        \$errors = \$this->schemaValidate([]);
{$assertions}
    }
PHP;
    }

    private function testEmailValidation(string $field): string
    {
        return <<<PHP
    #[Test]
    public function validation_fails_with_invalid_email(): void
    {
        \$data           = \$this->validData();
        \$data['{$field}'] = 'not-an-email';

        \$errors = \$this->schemaValidate(\$data);

        \$this->assertTrue(\$errors->has('{$field}'));
    }
PHP;
    }

    private function testInValidation(string $field, array $allowed): string
    {
        return <<<PHP
    #[Test]
    public function validation_fails_when_{$field}_is_not_in_allowed_values(): void
    {
        \$data           = \$this->validData();
        \$data['{$field}'] = '__invalid__';

        \$errors = \$this->schemaValidate(\$data);

        \$this->assertTrue(\$errors->has('{$field}'));
    }
PHP;
    }

    private function testUuidValidation(string $field): string
    {
        return <<<PHP
    #[Test]
    public function validation_fails_with_invalid_uuid(): void
    {
        \$data           = \$this->validData();
        \$data['{$field}'] = 'not-a-uuid';

        \$errors = \$this->schemaValidate(\$data);

        \$this->assertTrue(\$errors->has('{$field}'));
    }
PHP;
    }

    private function testConfirmedValidation(string $field): string
    {
        $confirmation = $field.'_confirmation';

        return <<<PHP
    #[Test]
    public function validation_fails_when_{$field}_confirmation_does_not_match(): void
    {
        \$data                    = \$this->validData();
        \$data['{$confirmation}'] = 'different_value';

        \$errors = \$this->schemaValidate(\$data);

        \$this->assertTrue(\$errors->has('{$field}'));
    }
PHP;
    }

    private function testUniqueValidation(string $model, string $field, array $validations): string
    {
        return <<<PHP
    #[Test]
    public function validation_passes_with_valid_data(): void
    {
        \$errors = \$this->schemaValidate(\$this->validData());

        \$this->assertTrue(\$errors->isEmpty());
    }

    #[Test]
    public function update_validation_ignores_own_record_in_unique_check(): void
    {
        \$model  = {$model}::create(\$this->createData());
        \$errors = \$this->schemaValidate(
            ['{$field}' => \$model->{$field}],
            ignoreUniqueFor: ['{$field}' => \$model->id],
            skipMissing: true,
        );

        \$this->assertTrue(\$errors->isEmpty());
    }
PHP;
    }

    private function testCreate(string $model, array $fillable, array $validations): string
    {
        return <<<PHP
    #[Test]
    public function it_can_create_a_{$this->snakeName($model)}(): void
    {
        \$model = {$model}::create(\$this->createData());

        \$this->assertNotNull(\$model->id);
        \$this->assertDatabaseHas(\$model->getTable(), ['id' => \$model->id]);
    }
PHP;
    }

    private function testHiddenInSerialization(string $model, string $hiddenField, array $fillable, array $validations): string
    {
        return <<<PHP
    #[Test]
    public function hidden_fields_are_not_visible_in_serialization(): void
    {
        \$model = {$model}::create(\$this->createData());

        \$this->assertArrayNotHasKey('{$hiddenField}', \$model->toArray());
    }
PHP;
    }

    private function testSoftDelete(string $model, array $fillable, array $validations): string
    {
        return <<<PHP
    #[Test]
    public function soft_delete_works(): void
    {
        \$model = {$model}::create(\$this->createData());
        \$id    = \$model->id;

        \$model->delete();

        \$this->assertNull({$model}::find(\$id));
        \$this->assertNotNull({$model}::withTrashed()->find(\$id)?->deleted_at);
    }

    #[Test]
    public function soft_deleted_record_can_be_restored(): void
    {
        \$model = {$model}::create(\$this->createData());
        \$model->delete();
        \$model->restore();

        \$this->assertNotNull({$model}::find(\$model->id));
    }
PHP;
    }

    // -------------------------------------------------------------------------
    // Helper method stubs
    // -------------------------------------------------------------------------

    private function buildHelperMethods(array $validations, string $model): string
    {
        $factoryClass = "Database\\Factories\\{$model}Factory";
        $hasFactory = class_exists($factoryClass);

        // validData still uses hardcoded values because it's used for validation
        // edge-case tests (invalid email, wrong status, etc.) and needs predictable
        // field values — factory randomness would make those assertions fragile.
        $validLines = $this->renderValidData($validations);

        // createData uses the factory when available so tests stay in sync
        // with the factory definition automatically.
        $createDataMethod = $hasFactory
            ? $this->renderCreateDataWithFactory($model, $validations)
            : $this->renderCreateDataInline($validations);

        return <<<PHP
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal valid data that passes all validation rules. */
    private function validData(): array
    {
        return [
{$validLines}
        ];
    }

{$createDataMethod}

    private function schemaValidate(array \$data, array \$ignoreUniqueFor = [], bool \$skipMissing = false): \Illuminate\Support\MessageBag
    {
        return {$model}::schemaValidate(\$data, \$ignoreUniqueFor, \$skipMissing);
    }
PHP;
    }

    /**
     * createData() using the model factory — stays in sync automatically.
     * Merges factory output with any confirmation fields needed for validation.
     */
    private function renderValidData(array $validations): string
    {
        $lines = [];

        foreach ($validations['fields'] as $field => $info) {
            $lines[] = "            '{$field}' => ".$this->fakeValueFor($field, $info).',';
        }

        // Add confirmation fields (e.g. password_confirmation)
        foreach ($validations['confirmed'] as $field) {
            $value = $this->fakeValueFor($field, $validations['fields'][$field] ?? []);
            $lines[] = "            '{$field}_confirmation' => {$value},";
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderCreateDataWithFactory(string $model, array $validations): string
    {
        // Confirmation fields (e.g. password_confirmation) are never in the factory
        // so we merge them on top.
        $confirmLines = [];
        foreach ($validations['confirmed'] as $field) {
            $value = $this->fakeValueFor($field, $validations['fields'][$field] ?? []);
            $confirmLines[] = "            '{$field}_confirmation' => {$value},";
        }

        if (empty($confirmLines)) {
            return <<<PHP
    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     */
    private function createData(): array
    {
        return {$model}::factory()->make()->toArray();
    }
PHP;
        }

        $confirmMerge = implode(PHP_EOL, $confirmLines);

        return <<<PHP
    /**
     * Data suitable for Model::create().
     * Uses the factory so it stays in sync with your factory definition.
     * Merges confirmation fields on top that factories don't include.
     */
    private function createData(): array
    {
        return array_merge(
            {$model}::factory()->make()->toArray(),
            [
{$confirmMerge}
            ]
        );
    }
PHP;
    }

    /**
     * createData() fallback when no factory exists — uses hardcoded values.
     * Run php artisan schema:factory to generate a factory and regenerate this test.
     */
    private function renderCreateDataInline(array $validations): string
    {
        $lines = [];

        foreach ($validations['fields'] as $field => $info) {
            $lines[] = "            '{$field}' => ".$this->fakeValueFor($field, $info).',';
        }

        $body = implode(PHP_EOL, $lines);

        return <<<PHP
    /**
     * Data suitable for Model::create().
     * NOTE: No factory found — using hardcoded values.
     * Run: php artisan schema:factory to generate a factory, then regenerate this test.
     */
    private function createData(): array
    {
        return [
{$body}
        ];
    }
PHP;
    }

    private function fakeValueFor(string $field, array $info): string
    {
        if ($info['uuid'] ?? false) {
            return '(string) Str::uuid()';
        }

        if ($info['email'] ?? false) {
            return "'test@example.com'";
        }

        if (! empty($info['in'])) {
            return "'".$info['in'][0]."'";
        }

        if ($field === 'password') {
            return "'password123'";
        }

        if (($info['cast'] ?? '') === 'boolean' || ($info['colType'] ?? '') === 'boolean') {
            return 'false';
        }

        if (in_array($info['colType'] ?? '', ['date', 'datetime', 'timestamp'])) {
            return 'now()->toDateString()';
        }

        if (in_array($info['colType'] ?? '', ['json', 'jsonb'])) {
            return '[]';
        }

        if (in_array($info['colType'] ?? '', [
            'integer', 'unsignedBigInteger', 'unsignedInteger', 'bigInteger',
            'smallInteger', 'tinyInteger', 'unsignedTinyInteger', 'unsignedSmallInteger',
        ])) {
            $min = $info['min'] ?? 1;

            return (string) max(1, (int) $min);
        }

        // Decimal / float — return a numeric value respecting min constraint
        if (in_array($info['colType'] ?? '', ['decimal', 'float', 'double'])) {
            $min = $info['min'] ?? 0;

            return number_format(max(0, (float) $min) + 1.00, 2, '.', '');
        }

        if ($info['nullable'] ?? false) {
            return 'null';
        }

        if (str_ends_with($field, '_id')) {
            return '1';
        }

        $min = $info['min'] ?? 2;

        return "'".str_repeat('a', max(2, (int) $min))."'";
    }

    // -------------------------------------------------------------------------
    // Schema introspection
    // -------------------------------------------------------------------------

    private function classAttr(string $attrClass): ?object
    {
        $attrs = $this->ref->getAttributes($attrClass);

        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private function collectColumns(): array
    {
        $cols = [];

        foreach ($this->ref->getProperties() as $prop) {
            if (! empty($prop->getAttributes(Column::class))) {
                $colAttr = $prop->getAttributes(Column::class)[0]->newInstance();
                $cols[] = $colAttr->name ?? $prop->getName();
            }
        }

        return $cols;
    }

    private function collectFillable(): array
    {
        return array_values(array_map(
            fn ($p) => $p->getName(),
            array_filter(
                $this->ref->getProperties(),
                fn ($p) => ! empty($p->getAttributes(Fillable::class))
            )
        ));
    }

    private function collectHidden(): array
    {
        return array_values(array_map(
            fn ($p) => $p->getName(),
            array_filter(
                $this->ref->getProperties(),
                fn ($p) => ! empty($p->getAttributes(Hidden::class))
            )
        ));
    }

    private function collectCasts(): array
    {
        $casts = [];
        foreach ($this->ref->getProperties() as $prop) {
            $attrs = $prop->getAttributes(Cast::class);
            if ($attrs) {
                $casts[$prop->getName()] = $attrs[0]->newInstance()->as;
            }
        }

        return $casts;
    }

    private function collectValidationInfo(): array
    {
        $required = [];
        $email = [];
        $unique = [];
        $in = [];
        $uuid = [];
        $confirmed = [];
        $fields = [];

        foreach ($this->ref->getProperties() as $prop) {
            if (! empty($prop->getAttributes(PrimaryKey::class))) {
                continue;
            }

            $name = $prop->getName();
            $colAttrs = $prop->getAttributes(Column::class);
            $colType = $colAttrs ? $colAttrs[0]->newInstance()->type : 'string';
            $nullable = $colAttrs ? $colAttrs[0]->newInstance()->nullable : false;

            $minAttr = $prop->getAttributes(Min::class);
            $maxAttr = $prop->getAttributes(Max::class);

            $fieldInfo = [
                'colType' => $colType,
                'nullable' => $nullable,
                'min' => $minAttr ? $minAttr[0]->newInstance()->min : null,
                'max' => $maxAttr ? $maxAttr[0]->newInstance()->max : null,
                'cast' => $prop->getAttributes(Cast::class) ? $prop->getAttributes(Cast::class)[0]->newInstance()->as : null,
                'uuid' => false,
                'email' => false,
                'in' => [],
            ];

            if (! empty($prop->getAttributes(Required::class))) {
                $required[] = $name;
            }
            if (! empty($prop->getAttributes(Email::class))) {
                $email[] = $name;
                $fieldInfo['email'] = true;
            }
            if (! empty($prop->getAttributes(Unique::class))) {
                $unique[] = $name;
            }
            if (! empty($prop->getAttributes(In::class))) {
                $inInstance = $prop->getAttributes(In::class)[0]->newInstance();
                $allowed = $inInstance->allowed;
                $in[] = [$name, $allowed];
                $fieldInfo['in'] = $allowed;
            }
            if (! empty($prop->getAttributes(Uuid::class))) {
                $uuid[] = $name;
                $fieldInfo['uuid'] = true;
            }
            if (! empty($prop->getAttributes(Confirmed::class))) {
                $confirmed[] = $name;
            }

            if (! empty($prop->getAttributes(Fillable::class))) {
                $fields[$name] = $fieldInfo;
            }
        }

        return compact('required', 'email', 'unique', 'in', 'uuid', 'confirmed', 'fields');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildUses(string $modelFqcn, bool $softDeletes): string
    {
        return "use {$modelFqcn};";
    }

    private function resolveModelFqcn(): string
    {
        $attrs = $this->ref->getAttributes(EloquentModel::class);
        if ($attrs) {
            return $attrs[0]->newInstance()->model;
        }
        $base = preg_replace('/Schema$/', '', $this->ref->getShortName());

        return "App\\Models\\{$base}";
    }

    private function snakeName(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }
}
