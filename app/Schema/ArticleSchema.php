<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Models\Article;
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
use Liar88828\LaravelSchemaAttributes\Attributes\Model\Fillable;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Email;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Numeric;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\In;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Unique;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Confirmed;
// use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Regex;

// ── Model ──────────────────────────────────────────────────────────────────
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Max;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Min;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Hidden;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Cast;
// use Liar88828\LaravelSchemaAttributes\Attributes\Model\Appended;

// Model
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Required;

#[EloquentModel(model: Article::class)]
#[Table(name: 'articles', timestamps: true, softDeletes: true)]
class ArticleSchema
{
    // ── Primary Key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── User Relation (Author) ─────────────────────────────────────────────
    #[Column(type: 'unsignedBigInteger', nullable: false, index: true)]
    #[ForeignSchema(schema: RoleSchema::class, onDelete: 'cascade')]
    #[Fillable] // ← add this
    #[Required] // ← add this
    #[BelongsTo(related: RoleSchema::class)]
    public int $role_id;

    // ── Title ──────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 191, nullable: false)]
    #[Fillable]
    #[Required]
    #[Min(2)]
    #[Max(191)]
    public string $title;

    // ── Slug ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 191, unique: true)]
    #[Fillable]
    public string $slug;

    // ── Content ────────────────────────────────────────────────────────────
    #[Column(type: 'longText', nullable: false)]
    #[Fillable]
    #[Required]
    public string $content;

    // ── Excerpt ────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 255, nullable: true)]
    #[Fillable]
    #[Max(255)]
    public ?string $excerpt = null;

    // ── Status ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 20, default: 'draft', index: true)]
    #[Fillable]
    #[In('draft', 'published', 'archived')]
    public string $status = 'draft';

    // ── Published At ───────────────────────────────────────────────────────
    #[Column(type: 'timestamp', nullable: true)]
    #[Fillable]
    #[Cast('datetime')]
    public ?string $published_at = null;

    // ── Views ──────────────────────────────────────────────────────────────
    #[Column(type: 'unsignedBigInteger', default: 0)]
    #[Cast('integer')]
    public int $views = 0;
}
