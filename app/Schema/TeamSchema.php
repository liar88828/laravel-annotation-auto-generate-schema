<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Column;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Required;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Unique;
use App\Attributes\Validation\Uuid;
// Model
use App\Models\Team;

#[EloquentModel(model: Team::class)]
#[Table(name: 'teams', timestamps: true, softDeletes: false)]
class TeamSchema
{
    // ── Primary Key (UUID) ─────────────────────────────────────────────────
    #[PrimaryKey(type: 'uuid')]
    #[Uuid]
    public string $id;

    // ── Name ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 100, nullable: false)]
    #[Fillable]
    #[Required]
    #[Max(100)]
    public string $name;

    // ── Slug (recommended) ─────────────────────────────────────────────────
    #[Column(type: 'string', length: 120, unique: true)]
    #[Fillable]
    #[Unique(table: 'teams', column: 'slug')]
    public string $slug;

    // ── Description ────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $description = null;
}
