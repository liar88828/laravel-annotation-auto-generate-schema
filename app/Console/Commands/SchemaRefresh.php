<?php

namespace App\Console\Commands;

use App\Attributes\Model\EloquentModel;
use App\Support\MigrationGenerator;
use App\Support\ModelGenerator;
use App\Support\RelationGenerator;
use App\Support\SchemaTestGenerator;
use Illuminate\Console\Command;

/**
 * SchemaRefresh
 *
 * Regenerates Model, Migration, Factory, and Test for one schema
 * or every schema in app/Schema/ at once.
 *
 * Usage:
 *   php artisan schema:refresh                          all schemas, all artifacts
 *   php artisan schema:refresh TransactionSchema        single schema
 *   php artisan schema:refresh --force                  overwrite all existing files
 *   php artisan schema:refresh --raw                    expanded Schema::create() migrations
 *   php artisan schema:refresh --migration-only         only migration files
 *   php artisan schema:refresh --model-only             only model files
 *   php artisan schema:refresh --factory-only           only factory files
 *   php artisan schema:refresh --test-only              only test files
 *   php artisan schema:refresh --pivot                  also generate pivot table migrations
 */
class SchemaRefresh extends Command
{
    protected $signature = 'schema:refresh
                            {class?           : Short name (DiscountSchema) or FQCN. Omit to refresh all.}
                            {--force          : Overwrite existing files without confirmation}
                            {--raw            : Generate full Schema::create() migrations}
                            {--migration-only : Only regenerate migration files}
                            {--model-only     : Only regenerate model files}
                            {--factory-only   : Only regenerate factory files}
                            {--test-only      : Only regenerate test files}
                            {--pivot          : Also generate pivot table migrations for BelongsToMany relations}';

    protected $description = 'Regenerate Model + Migration + Factory + Test for one schema or all schemas in app/Schema/.';

    public function handle(): int
    {
        $input = $this->argument('class');

        $schemas = $input
            ? [$this->expandAndLoad($this->expandClass($input))]
            : $this->loadAllSchemas();

        $schemas = array_filter($schemas);

        if (empty($schemas)) {
            $this->warn('No schemas found or loaded.');

            return self::FAILURE;
        }

        $only = $this->resolveOnly();

        $counts = ['migration' => 0, 'model' => 0, 'factory' => 0, 'test' => 0, 'pivot' => 0, 'failed' => 0];

        $this->line('');

        foreach ($schemas as $schemaClass) {
            $this->line("<fg=cyan>━━ {$schemaClass}</>");

            // ── Migration ─────────────────────────────────────────────────
            if ($only['migration']) {
                try {
                    $path = MigrationGenerator::write($schemaClass, null, $this->option('raw'));
                    $this->info("   Migration → {$path}");
                    $counts['migration']++;
                } catch (\Throwable $e) {
                    $this->error('   Migration ✗ '.$e->getMessage());
                    $counts['failed']++;
                }
            }

            // ── Pivot migrations ───────────────────────────────────────────
            if ($this->option('pivot')) {
                try {
                    $paths = RelationGenerator::writePivotMigrations(
                        $schemaClass,
                        null,
                        (bool) $this->option('force'),  // ← pass force
                    );
                    if (empty($paths)) {
                        $this->warn('   Pivot     skipped (use --force to overwrite)');
                    } else {
                        foreach ($paths as $path) {
                            $this->info("   Pivot     → {$path}");
                            $counts['pivot']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error('   Pivot     ✗ '.$e->getMessage());
                    $counts['failed']++;
                }
            }

            // ── Model ──────────────────────────────────────────────────────
            if ($only['model']) {
                try {
                    $path = $this->writeModel($schemaClass);
                    if ($path) {
                        $this->info("   Model     → {$path}");
                        $counts['model']++;
                    } else {
                        $this->warn('   Model     skipped (use --force to overwrite)');
                    }
                } catch (\Throwable $e) {
                    $this->error('   Model     ✗ '.$e->getMessage());
                    $counts['failed']++;
                }
            }

            // ── Factory ────────────────────────────────────────────────────
            if ($only['factory']) {
                try {
                    $path = $this->writeFactory($schemaClass);
                    if ($path) {
                        $this->info("   Factory   → {$path}");
                        $counts['factory']++;
                    } else {
                        $this->warn('   Factory   skipped (use --force to overwrite)');
                    }
                } catch (\Throwable $e) {
                    $this->error('   Factory   ✗ '.$e->getMessage());
                    $counts['failed']++;
                }
            }

            // ── Test ───────────────────────────────────────────────────────
            if ($only['test']) {
                try {
                    $path = SchemaTestGenerator::write($schemaClass, [
                        'model' => true,
                        'migrate' => true,
                        'force' => $this->option('force'),
                    ]);
                    if ($path) {
                        $this->info("   Test      → {$path}");
                        $counts['test']++;
                    } else {
                        $this->warn('   Test      skipped (use --force to overwrite)');
                    }
                } catch (\Throwable $e) {
                    $this->error('   Test      ✗ '.$e->getMessage());
                    $counts['failed']++;
                }
            }

            $this->line('');
        }

        // ── Summary ────────────────────────────────────────────────────────
        $parts = [];
        if ($only['migration']) {
            $parts[] = "{$counts['migration']} migration(s)";
        }
        if ($this->option('pivot')) {
            $parts[] = "{$counts['pivot']} pivot migration(s)";
        }
        if ($only['model']) {
            $parts[] = "{$counts['model']} model(s)";
        }
        if ($only['factory']) {
            $parts[] = "{$counts['factory']} factor(ies)";
        }
        if ($only['test']) {
            $parts[] = "{$counts['test']} test(s)";
        }

        $this->line('Done: '.count($schemas).' schema(s) — '.implode(', ', $parts).", {$counts['failed']} failed.");

        if ($only['migration'] || $this->option('pivot')) {
            $this->line('');
            $this->line('Next: <fg=yellow>php artisan migrate</>');
        }

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Which artifacts to generate
    // -------------------------------------------------------------------------

    private function resolveOnly(): array
    {
        $migrationOnly = $this->option('migration-only');
        $modelOnly = $this->option('model-only');
        $factoryOnly = $this->option('factory-only');
        $testOnly = $this->option('test-only');

        $anyOnly = $migrationOnly || $modelOnly || $factoryOnly || $testOnly;

        return [
            'migration' => $anyOnly ? $migrationOnly : true,
            'model' => $anyOnly ? $modelOnly : true,
            'factory' => $anyOnly ? $factoryOnly : true,
            'test' => $anyOnly ? $testOnly : true,
        ];
    }

    // -------------------------------------------------------------------------
    // Writers
    // -------------------------------------------------------------------------

    private function writeModel(string $schemaClass): ?string
    {
        $path = $this->resolveModelPath($schemaClass);

        if (file_exists($path) && ! $this->option('force')) {
            return null;
        }

        return ModelGenerator::write($schemaClass);
    }

    private function writeFactory(string $schemaClass): ?string
    {
        $modelName = preg_replace('/Schema$/', '', class_basename($schemaClass));
        $path = database_path("factories/{$modelName}Factory.php");

        if (file_exists($path) && ! $this->option('force')) {
            return null;
        }

        return $this->generateFactory($schemaClass, $path);
    }

    private function generateFactory(string $schemaClass, string $path): ?string
    {
        $flags = ['class' => class_basename($schemaClass)];
        if ($this->option('force')) {
            $flags['--force'] = true;
        }

        $exitCode = $this->call('schema:factory', $flags);

        return $exitCode === 0 && file_exists($path) ? $path : null;
    }

    private function resolveModelPath(string $schemaClass): string
    {
        $ref = new \ReflectionClass($schemaClass);
        $attrs = $ref->getAttributes(EloquentModel::class);
        $modelFqcn = $attrs ? $attrs[0]->newInstance()->model : null;

        if (! $modelFqcn) {
            $base = preg_replace('/Schema$/', '', class_basename($schemaClass));

            return app_path("Models/{$base}.php");
        }

        return app_path('Models/'.class_basename($modelFqcn).'.php');
    }

    // -------------------------------------------------------------------------
    // Schema discovery
    // -------------------------------------------------------------------------

    private function loadAllSchemas(): array
    {
        $schemaDir = app_path('Schema');

        if (! is_dir($schemaDir)) {
            $this->error("Schema directory not found: {$schemaDir}");

            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($schemaDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $classes = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $fqcn = $this->expandAndLoad($this->fileToClass($file->getPathname()));
                if ($fqcn) {
                    $classes[] = $fqcn;
                }
            }
        }

        sort($classes);

        return $classes;
    }

    private function expandAndLoad(string $fqcn): ?string
    {
        if (! class_exists($fqcn)) {
            $relative = str_replace('\\', '/', ltrim(
                preg_replace('/^App/', '', $fqcn), '\\'
            )).'.php';

            $path = app_path($relative);

            if (! file_exists($path)) {
                $this->warn("File not found for [{$fqcn}], skipping.");

                return null;
            }

            require_once $path;
        }

        if (! class_exists($fqcn)) {
            $this->warn("Class [{$fqcn}] could not be loaded, skipping.");

            return null;
        }

        return $fqcn;
    }

    private function expandClass(string $input): string
    {
        $input = str_replace('/', '\\', $input);

        return str_contains($input, '\\') ? $input : 'App\\Schema\\'.$input;
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
