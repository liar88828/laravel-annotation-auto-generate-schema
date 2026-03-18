<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignSchema;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\Cast;
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Uuid;
// Model
use App\Models\Profile;

#[EloquentModel(model: Profile::class)]
#[Table(name: 'profiles', timestamps: true, softDeletes: false)]
class ProfileSchema
{
    // ── Primary Key (UUID) ─────────────────────────────────────────────────
    #[PrimaryKey(type: 'uuid')]
    #[Uuid(version: 4)]
    public string $id;

    // ── Role Relation (based on your current RoleSchema) ────────────────────
    #[Column(type: 'unsignedBigInteger', nullable: false, index: true, unique: true)]
    #[ForeignSchema(schema: RoleSchema::class, onDelete: 'cascade')]
    #[BelongsTo(related: RoleSchema::class)]
    #[Required] // ← add this
    #[Fillable]
    public int $role_id;

    // ── Bio ────────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 255, nullable: true)]
    #[Fillable]
    #[Max(255)]
    public ?string $bio = null;

    // ── Avatar ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 255, nullable: true)]
    #[Fillable]
    public ?string $avatar = null;

    // ── Phone ──────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 20, nullable: true)]
    #[Fillable]
    public ?string $phone = null;

    // ── Address ────────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $address = null;

    // ── Birth Date ─────────────────────────────────────────────────────────
    #[Column(type: 'date', nullable: true)]
    #[Fillable]
    #[Cast('date:Y-m-d')]
    public ?string $birth_date = null;
}
