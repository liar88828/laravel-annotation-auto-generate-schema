<?php

namespace App\Support;

use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignKey;
use App\Attributes\Migration\ForeignSchema;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use ReflectionClass;
use ReflectionProperty;

/**
 * Generates a Laravel migration Blueprint from a schema class decorated
 * with migration attributes.
 */
class MigrationGenerator
{
    private ReflectionClass $ref;

    private Table $table;

    private function __construct(private readonly string $schemaClass) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public static function generate(string $schemaClass, bool $raw = false): string
    {
        return $raw
            ? (new self($schemaClass))->build()
            : (new self($schemaClass))->buildAnnotationDriven();
    }

    public static function write(string $schemaClass, ?string $outputDir = null, bool $raw = false): string
    {
        $content = static::generate($schemaClass, $raw);
        $dir = $outputDir ?? database_path('migrations');
        $tableName = static::resolveTableName($schemaClass);

        $dir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $existing = static::findExistingMigration($dir, $tableName);

        if ($existing) {
            file_put_contents($existing, $content);

            return $existing;
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$tableName}_table.php";
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        file_put_contents($path, $content);

        return $path;
    }

    private static function findExistingMigration(string $dir, string $tableName): ?string
    {
        if (! is_dir($dir)) {
            return null;
        }

        $dir = str_replace('\\', '/', $dir);
        $pattern = "{$dir}/*_create_{$tableName}_table.php";
        $matches = glob($pattern);

        return ! empty($matches) ? $matches[0] : null;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function buildAnnotationDriven(): string
    {
        $schemaFqcn = ltrim($this->schemaClass, '\\');

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use App\Traits\RunsSchemaMigration;

return new class extends Migration
{
    use RunsSchemaMigration;

    protected function schema(): string
    {
        return \\{$schemaFqcn}::class;
    }
};
PHP;
    }

    private function build(): string
    {
        $this->ref = new ReflectionClass($this->schemaClass);

        $tableAttrs = $this->ref->getAttributes(Table::class);

        if (empty($tableAttrs)) {
            throw new \RuntimeException(
                "Class [{$this->schemaClass}] is missing the #[Table] attribute."
            );
        }

        $this->table = $tableAttrs[0]->newInstance();

        $tableName = $this->table->name;
        $upLines = $this->buildUpLines();
        $className = 'Create'.str($tableName)->studly().'Table';

        return $this->renderMigration($className, $tableName, $upLines);
    }

    private function buildUpLines(): array
    {
        $lines = [];

        foreach ($this->ref->getProperties() as $property) {
            // Primary key
            $pkAttrs = $property->getAttributes(PrimaryKey::class);
            if (! empty($pkAttrs)) {
                $pk = $pkAttrs[0]->newInstance();
                $col = $pk->name ?? $property->getName();

                if (in_array($pk->type, ['uuid', 'ulid'])) {
                    $lines[] = "\$table->{$pk->type}('{$col}')->primary();";
                } else {
                    $lines[] = "\$table->{$pk->type}('{$col}');";
                }

                continue;
            }

            // #[ForeignSchema] — expand into column + FK constraint
            $fsAttrs = $property->getAttributes(ForeignSchema::class);
            if (! empty($fsAttrs)) {
                $fs = $fsAttrs[0]->newInstance();
                $spec = ForeignSchemaResolver::resolve($fs);
                $name = $property->getName();

                $line = "\$table->{$spec['colType']}('{$name}')";
                if ($spec['nullable']) {
                    $line .= '->nullable()';
                }
                if ($spec['index']) {
                    $line .= '->index()';
                }
                $lines[] = $line.';';
                $lines[] = "\$table->foreign('{$name}')"
                    ."->references('{$spec['references']}')"
                    ."->on('{$spec['table']}')"
                    ."->onDelete('{$spec['onDelete']}')"
                    ."->onUpdate('{$spec['onUpdate']}');";

                continue;
            }

            // Regular column
            $colAttrs = $property->getAttributes(Column::class);
            if (empty($colAttrs)) {
                continue;
            }

            /** @var Column $colDef */
            $colDef = $colAttrs[0]->newInstance();
            $name = $colDef->name ?? $property->getName();
            $lines[] = $this->renderColumnLine($name, $colDef, $property);
        }

        if ($this->table->timestamps) {
            $lines[] = '$table->timestamps();';
        }

        if ($this->table->softDeletes) {
            $lines[] = '$table->softDeletes();';
        }

        return $lines;
    }

    private function renderColumnLine(
        string $name,
        Column $col,
        ReflectionProperty $property
    ): string {
        // rememberToken() is a no-argument Blueprint macro
        if ($col->type === 'rememberToken') {
            return '$table->rememberToken();';
        }

        // Build the base fluent call — priority: precision/scale > length > bare
        if ($col->precision !== null) {
            $scale = $col->scale ?? 2;
            $args = "'{$name}', {$col->precision}, {$scale}";
        } elseif ($col->length !== null) {
            $args = "'{$name}', {$col->length}";
        } else {
            $args = "'{$name}'";
        }

        $line = "\$table->{$col->type}({$args})";

        if ($col->nullable) {
            $line .= '->nullable()';
        }

        if ($col->default !== '__UNSET__') {
            $default = $this->renderPhpValue($col->default);
            $line .= "->default({$default})";
        }

        if ($col->unique) {
            $line .= '->unique()';
        }

        if ($col->primary) {
            $line .= '->primary()';
        }

        if ($col->index && ! $col->unique && ! $col->primary) {
            $line .= '->index()';
        }

        if ($col->comment !== null) {
            $safe = addslashes($col->comment);
            $line .= "->comment('{$safe}')";
        }

        // Foreign key constraint
        $fkAttrs = $property->getAttributes(ForeignKey::class);
        if (! empty($fkAttrs)) {
            /** @var ForeignKey $fk */
            $fk = $fkAttrs[0]->newInstance();
            $line .= ';'.PHP_EOL;
            $line .= "            \$table->foreign('{$name}')"
                ."->references('{$fk->references}')"
                ."->on('{$fk->on}')"
                ."->onDelete('{$fk->onDelete}')"
                ."->onUpdate('{$fk->onUpdate}')";
        }

        return $line.';';
    }

    private function renderPhpValue(mixed $value): string
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

    private function renderMigration(string $className, string $tableName, array $upLines): string
    {
        $indent = str_repeat(' ', 12);
        $body = implode(PHP_EOL.$indent, $upLines);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            {$body}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    private static function resolveTableName(string $schemaClass): string
    {
        $ref = new ReflectionClass($schemaClass);
        $attrs = $ref->getAttributes(Table::class);

        if (! empty($attrs)) {
            return $attrs[0]->newInstance()->name;
        }

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename($schemaClass)));
    }
}
