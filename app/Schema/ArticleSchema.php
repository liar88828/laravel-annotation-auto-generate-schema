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
use App\Attributes\Validation\In;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
// Model
use App\Models\Article;

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
