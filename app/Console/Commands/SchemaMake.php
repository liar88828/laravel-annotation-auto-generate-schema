<?php

namespace App\Console\Commands;

use App\Support\MigrationGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SchemaMake extends Command
{
    protected $signature = 'schema:make
                            {name : Schema class name, e.g. ProductSchema or Shop/ProductSchema}
                            {--namespace= : Custom namespace, default App\Schema}
                            {--dir= : Custom directory, default app/Schema}
                            {--uuid : Use UUID v4 as primary key instead of auto-incrementing bigint}
                            {--raw : Generate a full Schema::create() migration instead of the annotation-driven form}';

    protected $description = 'Create a new schema class, bare model, and migration file.';

    public function handle(): int
    {
        $rootNs = $this->option('namespace') ?? 'App\\Schema';
        $rootDir = $this->option('dir') ?? app_path('Schema');

        // Normalize input — accept any of these formats:
        //   TransactionSchema
        //   App\Schema\TransactionSchema
        //   App/Schema/TransactionSchema
        $input = str_replace('/', '\\', $this->argument('name'));

        // Strip the root namespace prefix if the user passed a full FQCN
        $rootNsPrefix = rtrim($rootNs, '\\').'\\';
        if (str_starts_with($input, $rootNsPrefix)) {
            $input = substr($input, strlen($rootNsPrefix));
        }

        $base = class_basename($input);   // TransactionSchema
        $subNs = str_contains($input, '\\') ? '\\'.Str::beforeLast($input, '\\') : '';
        $subDir = str_contains($input, '\\')
            ? DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, Str::beforeLast($input, '\\'))
            : '';

        $namespace = $rootNs.$subNs;                              // App\Schema
        $dir = $rootDir.$subDir;                            // app/Schema
        $path = $dir.DIRECTORY_SEPARATOR.$base.'.php';   // app/Schema/TransactionSchema.php
        $modelName = preg_replace('/Schema$/', '', $base);           // Transaction
        $fqcn = $namespace.'\\'.$base;                     // App\Schema\TransactionSchema

        // Prevent overwrite
        if (file_exists($path)) {
            $this->error("Schema already exists: {$path}");

            return self::FAILURE;
        }

        // Create directory if needed
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 1. Write the schema file
        file_put_contents($path, $this->stub($namespace, $base, $modelName, $this->option('uuid')));
        $this->info("Schema created:    {$path}");

        // 2. Write a bare model so 'use App\Models\Transaction' resolves immediately
        $modelPath = $this->createModel($modelName, $fqcn);
        if ($modelPath) {
            $this->info("Model created:     {$modelPath}");
        }

        // 3. Generate the migration file directly from the schema annotations
        $migrationPath = $this->createMigration($fqcn, $path, $this->option('raw'));
        if ($migrationPath) {
            $this->info("Migration created: {$migrationPath}");
        }

        $this->line('');
        $this->line('Next steps:');
        $this->line("  1. Add columns to the schema:  {$path}");
        $this->line("  2. Re-run migration if needed: php artisan schema:migrate \"{$fqcn}\"");
        $this->line('  3. Run migrations:             php artisan migrate');

        return self::SUCCESS;
    }

    /**
     * Generate the migration file by loading the freshly-written schema and
     * passing it through MigrationGenerator.
     *
     * Because the schema file was just created (and may not be in the autoloader yet),
     * we require_once it manually before calling MigrationGenerator::write().
     *
     * Returns the migration path on success, or null on failure.
     */
    private function createMigration(string $fqcn, string $schemaFilePath, bool $raw = false): ?string
    {
        try {
            // Load the schema file directly — it won't be in the autoloader yet
            require_once $schemaFilePath;

            $migrationPath = MigrationGenerator::write($fqcn, null, $raw);

            return $migrationPath;
        } catch (\Throwable $e) {
            $this->warn('Migration skipped: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Create a bare Eloquent Model file at app/Models/{Name}.php.
     * The model uses #[UsesSchema] + HasSchema so it is immediately functional
     * once the schema is filled in and schema:model is run for a full regeneration.
     *
     * Returns the path written, or null if the file already existed.
     */
    private function createModel(string $modelName, string $schemaFqcn): ?string
    {
        $modelsDir = app_path('Models');

        if (! is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }

        $modelPath = $modelsDir.DIRECTORY_SEPARATOR.$modelName.'.php';

        // Don't overwrite an existing model
        if (file_exists($modelPath)) {
            $this->warn("Model already exists, skipped: {$modelPath}");

            return null;
        }

        $stub = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use {$schemaFqcn};

#[UsesSchema({$modelName}Schema::class)]
class {$modelName} extends Model
{
    use HasSchema,HasFactory;
}
PHP;

        file_put_contents($modelPath, $stub);

        return $modelPath;
    }

    private function stub(string $namespace, string $class, string $modelName, bool $uuid = false): string
    {
        $tableName = Str::snake(Str::pluralStudly($modelName)); // Product → products

        // Primary key block differs between bigIncrements and UUID
        if ($uuid) {
            $pkBlock = <<<'BLOCK'
    // ── Primary key (UUID v4) ──────────────────────────────────────────────
    // $incrementing = false and $keyType = 'string' are set automatically
    // by HasSchema when it reads #[PrimaryKey(type: 'uuid')].

    #[PrimaryKey(type: 'uuid')]
    #[Uuid(version: 4)]
    public string $id;
BLOCK;
            $uuidImport = 'use App\\Attributes\\Validation\\Uuid;';
            $uuidUncomment = '// use App\Attributes\Validation\Uuid;';
        } else {
            $pkBlock = <<<'BLOCK'
    // ── Primary key ────────────────────────────────────────────────────────

    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;
BLOCK;
            $uuidImport = '// use App\Attributes\Validation\Uuid;';
            $uuidUncomment = '// use App\Attributes\Validation\Uuid;';
        }

        return <<<PHP
<?php

namespace {$namespace};

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Table;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\PrimaryKey;
// use App\Attributes\Migration\ForeignKey;
// use App\Attributes\Migration\HasOne;
// use App\Attributes\Migration\HasMany;
// use App\Attributes\Migration\BelongsTo;
// use App\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Max;
// use App\Attributes\Validation\Email;
// use App\Attributes\Validation\Numeric;
// use App\Attributes\Validation\In;
// use App\Attributes\Validation\Unique;
// use App\Attributes\Validation\Confirmed;
// use App\Attributes\Validation\Regex;
{$uuidImport}

// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
// use App\Attributes\Model\Hidden;
// use App\Attributes\Model\Cast;
// use App\Attributes\Model\Appended;

// NOTE: The model class name is '{$modelName}' (without 'Schema' suffix).
// This import is required so PHP resolves {$modelName}::class to App\Models\\{$modelName}
// and not to {$namespace}\\{$modelName}.
use App\Models\\{$modelName};

#[EloquentModel(model: {$modelName}::class)]
#[Table(name: '{$tableName}', timestamps: true, softDeletes: false)]
class {$class}
{
{$pkBlock}

    // ── Add your columns below ─────────────────────────────────────────────
    //
    // #[Column(type: 'string', length: 100, nullable: false)]
    // #[Fillable]
    // #[Required(message: '{$class} name is required.')]
    // #[Min(2,   message: 'Name must be at least 2 characters.')]
    // #[Max(100, message: 'Name must not exceed 100 characters.')]
    // public string \$name;
}
PHP;
    }
}
