<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\History;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsToMany;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Column;
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
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Email;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Numeric;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Confirmed;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Regex;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Fillable;
// ── Model ──────────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Max;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Required;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Hidden;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Appended;

// Model
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Uuid;

#[EloquentModel(model: History::class)]
#[Table(name: 'histories', timestamps: true, softDeletes: false)]
class HistorySchema
{
    // ── Primary Key (UUID) ─────────────────────────────────────────────────
    #[PrimaryKey(type: 'uuid')]
    #[Uuid]
    public string $id;

    // ── Action ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 100, nullable: false)]
    #[Fillable]
    #[Required]
    #[Max(100)]
    public string $action;

    // ── Description ────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $description = null;

    // ── Metadata (JSON) ────────────────────────────────────────────────────
    #[Column(type: 'json', nullable: true)]
    #[Cast('array')]
    public ?string $meta = null;

    // ── Many-to-Many: Roles ────────────────────────────────────────────────
    #[BelongsToMany(
        related: RoleSchema::class,
        pivotTable: 'history_role',
        foreignPivotKey: 'history_id',
        relatedPivotKey: 'role_id',
        withTimestamps: true,
    )]
    public array $roles;
}
