<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ReflectionClass;
use App\Attributes\Migration\Table;
use App\Attributes\Migration\HasOne;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\BelongsToMany;
use App\Attributes\Model\Fillable;
use App\Attributes\Migration\Column;

/**
 * SchemaController
 *
 * Generates a CRUD controller from a schema class.
 *
 * Usage:
 *   php artisan schema:controller ProductSchema          API controller (default)
 *   php artisan schema:controller ProductSchema --api    API controller (explicit)
 *   php artisan schema:controller ProductSchema --blade  Blade controller + views stub
 *   php artisan schema:controller ProductSchema --force  Overwrite existing files
 *   php artisan schema:controller --all                  Generate for all schemas in app/Schema/
 *   php artisan schema:controller --all --blade          All schemas as Blade controllers
 */
class SchemaController extends Command
{
    protected $signature = 'schema:controller
                            {class? : Short name (ProductSchema) or FQCN. Omit to use --all.}
                            {--all   : Generate controllers for every schema in app/Schema/}
                            {--api   : Generate an API JSON controller (default)}
                            {--blade : Generate a Blade web controller}
                            {--raw   : Use plain Route:: calls instead of Spatie route attributes}
                            {--test  : Also generate a feature test for the controller}
                            {--force : Overwrite existing controller files}';

    protected $description = 'Generate a CRUD controller from a schema class. Use --all for every schema in app/Schema/.';

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
            $this->line('  php artisan schema:controller ProductSchema');
            $this->line('  php artisan schema:controller --all');
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
    // --all: scan app/Schema/ and generate a controller for every schema
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

        $this->line('Found ' . count($files) . ' schema(s). Generating controllers...');
        $this->line('');

        $success = 0;
        $failed  = 0;

        foreach ($files as $file) {
            $schemaClass = $this->fileToClass($file);

            require_once $file;

            if (! class_exists($schemaClass)) {
                $this->warn("  Skipped [{$schemaClass}] — class not found after require.");
                $failed++;
                continue;
            }

            $this->line("<fg=cyan>━━ {$schemaClass}</>");

            $result = $this->generateOne($schemaClass, quiet: true);

            if ($result === self::SUCCESS) {
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
    // Generate controller (and views) for a single schema
    // -------------------------------------------------------------------------

    private function generateOne(string $schemaClass, bool $quiet = false): int
    {
        $blade = $this->option('blade');
        $meta  = $this->extractMeta($schemaClass);

        $controllerPath = $this->writeController($meta, $blade);

        if (! $controllerPath) {
            return self::FAILURE;
        }

        $this->info("  Controller: {$controllerPath}");

        if ($blade) {
            $viewPaths = $this->writeViews($meta);
            foreach ($viewPaths as $path) {
                $this->info("  View:       {$path}");
            }
        }

        // --test: generate a feature test for the controller
        if ($this->option('test')) {
            $testPath = $this->writeControllerTest($meta, $blade);
            if ($testPath) {
                $this->info("  Test:       {$testPath}");
            } else {
                $this->warn("  Test       skipped (use --force to overwrite)");
            }
        }

        if (! $quiet) {
            $this->line('');
            if ($this->option('raw')) {
                $this->line('Add to your routes file:');
                if ($blade) {
                    $this->line("  Route::resource('{$meta['routeName']}', {$meta['controllerClass']}::class);");
                } else {
                    $this->line("  Route::apiResource('{$meta['routeName']}', {$meta['controllerClass']}::class);");
                }
                if ($meta['softDeletes']) {
                    $this->line("  Route::patch('{$meta['routeName']}/{id}/restore', [{$meta['controllerClass']}::class, 'restore']);");
                }
            } else {
                $this->line('<fg=green>Routes are registered automatically via Spatie route attributes.</>');
                $this->line('Make sure app/Http/Controllers is in your route-attributes config.');
            }
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Meta extraction from schema
    // -------------------------------------------------------------------------

    private function extractMeta(string $schemaClass): array
    {
        $ref        = new ReflectionClass($schemaClass);
        $modelName  = preg_replace('/Schema$/', '', class_basename($schemaClass));
        $modelFqcn  = "App\\Models\\{$modelName}";
        $varName    = Str::camel($modelName);           // product
        $routeName  = Str::snake(Str::pluralStudly($modelName)); // products
        $viewPrefix = Str::snake($modelName);           // product

        // Table metadata
        $tableAttrs  = $ref->getAttributes(Table::class);
        $softDeletes = $tableAttrs ? $tableAttrs[0]->newInstance()->softDeletes : false;

        // Fillable fields
        $fillable = [];
        foreach ($ref->getProperties() as $prop) {
            if ($prop->getAttributes(Fillable::class)) {
                $fillable[] = $prop->getName();
            }
        }

        // Relations
        $hasOne        = [];
        $hasMany       = [];
        $belongsTo     = [];
        $belongsToMany = [];

        foreach ($ref->getProperties() as $prop) {
            foreach ($prop->getAttributes(HasOne::class) as $_) {
                $hasOne[] = $prop->getName();
            }
            foreach ($prop->getAttributes(HasMany::class) as $_) {
                $hasMany[] = $prop->getName();
            }
            foreach ($prop->getAttributes(BelongsTo::class) as $_) {
                $belongsTo[] = preg_replace('/_id$/', '', $prop->getName());
            }
            foreach ($prop->getAttributes(BelongsToMany::class) as $_) {
                $belongsToMany[] = $prop->getName();
            }
        }

        $allRelations = array_merge($hasOne, $hasMany, $belongsTo, $belongsToMany);

        return [
            'schemaClass'     => $schemaClass,
            'modelName'       => $modelName,
            'modelFqcn'       => $modelFqcn,
            'varName'         => $varName,
            'routeName'       => $routeName,
            'viewPrefix'      => $viewPrefix,
            'controllerClass' => $modelName . 'Controller',
            'softDeletes'     => $softDeletes,
            'fillable'        => $fillable,
            'hasOne'          => $hasOne,
            'hasMany'         => $hasMany,
            'belongsTo'       => $belongsTo,
            'belongsToMany'   => $belongsToMany,
            'allRelations'    => $allRelations,
        ];
    }

    // -------------------------------------------------------------------------
    // Controller generation
    // -------------------------------------------------------------------------

    private function writeController(array $meta, bool $blade): ?string
    {
        $dir  = app_path('Http/Controllers');
        $path = $dir . '/' . $meta['controllerClass'] . '.php';

        if (file_exists($path) && ! $this->option('force')) {
            if (! $this->confirm("Controller already exists at [{$path}]. Overwrite?")) {
                $this->warn('Aborted.');
                return null;
            }
        }

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $blade
            ? $this->buildBladeController($meta)
            : $this->buildApiController($meta);

        file_put_contents($path, $content);

        return $path;
    }

    // -------------------------------------------------------------------------
    // API controller stub
    // -------------------------------------------------------------------------

    private function buildApiController(array $meta): string
    {
        $m           = $meta['modelName'];
        $var         = $meta['varName'];
        $model       = $meta['modelFqcn'];
        $controller  = $meta['controllerClass'];
        $route       = $meta['routeName'];
        $fillable    = $this->renderOnly($meta['fillable']);
        $relations   = $this->renderRelationsArray($meta['allRelations']);
        $softDeletes = $meta['softDeletes'];
        $raw         = $this->option('raw');

        $restoreMethod = $softDeletes ? $this->apiRestoreMethod($m, $var, $route, $raw) : '';

        if ($raw) {
            $docblock  = "/**\n * {$controller}\n *\n * Add to routes/api.php:\n *   Route::apiResource('{$route}', {$controller}::class);"
                . ($softDeletes ? "\n *   Route::patch('{$route}/{id}/restore', [{$controller}::class, 'restore']);" : '')
                . "\n */";
            $classAttr = '';
            $spatieUse = '';
            $attrs     = array_fill(0, 5, '');
        } else {
            $docblock  = "/**\n * {$controller}\n *\n * Routes registered automatically via spatie/laravel-route-attributes.\n * Requires: composer require spatie/laravel-route-attributes\n */";
            $classAttr = "\n#[Prefix('{$route}')]";
            $spatieUse = <<<USE

use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
USE;
            $attrs = [
                "\n    #[Get('/')]",
                "\n    #[Post('/')]",
                "\n    #[Get('/{{$var}}')]",
                "\n    #[Put('/{{$var}}')]",
                "\n    #[Delete('/{{$var}}')]",
            ];
        }

        [$attrIndex, $attrStore, $attrShow, $attrUpdate, $attrDestroy] = $attrs;

        return <<<PHP
<?php

namespace App\Http\Controllers;

use {$model};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;{$spatieUse}

{$docblock}{$classAttr}
class {$controller} extends Controller
{
    // ── GET /{$route} ──────────────────────────────────────────────────────────
{$attrIndex}
    public function index(Request \$request): JsonResponse
    {
        \${$var}s = {$m}::query()
            ->when(\$request->filled('search'), fn (\$q) =>
                \$q->where('id', 'like', "%{\$request->search}%")
            ){$this->renderWhenFilters($meta)}
            ->latest()
            ->paginate(\$request->integer('per_page', 15));

        return response()->json(\${$var}s);
    }

    // ── POST /{$route} ─────────────────────────────────────────────────────────
{$attrStore}
    public function store(Request \$request): JsonResponse
    {
        {$m}::schemaValidateOrFail(\$request->all());

        \${$var} = {$m}::create(\$request->only({$fillable}));
{$this->renderBelongsToManySync($meta, $var)}
        return response()->json(\${$var}{$relations}, Response::HTTP_CREATED);
    }

    // ── GET /{$route}/{{$var}} ──────────────────────────────────────────────────
{$attrShow}
    public function show({$m} \${$var}): JsonResponse
    {
        return response()->json(\${$var}{$relations});
    }

    // ── PUT /{$route}/{{$var}} ─────────────────────────────────────────────────
{$attrUpdate}
    public function update(Request \$request, {$m} \${$var}): JsonResponse
    {
        \${$var}->schemaValidateForUpdate(\$request->all());

        \${$var}->update(\$request->only({$fillable}));
{$this->renderBelongsToManySync($meta, $var)}
        return response()->json(\${$var}->fresh(){$this->renderLoadCall($meta)});
    }

    // ── DELETE /{$route}/{{$var}} ──────────────────────────────────────────────
{$attrDestroy}
    public function destroy({$m} \${$var}): JsonResponse
    {
        \${$var}->delete();

        return response()->json(['message' => '{$m} deleted.']);
    }
{$restoreMethod}
}
PHP;
    }

    // -------------------------------------------------------------------------
    // Blade controller stub
    // -------------------------------------------------------------------------

    private function buildBladeController(array $meta): string
    {
        $m           = $meta['modelName'];
        $var         = $meta['varName'];
        $model       = $meta['modelFqcn'];
        $controller  = $meta['controllerClass'];
        $route       = $meta['routeName'];
        $view        = $meta['viewPrefix'];
        $fillable    = $this->renderOnly($meta['fillable']);
        $softDeletes = $meta['softDeletes'];
        $raw         = $this->option('raw');

        $restoreMethod = $softDeletes ? $this->bladeRestoreMethod($m, $var, $route, $raw) : '';

        if ($raw) {
            $docblock  = "/**\n * {$controller}\n *\n * Views: resources/views/{$view}/\n *\n * Add to routes/web.php:\n *   Route::resource('{$route}', {$controller}::class);"
                . ($softDeletes ? "\n *   Route::patch('{$route}/{id}/restore', [{$controller}::class, 'restore']);" : '')
                . "\n */";
            $classAttr = '';
            $spatieUse = '';
            $attrs     = array_fill(0, 7, '');
        } else {
            $docblock  = "/**\n * {$controller}\n *\n * Views: resources/views/{$view}/\n * Routes registered automatically via spatie/laravel-route-attributes.\n */";
            $classAttr = "\n#[Prefix('{$route}')]";
            $spatieUse = <<<USE

use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;
USE;
            $attrs = [
                "\n    #[Get('/')]",
                "\n    #[Get('/create')]",
                "\n    #[Post('/')]",
                "\n    #[Get('/{{$var}}')]",
                "\n    #[Get('/{{$var}}/edit')]",
                "\n    #[Put('/{{$var}}')]",
                "\n    #[Delete('/{{$var}}')]",
            ];
        }

        [$attrIndex, $attrCreate, $attrStore, $attrShow, $attrEdit, $attrUpdate, $attrDestroy] = $attrs;

        return <<<PHP
<?php

namespace App\Http\Controllers;

use {$model};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;{$spatieUse}

{$docblock}{$classAttr}
class {$controller} extends Controller
{
    // ── GET /{$route} ──────────────────────────────────────────────────────────
{$attrIndex}
    public function index(Request \$request): View
    {
        \${$var}s = {$m}::query()
            ->when(\$request->filled('search'), fn (\$q) =>
                \$q->where('id', 'like', "%{\$request->search}%")
            ){$this->renderWhenFilters($meta)}
            ->latest()
            ->paginate(\$request->integer('per_page', 15));

        return view('{$view}.index', compact('{$var}s'));
    }

    // ── GET /{$route}/create ───────────────────────────────────────────────────
{$attrCreate}
    public function create(): View
    {
        return view('{$view}.create');
    }

    // ── POST /{$route} ─────────────────────────────────────────────────────────
{$attrStore}
    public function store(Request \$request): RedirectResponse
    {
        {$m}::schemaValidateOrFail(\$request->all());

        \${$var} = {$m}::create(\$request->only({$fillable}));
{$this->renderBelongsToManySync($meta, $var)}
        return redirect()->route('{$route}.show', \${$var})
            ->with('success', '{$m} created.');
    }

    // ── GET /{$route}/{{$var}} ──────────────────────────────────────────────────
{$attrShow}
    public function show({$m} \${$var}): View
    {
        return view('{$view}.show', compact('{$var}'));
    }

    // ── GET /{$route}/{{$var}}/edit ─────────────────────────────────────────────
{$attrEdit}
    public function edit({$m} \${$var}): View
    {
        return view('{$view}.edit', compact('{$var}'));
    }

    // ── PUT /{$route}/{{$var}} ─────────────────────────────────────────────────
{$attrUpdate}
    public function update(Request \$request, {$m} \${$var}): RedirectResponse
    {
        \${$var}->schemaValidateForUpdate(\$request->all());

        \${$var}->update(\$request->only({$fillable}));
{$this->renderBelongsToManySync($meta, $var)}
        return redirect()->route('{$route}.show', \${$var})
            ->with('success', '{$m} updated.');
    }

    // ── DELETE /{$route}/{{$var}} ──────────────────────────────────────────────
{$attrDestroy}
    public function destroy({$m} \${$var}): RedirectResponse
    {
        \${$var}->delete();

        return redirect()->route('{$route}.index')
            ->with('success', '{$m} deleted.');
    }
{$restoreMethod}
}
PHP;
    }

    // -------------------------------------------------------------------------
    // Blade view stubs
    // -------------------------------------------------------------------------

    private function writeViews(array $meta): array
    {
        $view   = $meta['viewPrefix'];
        $dir    = resource_path("views/{$view}");
        $m      = $meta['modelName'];
        $var    = $meta['varName'];
        $route  = $meta['routeName'];

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $views = [
            'index'  => $this->viewIndex($meta),
            'create' => $this->viewForm($meta, 'create'),
            'edit'   => $this->viewForm($meta, 'edit'),
            'show'   => $this->viewShow($meta),
        ];

        $paths = [];
        foreach ($views as $name => $content) {
            $path = $dir . '/' . $name . '.blade.php';
            if (! file_exists($path) || $this->option('force')) {
                file_put_contents($path, $content);
                $paths[] = $path;
            } else {
                $this->warn("View skipped (exists): {$path}");
            }
        }

        return $paths;
    }

    private function viewIndex(array $meta): string
    {
        $m     = $meta['modelName'];
        $var   = $meta['varName'];
        $route = $meta['routeName'];
        $cols  = implode(', ', array_map(fn($f) => "{{ \${$var}->{$f} }}", array_slice($meta['fillable'], 0, 3)));

        return <<<BLADE
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h1>{$m}s</h1>
        <a href="{{ route('{$route}.create') }}" class="btn btn-primary">Create {$m}</a>
    </div>

    <form method="GET">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-control mb-3">
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                @foreach({$this->phpArr($meta['fillable'])} as \$col)
                <th>{{ ucfirst(\$col) }}</th>
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\${$var}s as \${$var})
            <tr>
                <td>{{ \${$var}->id }}</td>
                @foreach({$this->phpArr($meta['fillable'])} as \$col)
                <td>{{ \${$var}->{\$col} }}</td>
                @endforeach
                <td>
                    <a href="{{ route('{$route}.show', \${$var}) }}" class="btn btn-sm btn-info">View</a>
                    <a href="{{ route('{$route}.edit', \${$var}) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" action="{{ route('{$route}.destroy', \${$var}) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ \${$var}s->withQueryString()->links() }}
</div>
@endsection
BLADE;
    }

    private function viewForm(array $meta, string $mode): string
    {
        $m     = $meta['modelName'];
        $var   = $meta['varName'];
        $route = $meta['routeName'];
        $title = $mode === 'create' ? "Create {$m}" : "Edit {$m}";
        $action = $mode === 'create'
            ? "{{ route('{$route}.store') }}"
            : "{{ route('{$route}.update', \${$var}) }}";
        $method = $mode === 'edit' ? '@method(\'PUT\')' : '';
        $old    = $mode === 'edit' ? ", \${$var}->{field}" : '';

        $fields = '';
        foreach ($meta['fillable'] as $field) {
            $oldVal = $mode === 'edit' ? ", \${$var}->{$field}" : '';
            $fields .= <<<FIELD

        <div class="mb-3">
            <label class="form-label">{{ ucfirst('{$field}') }}</label>
            <input type="text" name="{$field}" value="{{ old('{$field}'{$oldVal}) }}" class="form-control @error('{$field}') is-invalid @enderror">
            @error('{$field}') <div class="invalid-feedback">{{ \$message }}</div> @enderror
        </div>
FIELD;
        }

        return <<<BLADE
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{$title}</h1>

    <form method="POST" action="{$action}">
        @csrf
        {$method}
{$fields}
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="{{ route('{$route}.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
BLADE;
    }

    private function viewShow(array $meta): string
    {
        $m     = $meta['modelName'];
        $var   = $meta['varName'];
        $route = $meta['routeName'];

        $rows = '';
        foreach ($meta['fillable'] as $field) {
            $rows .= "        <tr><th>{{ ucfirst('{$field}') }}</th><td>{{ \${$var}->{$field} }}</td></tr>\n";
        }

        return <<<BLADE
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{$m} #{{ \${$var}->id }}</h1>

    <table class="table">
        <tbody>
{$rows}
        </tbody>
    </table>

    <a href="{{ route('{$route}.edit', \${$var}) }}" class="btn btn-warning">Edit</a>
    <a href="{{ route('{$route}.index') }}" class="btn btn-secondary">Back</a>

    <form method="POST" action="{{ route('{$route}.destroy', \${$var}) }}" style="display:inline">
        @csrf @method('DELETE')
        <button class="btn btn-danger" onclick="return confirm('Delete?')">Delete</button>
    </form>
</div>
@endsection
BLADE;
    }

    // -------------------------------------------------------------------------
    // Restore methods
    // -------------------------------------------------------------------------

    private function apiRestoreMethod(string $m, string $var, string $route, bool $raw = false): string
    {
        // Under #[Prefix] the path is relative — just '/restore/{id}'
        $attr = $raw ? '' : "\n    #[Patch('/restore/{id}')]";

        return <<<PHP


    // ── PATCH /{$route}/restore/{id} ───────────────────────────────────────────
{$attr}
    public function restore(int \$id): JsonResponse
    {
        \${$var} = {$m}::withTrashed()->findOrFail(\$id);
        \${$var}->restore();

        return response()->json(['message' => '{$m} restored.', '{$var}' => \${$var}]);
    }
PHP;
    }

    private function bladeRestoreMethod(string $m, string $var, string $route, bool $raw = false): string
    {
        $attr = $raw ? '' : "\n    #[Patch('/restore/{id}')]";

        return <<<PHP


    // ── PATCH /{$route}/restore/{id} ───────────────────────────────────────────
{$attr}
    public function restore(int \$id): RedirectResponse
    {
        \${$var} = {$m}::withTrashed()->findOrFail(\$id);
        \${$var}->restore();

        return redirect()->route('{$route}.index')
            ->with('success', '{$m} restored.');
    }
PHP;
    }

    // -------------------------------------------------------------------------
    // Controller test generator
    // -------------------------------------------------------------------------

    private function writeControllerTest(array $meta, bool $blade): ?string
    {
        $dir  = base_path('tests/Feature');
        $path = $dir . '/' . $meta['controllerClass'] . 'Test.php';

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->option('force')) {
            return null;
        }

        file_put_contents($path, $this->buildControllerTest($meta, $blade));

        return $path;
    }

    private function buildControllerTest(array $meta, bool $blade): string
    {
        $m           = $meta['modelName'];
        $var         = $meta['varName'];
        $model       = $meta['modelFqcn'];
        $controller  = $meta['controllerClass'];
        $route       = $meta['routeName'];
        $softDeletes = $meta['softDeletes'];
        $fillable    = $meta['fillable'];

        // Check if a factory exists for this model
        $factoryClass   = "Database\\Factories\\{$m}Factory";
        $hasFactory     = class_exists($factoryClass);
        $createData     = $hasFactory
            ? "{$m}::factory()->create()"
            : "{$m}::create(\$this->validData())";
        $makeData       = $hasFactory
            ? "{$m}::factory()->make()->toArray()"
            : "\$this->validData()";

        $softDeleteTests = $softDeletes ? $this->buildSoftDeleteApiTests($m, $var, $route, $createData) : '';

        return <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;
use {$model};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * {$controller}Test
 *
 * Feature tests for {$controller}.
 * Tests every endpoint: index, store, show, update, destroy.
 * Generated by: php artisan schema:controller {$m}Schema --test
 */
class {$controller}Test extends TestCase
{
    use RefreshDatabase;

    // ── GET /{$route} ──────────────────────────────────────────────────────────

    #[Test]
    public function index_returns_paginated_list(): void
    {
        {$createData};
        {$createData};

        \$response = \$this->getJson('/{$route}');

        \$response->assertOk()
            ->assertJsonStructure(['data', 'total', 'per_page', 'current_page']);
    }

    // ── POST /{$route} ─────────────────────────────────────────────────────────

    #[Test]
    public function store_creates_a_new_{$var}(): void
    {
        \$data = {$makeData};

        \$response = \$this->postJson('/{$route}', \$data);

        \$response->assertCreated()
            ->assertJsonFragment(['id' => \$response->json('id')]);

        \$this->assertDatabaseHas((new {$m})->getTable(), ['id' => \$response->json('id')]);
    }

    #[Test]
    public function store_fails_validation_with_empty_data(): void
    {
        \$response = \$this->postJson('/{$route}', []);

        \$response->assertUnprocessable();
    }

    // ── GET /{$route}/{{$var}} ──────────────────────────────────────────────────

    #[Test]
    public function show_returns_a_single_{$var}(): void
    {
        \${$var} = {$createData};

        \$response = \$this->getJson("/{$route}/{\${$var}->id}");

        \$response->assertOk()
            ->assertJsonFragment(['id' => \${$var}->id]);
    }

    #[Test]
    public function show_returns_404_for_missing_{$var}(): void
    {
        \$this->getJson('/{$route}/999999')->assertNotFound();
    }

    // ── PUT /{$route}/{{$var}} ─────────────────────────────────────────────────

    #[Test]
    public function update_modifies_an_existing_{$var}(): void
    {
        \${$var}  = {$createData};
        \$data = {$makeData};

        \$response = \$this->putJson("/{$route}/{\${$var}->id}", \$data);

        \$response->assertOk()
            ->assertJsonFragment(['id' => \${$var}->id]);
    }

    // ── DELETE /{$route}/{{$var}} ──────────────────────────────────────────────

    #[Test]
    public function destroy_deletes_a_{$var}(): void
    {
        \${$var} = {$createData};

        \$this->deleteJson("/{$route}/{\${$var}->id}")->assertOk();

        \$this->assertDatabaseMissing((new {$m})->getTable(), ['id' => \${$var}->id]);
    }
{$softDeleteTests}
}
PHP;
    }

    private function buildSoftDeleteApiTests(string $m, string $var, string $route, string $createData): string
    {
        return <<<PHP


    // ── Soft delete / restore ──────────────────────────────────────────────────

    #[Test]
    public function destroy_soft_deletes_a_{$var}(): void
    {
        \${$var} = {$createData};

        \$this->deleteJson("/{$route}/{\${$var}->id}")->assertOk();

        \$this->assertSoftDeleted((new {$m})->getTable(), ['id' => \${$var}->id]);
    }

    #[Test]
    public function restore_recovers_a_soft_deleted_{$var}(): void
    {
        \${$var} = {$createData};
        \${$var}->delete();

        \$this->patchJson("/{$route}/restore/{\${$var}->id}")->assertOk();

        \$this->assertNotSoftDeleted((new {$m})->getTable(), ['id' => \${$var}->id]);
    }
PHP;
    }

    // -------------------------------------------------------------------------
    // Rendering helpers
    // -------------------------------------------------------------------------

    private function renderOnly(array $fillable): string
    {
        if (empty($fillable)) return '[]';
        $items = implode(', ', array_map(fn($f) => "'{$f}'", $fillable));
        return "[{$items}]";
    }

    private function renderRelationsArray(array $relations): string
    {
        if (empty($relations)) return '';
        $items = implode(', ', array_map(fn($r) => "'{$r}'", $relations));
        return "->load([{$items}])";
    }

    private function renderLoadCall(array $meta): string
    {
        if (empty($meta['allRelations'])) return '';
        $items = implode(', ', array_map(fn($r) => "'{$r}'", $meta['allRelations']));
        return "->load([{$items}])";
    }

    private function renderWhenFilters(array $meta): string
    {
        // Add when() filter for each BelongsTo FK field
        $lines = '';
        foreach ($meta['belongsTo'] as $rel) {
            $fk = $rel . '_id';
            $lines .= "\n            ->when(\$request->filled('{$fk}'), fn (\$q) => \$q->where('{$fk}', \$request->{$fk}))";
        }
        return $lines;
    }

    private function renderBelongsToManySync(array $meta, string $var): string
    {
        if (empty($meta['belongsToMany'])) return '';

        $lines = "\n";
        foreach ($meta['belongsToMany'] as $rel) {
            $ids = Str::singular($rel) . '_ids';
            $lines .= "        if (\$request->has('{$ids}')) {\n";
            $lines .= "            \${$var}->{$rel}()->sync(\$request->{$ids});\n";
            $lines .= "        }\n";
        }

        return $lines;
    }

    private function restoreRouteComment(string $route, string $controller, bool $softDeletes): string
    {
        if (! $softDeletes) return '';
        return "\n *   Route::patch('{$route}/{id}/restore', [{$controller}::class, 'restore']);";
    }

    private function phpArr(array $items): string
    {
        $quoted = implode(', ', array_map(fn($i) => "'{$i}'", $items));
        return "[{$quoted}]";
    }

    // -------------------------------------------------------------------------
    // Schema discovery (used by --all)
    // -------------------------------------------------------------------------

    private function findSchemaFiles(string $dir): array
    {
        $files    = [];
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
        $appPath  = rtrim(app_path(), DIRECTORY_SEPARATOR);
        $relative = ltrim(str_replace($appPath, '', $filePath), DIRECTORY_SEPARATOR);
        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        $relative = preg_replace('/\.php$/', '', $relative);

        return 'App\\' . $relative;
    }

    // -------------------------------------------------------------------------
    // Class resolution helpers
    // -------------------------------------------------------------------------

    private function expandClass(string $input): string
    {
        $input = str_replace('/', '\\', $input);
        return str_contains($input, '\\') ? $input : 'App\\Schema\\' . $input;
    }

    private function resolveClass(string $class): void
    {
        if (class_exists($class)) return;

        $relative = str_replace('\\', '/', ltrim(
                preg_replace('/^App/', '', $class), '\\'
            )) . '.php';

        foreach ([app_path($relative), base_path('app/' . ltrim($relative, '/'))] as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
}
