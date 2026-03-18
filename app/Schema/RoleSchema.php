<?php

namespace App\Schema;

use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\BelongsToMany;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignKey;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\HasOne;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
use App\Attributes\Model\Appended;

// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Model\Hidden;
use App\Attributes\Validation\Confirmed;
use App\Attributes\Validation\Email;
use App\Attributes\Validation\In;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Unique;
use App\Attributes\Validation\Uuid;

// Models
use App\Models\Role;

#[EloquentModel(model: Role::class)]
#[Table(name: 'roles', timestamps: true, softDeletes: true)]
class RoleSchema
{
    // ── Primary key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── public_id (UUID) ───────────────────────────────────────────────────
    #[Column(type: 'uuid', nullable: false, unique: true)]
    #[Fillable]
    #[Required(message: 'A public UUID is required.')]
    #[Uuid(version: 4)]
    public string $public_id;

    // ── name ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 100, nullable: false)]
    #[Fillable]
    #[Required]
    #[Min(2)]
    #[Max(100)]
    public string $name;

    // ── status ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 20, nullable: true, default: 'active', index: true)]
    #[Fillable]
    #[In('active', 'inactive', 'suspended')]
    public ?string $status = 'active';

    // ── age ────────────────────────────────────────────────────────────────
    #[Column(type: 'unsignedTinyInteger', nullable: true)]
    #[Fillable]
    #[Min(0)]
    #[Max(150)]
    public ?int $age = null;

    // ── born_at ────────────────────────────────────────────────────────────
    #[Column(type: 'date', nullable: true)]
    #[Fillable]
    #[Cast('date:Y-m-d')]
    public ?string $born_at = null;

    // ── BelongsTo (renamed FK stays consistent) ────────────────────────────
    #[Column(type: 'unsignedBigInteger', nullable: true, index: true)]
    #[ForeignKey(references: 'id', on: 'departments', onDelete: 'set null')]
    #[Fillable]
    #[BelongsTo(related: DepartmentSchema::class)]
    public ?int $department_id = null;

    // ── HasOne ─────────────────────────────────────────────────────────────
    #[HasOne(related: ProfileSchema::class, foreignKey: 'role_id', eager: true)]
    public ProfileSchema $profile;

    // ── HasMany ────────────────────────────────────────────────────────────
    #[HasMany(related: ArticleSchema::class, foreignKey: 'role_id')]
    public array $articles;

    // ── BelongsToMany ──────────────────────────────────────────────────────

    #[BelongsToMany(
        related: HistorySchema::class,
        pivotTable: 'history_role', // renamed from history_user
        foreignPivotKey: 'role_id',
        relatedPivotKey: 'history_id',
        withTimestamps: true,
    )]
    public array $history;

    #[BelongsToMany(
        related: TeamSchema::class,
        pivotTable: 'team_role', // renamed from team_user
        foreignPivotKey: 'role_id',
        relatedPivotKey: 'team_id',
        pivotColumns: ['date:joined_at'],
    )]
    public array $teams;
}
