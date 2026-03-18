<?php

namespace App\Console\Commands;

use App\Support\MigrationGenerator;
use Illuminate\Console\Command;

/**
 * SchemaMigrate
 *
 * Generates a Laravel migration file from a schema class.
 *
 * Usage:
 *   php artisan schema:migrate DiscountSchema                  short name
 *   php artisan schema:migrate App\Schema\DiscountSchema       full FQCN
 *   php artisan schema:migrate DiscountSchema --print          preview only
 *   php artisan schema:migrate DiscountSchema --raw            expanded Schema::create() style
 *   php artisan schema:migrate --all                           migrate every schema in app/Schema/
 *   php artisan schema:migrate --all --raw                     all schemas, raw style
 */
class SchemaMigrate extends Command
{
    protected $signature = 'schema:migrate
                            {class? : Short name (DiscountSchema) or full FQCN (App\Schema\DiscountSchema)}
                            {--all : Generate migrations for every schema file in app/Schema/}
                            {--print : Print the migration instead of writing to disk}
                            {--raw : Generate a full Schema::create() migration instead of the annotation-driven form}';

    protected $description = 'Generate a migration from a schema class. Use --all for every schema in app/Schema/.';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->handleAll();
        }

        $input = $this->argument('class');

        if (! $input) {
            $this->error('Provide a schema name or use --all.');
            $this->line('  php artisan schema:migrate DiscountSchema');
            $this->line('  php artisan schema:migrate --all');

            return self::FAILURE;
        }

        $schemaClass = $this->expandClass($input);

        $this->resolveClass($schemaClass);

        if (! class_exists($schemaClass)) {
            $paths = $this->candidatePaths($schemaClass);

            $this->error("Class [{$schemaClass}] not found.");
            $this->line('');
            $this->warn('Looked for the file at:');
            foreach ($paths as $p) {
                $exists = file_exists($p) ? '✓ exists' : '✗ not found';
                $this->line("  {$p}  ({$exists})");
            }
            $this->line('');
            $this->line('Make sure the file path matches the namespace, then run:');
            $this->line('  composer dump-autoload');

            return self::FAILURE;
        }

        return $this->generateMigration($schemaClass);
    }

    // -------------------------------------------------------------------------
    // --all: scan app/Schema/ and migrate every .php file found
    // -------------------------------------------------------------------------

    private function handleAll(): int
    {
        $schemaDir = app_path('Schema');

        if (! is_dir($schemaDir)) {
            $this->error("Schema directory not found: {$schemaDir}");

            return self::FAILURE;
        }

        // Recursively find all .php files in app/Schema/
        $files = $this->findSchemaFiles($schemaDir);

        if (empty($files)) {
            $this->warn('No schema files found in app/Schema/');

            return self::SUCCESS;
        }

        $this->line('Found '.count($files).' schema(s). Generating migrations...');
        $this->line('');

        $success = 0;
        $failed = 0;

        foreach ($files as $file) {
            $schemaClass = $this->fileToClass($file);

            // Load the file so the class is available
            require_once $file;

            if (! class_exists($schemaClass)) {
                $this->warn("  Skipped  [{$schemaClass}] — class not found after require.");
                $failed++;

                continue;
            }

            try {
                if ($this->option('print')) {
                    $this->line("// ── {$schemaClass} ".str_repeat('─', 40));
                    $this->line(MigrationGenerator::generate($schemaClass, $this->option('raw')));
                    $this->line('');
                } else {
                    $path = MigrationGenerator::write($schemaClass, null, $this->option('raw'));
                    $this->info("  ✓  {$schemaClass}");
                    $this->line("     → {$path}");
                }
                $success++;
            } catch (\Throwable $e) {
                $this->error("  ✗  {$schemaClass}: ".$e->getMessage());
                $failed++;
            }
        }

        $this->line('');
        $this->line("Done: {$success} generated, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Single migration
    // -------------------------------------------------------------------------

    private function generateMigration(string $schemaClass): int
    {
        try {
            if ($this->option('print')) {
                $this->line(MigrationGenerator::generate($schemaClass, $this->option('raw')));

                return self::SUCCESS;
            }

            $path = MigrationGenerator::write($schemaClass, null, $this->option('raw'));
            $this->info("Migration written to: {$path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Accept short name or full FQCN.
     *
     *   DiscountSchema            →  App\Schema\DiscountSchema
     *   App\Schema\DiscountSchema →  App\Schema\DiscountSchema  (unchanged)
     */
    private function expandClass(string $input): string
    {
        $input = str_replace('/', '\\', $input);

        // Already a fully-qualified name
        if (str_contains($input, '\\')) {
            return $input;
        }

        // Short name — prepend default schema namespace
        return 'App\\Schema\\'.$input;
    }

    /**
     * Recursively find all .php files under a directory.
     */
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

    /**
     * Convert an absolute file path to a fully-qualified class name.
     *
     *   E:\Code\app\Schema\DiscountSchema.php  →  App\Schema\DiscountSchema
     */
    private function fileToClass(string $filePath): string
    {
        $appPath = rtrim(app_path(), DIRECTORY_SEPARATOR);
        $relative = ltrim(str_replace($appPath, '', $filePath), DIRECTORY_SEPARATOR);
        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        $relative = preg_replace('/\.php$/', '', $relative);

        return 'App\\'.$relative;
    }

    /**
     * Try to require the class file before checking class_exists().
     * Works without a fresh composer dump-autoload.
     */
    private function resolveClass(string $class): void
    {
        if (class_exists($class)) {
            return;
        }

        foreach ($this->candidatePaths($class) as $path) {
            if (file_exists($path)) {
                require_once $path;

                return;
            }
        }
    }

    /**
     * Return candidate file paths for a FQCN.
     *
     *   App\Schema\DiscountSchema  →  app/Schema/DiscountSchema.php
     */
    private function candidatePaths(string $class): array
    {
        $relative = str_replace('\\', '/', ltrim(
            preg_replace('/^App/', '', $class), '\\'
        )).'.php';

        return [
            app_path($relative),
            base_path('app/'.ltrim($relative, '/')),
        ];
    }
}
