<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Table;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Uuid;

// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Model\Cast;

// Model
use App\Models\History;

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
