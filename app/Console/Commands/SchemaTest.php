<?php

namespace App\Console\Commands;

use App\Support\SchemaTestGenerator;
use Illuminate\Console\Command;

/**
 * SchemaTest
 *
 * Generates a PHPUnit test class from a schema class.
 *
 * The generated test covers:
 *   --migrate  Table exists, all columns exist
 *   --model    $fillable, $hidden, $casts, $table resolved from schema
 *              Validation: required, email, unique, in, uuid, confirmed
 *              Persistence: create, hidden serialization, soft-delete/restore
 *
 * Usage:
 *   php artisan schema:test UserSchema --model          single schema, model tests
 *   php artisan schema:test UserSchema --migrate        single schema, migration tests only
 *   php artisan schema:test UserSchema --model --migrate  both
 *   php artisan schema:test --all --model               all schemas, model + validation tests
 *   php artisan schema:test --all --model --migrate     all schemas, full suite
 *   php artisan schema:test --all --force               overwrite existing test files
 */
class SchemaTest extends Command
{
    protected $signature = 'schema:test
                            {class?   : Short name (UserSchema) or FQCN. Omit to use --all.}
                            {--all     : Generate tests for every schema in app/Schema/}
                            {--model   : Include model wiring + validation + persistence tests}
                            {--migrate : Include migration (table + column) tests}
                            {--force   : Overwrite existing test files}';

    protected $description = 'Generate a PHPUnit test class from a schema class.';

    // -------------------------------------------------------------------------
    // Entry
    // -------------------------------------------------------------------------

    public function handle(): int
    {
        // Default: if neither flag passed, enable both
        if (! $this->option('model') && ! $this->option('migrate')) {
            $this->input->setOption('model', true);
            $this->input->setOption('migrate', true);
        }

        if ($this->option('all')) {
            return $this->handleAll();
        }

        $input = $this->argument('class');

        if (! $input) {
            $this->error('Provide a schema name or use --all.');
            $this->line('  php artisan schema:test UserSchema --model');
            $this->line('  php artisan schema:test --all --model --migrate');

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

        $this->line('Found '.count($files).' schema(s). Generating tests...');
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
    // Generate one test file
    // -------------------------------------------------------------------------

    private function generateOne(string $schemaClass, bool $quiet = false): int
    {
        try {
            $options = [
                'model' => $this->option('model'),
                'migrate' => $this->option('migrate'),
                'force' => $this->option('force'),
            ];

            $path = SchemaTestGenerator::write($schemaClass, $options);

            if ($path === null) {
                $this->warn('  Test already exists. Use --force to overwrite.');

                return self::SUCCESS;
            }

            $this->info("  Test: {$path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('  ✗ '.$e->getMessage());

            return self::FAILURE;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
