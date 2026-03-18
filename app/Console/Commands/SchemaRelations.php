<?php

namespace App\Console\Commands;

use App\Support\RelationGenerator;
use Illuminate\Console\Command;

/**
 * SchemaRelations
 *
 * Reads the #[HasOne], #[HasMany], #[BelongsTo], and #[BelongsToMany] annotations
 * on a schema class and generates ready-to-paste Eloquent relation methods.
 * Optionally also writes pivot table migration files for BelongsToMany relations.
 *
 * Usage:
 *   php artisan schema:relations "App\Schema\ProductSchema"
 *   php artisan schema:relations "App\Schema\ProductSchema" --pivot
 *   php artisan schema:relations "App\Schema\ProductSchema" --summary
 *
 * Options:
 *   --pivot    Write pivot table migrations to database/migrations/ for every
 *              #[BelongsToMany] relation found in the schema.
 *
 *   --summary  Print a compact, human-readable list of all relations instead of
 *              generating method stubs. Useful for a quick overview.
 *
 *              Example output:
 *                Relations for [App\Schema\UserSchema]:
 *                ────────────────────────────────────────
 *                  department_id: BelongsTo → Department
 *                  profile: HasOne → Profile [eager]
 *                  posts: HasMany → Post
 *                  roles: BelongsToMany → Role
 *
 * Output (default):
 *   Eloquent relation method stubs printed to the console.
 *   Copy and paste them into your Eloquent Model file.
 *
 * Output (--pivot):
 *   database/migrations/{timestamp}_create_{pivot_table}_table.php
 *
 * Pivot table name is auto-derived by alphabetically sorting the two model names:
 *   User + Role  →  role_user
 *   Team + User  →  team_user
 *
 * You can override the pivot table name explicitly in the schema:
 *   #[BelongsToMany(related: RoleSchema::class, pivotTable: 'user_roles')]
 */
class SchemaRelations extends Command
{
    protected $signature = 'schema:relations
                            {class  : Fully-qualified schema class name}
                            {--pivot : Also write pivot migrations to disk}
                            {--summary : Print a human-readable summary of relations}';

    protected $description = 'Generate Eloquent relationship methods and pivot migrations from a schema class.';

    public function handle(): int
    {
        $schemaClass = $this->expandClass($this->argument('class'));

        $this->resolveClass($schemaClass);

        if (! class_exists($schemaClass)) {
            $this->error("Class [{$schemaClass}] not found.");
            $this->line('Run: composer dump-autoload');

            return self::FAILURE;
        }

        // --summary: print a compact relation overview and exit.
        if ($this->option('summary')) {
            $this->line(RelationGenerator::summary($schemaClass));

            return self::SUCCESS;
        }

        // Generate Eloquent method stubs from relation annotations.
        $methods = RelationGenerator::eloquentMethods($schemaClass);

        if (empty($methods)) {
            $this->warn('No relationship attributes found on this class.');

            return self::SUCCESS;
        }

        $this->info('// ── Paste these methods into your Eloquent Model ──────────────');
        $this->line('');

        // Print each generated method stub separated by a blank line.
        foreach ($methods as $method) {
            $this->line($method);
            $this->line('');
        }

        // --pivot: write pivot migration files for all BelongsToMany relations.
        if ($this->option('pivot')) {
            $paths = RelationGenerator::writePivotMigrations($schemaClass);

            if (empty($paths)) {
                // No BelongsToMany found — nothing to write.
                $this->info('No BelongsToMany relations found — no pivot migrations written.');
            } else {
                foreach ($paths as $path) {
                    $this->info("Pivot migration written: {$path}");
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Attempt to require the class file directly before relying on the autoloader.
     *
     * Converts the FQCN to a file path and requires it if found.
     * This allows the command to work on a fresh checkout before
     * `composer dump-autoload` has been run.
     *
     * Example:
     *   App\Schema\ProductSchema  →  app/Schema/ProductSchema.php
     */
    private function expandClass(string $input): string
    {
        $input = str_replace('/', '\\', $input);

        return str_contains($input, '\\') ? $input : 'App\\Schema\\'.$input;
    }

    private function resolveClass(string $class): void
    {
        // Already loaded — nothing to do.
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
     * Return all candidate file paths for a given fully-qualified class name.
     *
     * Strips the leading 'App' segment (which maps to app/) then converts
     * namespace separators to directory separators.
     *
     * Example:
     *   App\Schema\ProductSchema  →  app/Schema/ProductSchema.php
     */
    private function candidatePaths(string $class): array
    {
        // App\Schema\ProductSchema → Schema/ProductSchema.php
        $relative = str_replace('\\', '/', ltrim(
            preg_replace('/^App/', '', $class), '\\'
        )).'.php';

        return [
            app_path($relative),                        // primary: app/Schema/ProductSchema.php
            base_path('app/'.ltrim($relative, '/')),  // fallback: explicit base_path concat
        ];
    }
}
