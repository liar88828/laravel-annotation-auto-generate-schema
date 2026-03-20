<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\Profile;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\BelongsTo;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Column;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\ForeignSchema;
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
