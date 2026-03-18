<?php

namespace App\Console\Commands;

use App\Support\ModelGenerator;
use Illuminate\Console\Command;

/**
 * SchemaModel
 *
 * Generates an Eloquent Model from a schema class.
 *
 * Usage:
 *   php artisan schema:model ProductSchema           single schema (short name)
 *   php artisan schema:model ProductSchema --print   preview only
 *   php artisan schema:model ProductSchema --force   overwrite without asking
 *   php artisan schema:model --all                   all schemas in app/Schema/
 *   php artisan schema:model --all --force           all schemas, overwrite all
 */
class SchemaModel extends Command
{
    protected $signature = 'schema:model
                            {class?  : Short name (ProductSchema) or FQCN. Omit to use --all.}
                            {--all   : Generate models for every schema in app/Schema/}
                            {--print : Print generated model instead of writing to disk}
                            {--force : Overwrite existing model files without asking}';

    protected $description = 'Generate an Eloquent Model from a schema class. Use --all for every schema in app/Schema/.';

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
            $this->line('  php artisan schema:model ProductSchema');
            $this->line('  php artisan schema:model --all');

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

        $this->line('Found '.count($files).' schema(s). Generating models...');
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
    // Generate one model
    // -------------------------------------------------------------------------

    private function generateOne(string $schemaClass, bool $quiet = false): int
    {
        try {
            if ($this->option('print')) {
                $this->line(ModelGenerator::generate($schemaClass));

                return self::SUCCESS;
            }

            $path = ModelGenerator::write($schemaClass);

            if (file_exists($path) && ! $this->option('force')) {
                if ($quiet) {
                    // In --all mode, skip silently without interactive prompt
                    $this->warn("  Skipped (exists): {$path}  — use --force to overwrite.");

                    return self::SUCCESS;
                }

                if (! $this->confirm("Model already exists at [{$path}]. Overwrite?")) {
                    $this->warn('Aborted.');

                    return self::SUCCESS;
                }
            }

            $path = ModelGenerator::write($schemaClass);
            $this->info("  Model: {$path}");

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

        foreach ($this->candidatePaths($class) as $path) {
            if (file_exists($path)) {
                require_once $path;

                return;
            }
        }
    }

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
