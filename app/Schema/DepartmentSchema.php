<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Column;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\In;
use App\Attributes\Validation\Max;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Unique;
// Model
use App\Models\Department;

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
