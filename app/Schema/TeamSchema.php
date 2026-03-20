<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\Team;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Column;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\PrimaryKey;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Table;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\ForeignKey;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasOne;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\HasMany;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsTo;
// use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsToMany;

// ── Validation ─────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Model\EloquentModel;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Fillable;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Email;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Numeric;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Confirmed;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Regex;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Max;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Required;
// ── Model ──────────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Uuid;

// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Hidden;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Appended;

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
