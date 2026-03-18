<?php

namespace App\Schema;

use App\Attributes\Migration\BelongsTo;
// ── Migration ──────────────────────────────────────────────────────────────
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
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Unique;
use App\Attributes\Validation\Uuid;
use App\Models\UserExample;

/**
 * UserSchema — single source of truth for:
 *
 *   1. Migration   → php artisan schema:migrate  "App\Example\UserSchema"
 *   2. Relations   → php artisan schema:relations "App\Example\UserSchema" [--pivot]
 *   3. Model       → php artisan schema:model     "App\Example\UserSchema"
 *   4. Validation  → Validator::validateOrFail(UserSchema::class, $data)
 */
#[EloquentModel(model: UserExample::class)]
#[Table(name: 'usersExample', timestamps: true, softDeletes: true)]
class UserExampleSchema
{
    // ── Primary key ────────────────────────────────────────────────────────

    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── public_id (UUID) ───────────────────────────────────────────────────

    #[Column(type: 'uuid', nullable: false, unique: true)]
    #[Fillable]
    #[Required(message: 'A public UUID is required.')]
    #[Uuid(version: 4, message: 'The :field must be a valid UUIDv4.')]
    public string $public_id;

    // ── name ───────────────────────────────────────────────────────────────

    #[Column(type: 'string', length: 100, nullable: false, comment: 'Full name')]
    #[Fillable]
    #[Required(message: 'Please enter your full name.')]
    #[Min(2, message: 'Your name must be at least 2 characters.')]
    #[Max(100, message: 'Your name must not exceed 100 characters.')]
    public string $name;

    // ── email ──────────────────────────────────────────────────────────────

    #[Column(type: 'string', length: 191, nullable: false, unique: true)]
    #[Fillable]
    #[Required(message: 'An email address is required.')]
    #[Email(message: 'Please enter a valid email address.')]
    #[Max(191, message: 'Email must not exceed 191 characters.')]
    #[Unique(table: 'users', column: 'email', message: 'That email is already registered.')]
    public string $email;

    // ── password ───────────────────────────────────────────────────────────

    #[Column(type: 'string', length: 255, nullable: false)]
    #[Fillable]
    #[Hidden]
    #[Cast('hashed')]
    #[Required(message: 'A password is required.')]
    #[Min(8, message: 'Password must be at least 8 characters.')]
    #[Confirmed(message: 'The passwords do not match.')]
    public string $password;

    // ── status ─────────────────────────────────────────────────────────────

    #[Column(type: 'string', length: 20, nullable: true, default: 'active', index: true)]
    #[Fillable]
    #[In('active', 'inactive', 'suspended', message: 'Status must be active, inactive, or suspended.')]
    public ?string $status = 'active';

    // ── age ────────────────────────────────────────────────────────────────

    #[Column(type: 'unsignedTinyInteger', nullable: true)]
    #[Fillable]
    #[Min(0, message: 'Age cannot be negative.')]
    #[Max(150, message: 'Please enter a realistic age (max 150).')]
    public ?int $age = null;

    // ── is_verified ────────────────────────────────────────────────────────

    #[Column(type: 'boolean', nullable: false, default: false)]
    #[Cast('boolean')]
    public bool $is_verified = false;

    // ── born_at ────────────────────────────────────────────────────────────

    #[Column(type: 'date', nullable: true)]
    #[Fillable]
    #[Cast('date:Y-m-d')]
    public ?string $born_at = null;

    // ── settings ───────────────────────────────────────────────────────────

    #[Column(type: 'json', nullable: true)]
    #[Cast('array')]
    public ?string $settings = null;

    // ── full_name (virtual / appended accessor) ────────────────────────────

    #[Appended]
    public string $full_name;

    // ── BelongsTo ─────────────────────────────────────────────────────────
    // FK lives on this table → also needs Column + ForeignKey.

    #[Column(type: 'unsignedBigInteger', nullable: true, index: true)]
    #[ForeignKey(references: 'id', on: 'departments', onDelete: 'set null')]
    #[Fillable]
    #[BelongsTo(related: DepartmentSchema::class)]
    public ?int $department_id = null;

    // ── HasOne ─────────────────────────────────────────────────────────────

    #[HasOne(related: ProfileSchema::class, foreignKey: 'user_id', eager: true)]
    public ProfileSchema $profile;

    // ── HasMany ────────────────────────────────────────────────────────────

    #[HasMany(related: PostSchema::class, foreignKey: 'user_id')]
    public array $posts;

    // ── BelongsToMany ──────────────────────────────────────────────────────

    #[BelongsToMany(
        related: RoleSchema::class,
        pivotTable: 'role_user',
        foreignPivotKey: 'user_id',
        relatedPivotKey: 'role_id',
        withTimestamps: true,
    )]
    public array $roles;

    #[BelongsToMany(
        related: TeamSchema::class,
        pivotTable: 'team_user',
        foreignPivotKey: 'user_id',
        relatedPivotKey: 'team_id',
        pivotColumns: ['date:joined_at'],
    )]
    public array $teams;
}
