<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\Department;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Column;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasMany;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\PrimaryKey;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Table;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\ForeignKey;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasOne;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasMany;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsTo;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\EloquentModel;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Fillable;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Max;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Email;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Numeric;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Confirmed;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Regex;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Min;
// ── Model ──────────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Required;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;

// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Hidden;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Appended;

#[EloquentModel(model: Department::class)]
#[Table(name: 'departments', timestamps: true, softDeletes: false)]
class DepartmentSchema
{
    // ── Primary Key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── Name ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 100, nullable: false)]
    #[Fillable]
    #[Required(message: 'Department name is required.')]
    #[Min(2, message: 'Name must be at least 2 characters.')]
    #[Max(100, message: 'Name must not exceed 100 characters.')]
    public string $name;

    // ── Code (unique short identifier) ─────────────────────────────────────
    #[Column(type: 'string', length: 20, unique: true, nullable: false)]
    #[Fillable]
    #[Required]
    #[Max(20)]
    #[Unique(table: 'departments', column: 'code')]
    public string $code;

    // ── Slug ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 120, unique: true)]
    #[Fillable]
    #[Unique(table: 'departments', column: 'slug')]
    public string $slug;

    // ── Description ────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $description = null;

    // ── Status ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 20, default: 'active')]
    #[Fillable]
    #[In('active', 'inactive')]
    public string $status = 'active';

    // ── Budget ─────────────────────────────────────────────────────────────
    #[Column(type: 'decimal', precision: 15, scale: 2, default: 0)]
    #[Fillable]
    #[Cast('decimal:2')]
    public float $budget = 0;

    // ── HasMany Users (Relation Example) ───────────────────────────────────
    #[HasMany(related: RoleSchema::class, foreignKey: 'department_id')]
    public array $roles;
}
