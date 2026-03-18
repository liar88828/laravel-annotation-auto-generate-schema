<?php

namespace App\Console\Commands;

use App\Attributes\Migration\BelongsTo as BelongsToAttr;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignSchema as ForeignSchemaAttr;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\Email;
use App\Attributes\Validation\In;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Uuid;
use Illuminate\Console\Command;
use ReflectionClass;

/**
 * SchemaFactory
 *
 * Generates a Laravel model factory from a schema class.
 * Reads #[Column], #[Cast], and validation attributes to produce
 * the most appropriate Faker call for each fillable field.
 *
 * Usage:
 *   php artisan schema:factory ProductSchema          single schema
 *   php artisan schema:factory --all                  all schemas in app/Schema/
 *   php artisan schema:factory ProductSchema --force  overwrite existing factory
 *   php artisan schema:factory --all --force
 */
class SchemaFactory extends Command
{
    protected $signature = 'schema:factory
                            {class? : Short name (ProductSchema) or FQCN. Omit to use --all.}
                            {--all   : Generate factories for every schema in app/Schema/}
                            {--force : Overwrite existing factory files}';

    protected $description = 'Generate a model factory from a schema class. Use --all for every schema in app/Schema/.';

    // -------------------------------------------------------------------------
    // Entry
    // -------------------------------------------------------------------------

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->handleAll();
        }

        $input = $this->argument('class');

        if (! $input) {
            $this->error('Provide a schema name or use --all.');
            $this->line('  php artisan schema:factory ProductSchema');
            $this->line('  php artisan schema:factory --all');

            return self::FAILURE;
        }

        $schemaClass = $this->expandClass($input);
        $this->resolveClass($schemaClass);

        if (! class_exists($schemaClass)) {
            $this->error("Class [{$schemaClass}] not found.");
            $this->line('Run: composer dump-autoload');

            return self::FAILURE;
        }

        return $this->generateOne($schemaClass);
    }

    // -------------------------------------------------------------------------
    // --all
    // -------------------------------------------------------------------------

    private function handleAll(): int
    {
        $schemaDir = app_path('Schema');

        if (! is_dir($schemaDir)) {
            $this->error("Schema directory not found: {$schemaDir}");

            return self::FAILURE;
        }

        $files = $this->findSchemaFiles($schemaDir);

        if (empty($files)) {
            $this->warn('No schema files found in app/Schema/');

            return self::SUCCESS;
        }

        $this->line('Found '.count($files).' schema(s). Generating factories...');
        $this->line('');

        $success = 0;
        $failed = 0;

        foreach ($files as $file) {
            $schemaClass = $this->fileToClass($file);
            require_once $file;

            if (! class_exists($schemaClass)) {
                $this->warn("  Skipped [{$schemaClass}] — class not found after require.");
                $failed++;

                continue;
            }

            $this->line("<fg=cyan>━━ {$schemaClass}</>");

            if ($this->generateOne($schemaClass, quiet: true) === self::SUCCESS) {
                $success++;
            } else {
                $failed++;
            }

            $this->line('');
        }

        $this->line("Done: {$success} generated, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Generate one factory
    // -------------------------------------------------------------------------

    private function generateOne(string $schemaClass, bool $quiet = false): int
    {
        try {
            $meta = $this->extractMeta($schemaClass);
            $path = $this->writeFactory($meta);

            if (! $path) {
                return self::FAILURE;
            }

            $this->info("  Factory: {$path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('  ✗ '.$e->getMessage());

            return self::FAILURE;
        }
    }

    // -------------------------------------------------------------------------
    // Meta extraction
    // -------------------------------------------------------------------------

    private function extractMeta(string $schemaClass): array
    {
        $ref = new ReflectionClass($schemaClass);
        $modelName = preg_replace('/Schema$/', '', class_basename($schemaClass));

        // Resolve model FQCN from #[EloquentModel] or default to App\Models\{Name}
        $modelAttrs = $ref->getAttributes(EloquentModel::class);
        $modelFqcn = $modelAttrs
            ? $modelAttrs[0]->newInstance()->model
            : "App\\Models\\{$modelName}";

        $fields = [];

        foreach ($ref->getProperties() as $prop) {
            // Skip primary key — never in factory definition
            if (! empty($prop->getAttributes(PrimaryKey::class))) {
                continue;
            }

            // Only generate factory values for fillable fields
            if (empty($prop->getAttributes(Fillable::class))) {
                continue;
            }

            $name = $prop->getName();
            $colAttrs = $prop->getAttributes(Column::class);
            $col = $colAttrs ? $colAttrs[0]->newInstance() : null;
            $nullable = $col?->nullable ?? false;
            $default = $col?->default !== '__UNSET__' ? $col?->default : null;
            $colType = $col?->type ?? 'string';
            $precision = $col?->precision;
            $scale = $col?->scale ?? 2;

            // ── #[ForeignSchema] → use related model's factory ────────────
            $fsAttrs = $prop->getAttributes(ForeignSchemaAttr::class);
            if (! empty($fsAttrs)) {
                $relatedSchema = $fsAttrs[0]->newInstance()->schema;
                $relatedModel = preg_replace('/Schema$/', '', class_basename($relatedSchema));
                $factoryClass = "Database\\Factories\\{$relatedModel}Factory";
                $faker = class_exists($factoryClass)
                    ? "\\App\\Models\\{$relatedModel}::factory()->create()->id"
                    : "\\App\\Models\\{$relatedModel}::factory()->create()->getKey()";
                if ($nullable) {
                    $faker = "fake()->boolean(80) ? {$faker} : null";
                }
                $fields[$name] = ['faker' => $faker, 'nullable' => $nullable, 'default' => $default];

                continue;
            }

            // ── #[BelongsTo] on an _id column → use related model's factory
            $btAttrs = $prop->getAttributes(BelongsToAttr::class);
            if (! empty($btAttrs) && str_ends_with($name, '_id')) {
                $relatedSchema = $btAttrs[0]->newInstance()->related;
                $relatedModel = preg_replace('/Schema$/', '', class_basename($relatedSchema));
                $factoryClass = "Database\\Factories\\{$relatedModel}Factory";
                $faker = class_exists($factoryClass)
                    ? "\\App\\Models\\{$relatedModel}::factory()->create()->id"
                    : "\\App\\Models\\{$relatedModel}::factory()->create()->getKey()";
                if ($nullable) {
                    $faker = "fake()->boolean(80) ? {$faker} : null";
                }
                $fields[$name] = ['faker' => $faker, 'nullable' => $nullable, 'default' => $default];

                continue;
            }

            $faker = $this->fakerExpression($name, $colType, $prop, $nullable, $default, $precision, $scale);

            $fields[$name] = [
                'faker' => $faker,
                'nullable' => $nullable,
                'default' => $default,
            ];
        }

        return [
            'schemaClass' => $schemaClass,
            'modelName' => $modelName,
            'modelFqcn' => $modelFqcn,
            'fields' => $fields,
        ];
    }

    // -------------------------------------------------------------------------
    // Faker expression resolver
    // Maps column type + field name + validation attributes → Faker call
    // -------------------------------------------------------------------------

    private function fakerExpression(
        string $name,
        string $colType,
        \ReflectionProperty $prop,
        bool $nullable,
        mixed $default,
        ?int $precision = null,
        int $scale = 2,
    ): string {

        // ── Nullable with default — wrap in maybe() ───────────────────────────
        $wrap = function (string $expr) use ($nullable, $default): string {
            if (! $nullable) {
                return $expr;
            }

            // If the expression is a plain value (integer, null, quoted string)
            // rather than a fake() call, just wrap in a ternary directly
            if (! str_starts_with($expr, 'fake()')
                && ! str_starts_with($expr, '(string)')
                && ! str_starts_with($expr, 'bcrypt(')
            ) {
                return "fake()->boolean(80) ? {$expr} : null";
            }

            return "fake()->optional(0.8)->{$this->unwrapFaker($expr)} ?? ".$this->phpValue($default);
        };

        // ── #[In(...)] — pick from allowed values ─────────────────────────────
        $inAttrs = $prop->getAttributes(In::class);
        if (! empty($inAttrs)) {
            $allowed = $this->extractInValues($inAttrs[0]->newInstance());
            if (! empty($allowed)) {
                $list = implode(', ', array_map(fn ($v) => var_export($v, true), $allowed));

                return $nullable
                    ? "fake()->optional(0.8)->randomElement([{$list}])"
                    : "fake()->randomElement([{$list}])";
            }
        }

        // ── #[Email] ──────────────────────────────────────────────────────────
        if (! empty($prop->getAttributes(Email::class))) {
            return $wrap('fake()->unique()->safeEmail()');
        }

        // ── #[Uuid] ───────────────────────────────────────────────────────────
        if (! empty($prop->getAttributes(Uuid::class))) {
            return "(string) \Illuminate\Support\Str::uuid()";
        }

        // ── Column type mapping ───────────────────────────────────────────────
        return $wrap($this->fakerForColumnType($name, $colType, $prop, $precision, $scale));
    }

    private function fakerForColumnType(
        string $name,
        string $type,
        \ReflectionProperty $prop,
        ?int $precision = null,
        int $scale = 2,
    ): string {
        // Min/Max constraints — now public readonly, readable directly
        $minAttr = $prop->getAttributes(Min::class);
        $maxAttr = $prop->getAttributes(Max::class);
        $min = $minAttr ? $minAttr[0]->newInstance()->min : null;
        $max = $maxAttr ? $maxAttr[0]->newInstance()->max : null;

        // Name-based heuristics first
        $heuristic = $this->nameHeuristic($name);
        if ($heuristic !== null) {
            return $heuristic;
        }

        return match (true) {
            // ── FK columns (_id suffix) ───────────────────────────────────────
            str_ends_with(strtolower($name), '_id') => '1',

            // ── UUID / ULID ───────────────────────────────────────────────────
            in_array($type, ['uuid']) => "(string) \Illuminate\Support\Str::uuid()",

            in_array($type, ['ulid']) => "(string) \Illuminate\Support\Str::ulid()",

            // ── Boolean ───────────────────────────────────────────────────────
            in_array($type, ['boolean', 'bool', 'tinyInteger']) => 'fake()->boolean()',

            // ── Integer types ─────────────────────────────────────────────────
            in_array($type, ['integer', 'unsignedInteger', 'bigInteger', 'unsignedBigInteger',
                'smallInteger', 'unsignedSmallInteger', 'mediumInteger',
                'unsignedMediumInteger', 'tinyInteger', 'unsignedTinyInteger']) => $this->fakerInt($min, $max, $type),

            // ── Decimal / Float — use precision/scale if available ────────────
            in_array($type, ['decimal', 'float', 'double']) => "fake()->randomFloat({$scale}, ".($min ?? 0).', '.($max ?? (pow(10, ($precision ?? 10) - $scale) - 1)).')',

            // ── Date / Time ───────────────────────────────────────────────────
            $type === 'date' => 'fake()->date()',

            $type === 'time' => 'fake()->time()',

            in_array($type, ['datetime', 'timestamp', 'dateTime']) => 'fake()->dateTime()',

            $type === 'year' => '(string) fake()->year()',

            // ── Text / Long string ────────────────────────────────────────────
            in_array($type, ['text', 'mediumText', 'longText']) => 'fake()->paragraph()',

            // ── JSON ──────────────────────────────────────────────────────────
            in_array($type, ['json', 'jsonb']) => '[]',

            // ── String (default) ─────────────────────────────────────────────
            default => $max !== null
                ? "fake()->text({$max})"
                : 'fake()->word()',
        };
    }

    /**
     * Name-based heuristics — common field names map to specific Faker methods.
     */
    private function nameHeuristic(string $name): ?string
    {
        $lower = strtolower($name);

        return match (true) {
            in_array($lower, ['name', 'full_name', 'fullname']) => 'fake()->name()',
            in_array($lower, ['first_name', 'firstname']) => 'fake()->firstName()',
            in_array($lower, ['last_name', 'lastname', 'surname']) => 'fake()->lastName()',
            in_array($lower, ['email', 'email_address']) => 'fake()->unique()->safeEmail()',
            in_array($lower, ['password']) => "bcrypt('password')",
            in_array($lower, ['phone', 'phone_number', 'mobile', 'telephone']) => 'fake()->phoneNumber()',
            in_array($lower, ['address', 'street', 'street_address']) => 'fake()->streetAddress()',
            in_array($lower, ['city']) => 'fake()->city()',
            in_array($lower, ['state', 'province']) => 'fake()->state()',
            in_array($lower, ['country']) => 'fake()->country()',
            in_array($lower, ['zip', 'zip_code', 'postal_code', 'postcode']) => 'fake()->postcode()',
            in_array($lower, ['latitude', 'lat']) => 'fake()->latitude()',
            in_array($lower, ['longitude', 'lng', 'lon']) => 'fake()->longitude()',
            in_array($lower, ['url', 'website', 'link']) => 'fake()->url()',
            in_array($lower, ['slug']) => 'fake()->slug()',
            in_array($lower, ['title']) => 'fake()->sentence(3)',
            in_array($lower, ['description', 'summary', 'bio', 'about', 'note', 'notes', 'remark']) => 'fake()->paragraph()',
            in_array($lower, ['content', 'body', 'text', 'message']) => 'fake()->paragraphs(3, true)',
            in_array($lower, ['image', 'avatar', 'photo', 'picture', 'thumbnail']) => 'fake()->imageUrl()',
            in_array($lower, ['color', 'colour']) => 'fake()->hexColor()',
            in_array($lower, ['price', 'amount', 'cost', 'salary', 'wage']) => 'fake()->randomFloat(2, 1, 9999)',
            in_array($lower, ['quantity', 'qty', 'stock', 'count']) => 'fake()->numberBetween(1, 100)',
            in_array($lower, ['age']) => 'fake()->numberBetween(18, 80)',
            in_array($lower, ['gender', 'sex']) => "fake()->randomElement(['male', 'female'])",
            in_array($lower, ['ip', 'ip_address']) => 'fake()->ipv4()',
            in_array($lower, ['mac', 'mac_address']) => 'fake()->macAddress()',
            in_array($lower, ['uuid', 'public_id', 'external_id']) => "(string) \Illuminate\Support\Str::uuid()",
            str_ends_with($lower, '_id') => null,    // let column type handler deal with it, wrap handled by $wrap
            str_starts_with($lower, 'is_') || str_starts_with($lower, 'has_') => 'fake()->boolean()',
            str_ends_with($lower, '_at') => 'fake()->dateTime()',
            str_ends_with($lower, '_date') => 'fake()->date()',
            str_ends_with($lower, '_url') || str_ends_with($lower, '_link') => 'fake()->url()',
            str_ends_with($lower, '_email') => 'fake()->safeEmail()',
            str_ends_with($lower, '_count') || str_ends_with($lower, '_number') => 'fake()->numberBetween(0, 100)',
            default => null,
        };
    }

    private function fakerInt(?float $min, ?float $max, string $type): string
    {
        // Unsigned tiny integer is 0-255
        if ($type === 'unsignedTinyInteger') {
            return 'fake()->numberBetween('.($min ?? 0).', '.($max ?? 255).')';
        }

        $lo = $min !== null ? (int) $min : 1;
        $hi = $max !== null ? (int) $max : 9999;

        return "fake()->numberBetween({$lo}, {$hi})";
    }

    // -------------------------------------------------------------------------
    // File writer
    // -------------------------------------------------------------------------

    private function writeFactory(array $meta): ?string
    {
        $dir = database_path('factories');
        $path = $dir.'/'.$meta['modelName'].'Factory.php';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->option('force')) {
            if (! $this->confirm("Factory already exists at [{$path}]. Overwrite?")) {
                $this->warn('  Skipped.');

                return null;
            }
        }

        file_put_contents($path, $this->buildFactory($meta));

        return $path;
    }

    private function buildFactory(array $meta): string
    {
        $modelFqcn = $meta['modelFqcn'];
        $modelName = $meta['modelName'];
        $modelBase = class_basename($modelFqcn);
        $namespace = implode('\\', array_slice(explode('\\', $modelFqcn), 0, -1));
        $fields = $meta['fields'];

        // Build the definition array lines
        $lines = [];
        foreach ($fields as $field => $info) {
            $faker = $info['faker'];
            $lines[] = "            '{$field}' => {$faker},";
        }

        $definition = implode(PHP_EOL, $lines);

        return <<<PHP
<?php

namespace Database\Factories;

use {$modelFqcn};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * {$modelName}Factory
 *
 * Generated from schema annotations.
 * Faker calls are derived from #[Column] type, field name heuristics,
 * and validation attributes (#[In], #[Email], #[Uuid], #[Min], #[Max]).
 *
 * @extends Factory<{$modelBase}>
 */
class {$modelName}Factory extends Factory
{
    protected \$model = {$modelBase}::class;

    public function definition(): array
    {
        return [
{$definition}
        ];
    }
}
PHP;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Extract allowed values from an #[In] attribute instance.
     */
    private function extractInValues(object $inInstance): array
    {
        return $inInstance->allowed ?? [];
    }

    /**
     * Strip fake()-> prefix for use inside optional().
     * e.g. "fake()->word()" → "word()"
     */
    private function unwrapFaker(string $expr): string
    {
        return preg_replace('/^fake\(\)->/', '', $expr);
    }

    private function phpValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value)) {
            return "'".addslashes($value)."'";
        }

        return (string) $value;
    }

    private function expandClass(string $input): string
    {
        $input = str_replace('/', '\\', $input);

        return str_contains($input, '\\') ? $input : 'App\\Schema\\'.$input;
    }

    private function resolveClass(string $class): void
    {
        if (class_exists($class)) {
            return;
        }

        $relative = str_replace('\\', '/', ltrim(
            preg_replace('/^App/', '', $class), '\\'
        )).'.php';

        foreach ([app_path($relative), base_path('app/'.ltrim($relative, '/'))] as $path) {
            if (file_exists($path)) {
                require_once $path;

                return;
            }
        }
    }

    private function findSchemaFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function fileToClass(string $filePath): string
    {
        $appPath = rtrim(app_path(), DIRECTORY_SEPARATOR);
        $relative = ltrim(str_replace($appPath, '', $filePath), DIRECTORY_SEPARATOR);
        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        $relative = preg_replace('/\.php$/', '', $relative);

        return 'App\\'.$relative;
    }
}
