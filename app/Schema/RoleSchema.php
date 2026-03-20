<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\Role;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsTo;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsToMany;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Column;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\ForeignKey;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasMany;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasOne;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\PrimaryKey;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\ForeignKey;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasOne;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasMany;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsTo;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Table;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\EloquentModel;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Fillable;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Email;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Numeric;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Confirmed;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Regex;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Max;
// ── Model ──────────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Min;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Required;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Hidden;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Appended;
// Models
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Uuid;

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
