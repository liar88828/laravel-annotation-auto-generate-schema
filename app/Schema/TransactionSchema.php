<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\BelongsTo;
use App\Attributes\Migration\Column;
use App\Attributes\Migration\ForeignSchema;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\In;
use App\Attributes\Validation\Max;
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Numeric;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Required;
use App\Attributes\Validation\Uuid;
use App\Models\Transaction;

#[EloquentModel(model: Transaction::class)]
#[Table(name: 'transactions', timestamps: true, softDeletes: false, )]
class TransactionSchema
{
    // ── Primary Key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'uuid')]
    #[Uuid(version: 4)]
    public string $id;

    // ── Product Relation ───────────────────────────────────────────────────
    #[Column(type: 'unsignedBigInteger', nullable: false, index: true)]
    #[ForeignSchema(schema: ProductSchema::class, onDelete: 'cascade')]
    #[Fillable]
    #[Required(message: 'Product is required.')]
    #[BelongsTo(related: ProductSchema::class)]
    public int $product_id;

    // ── Shop Relation (optional but recommended) ───────────────────────────
    #[Column(type: 'unsignedBigInteger', nullable: false, index: true)]
    #[ForeignSchema(schema: ShopSchema::class, onDelete: 'cascade')]
    #[Fillable]
    #[BelongsTo(related: ShopSchema::class)]
    public int $shop_id;

    // ── Quantity ───────────────────────────────────────────────────────────
    #[Column(type: 'integer', nullable: false)]
    #[Fillable]
    #[Required(message: 'Quantity is required.')]
    #[Numeric(message: 'Quantity must be a number.')]
    #[Min(1, message: 'Minimum quantity is 1.')]
    public int $quantity;

    // ── Price (snapshot) ───────────────────────────────────────────────────
    #[Column(type: 'decimal', precision: 12, scale: 2, nullable: false)]
    #[Fillable]
    #[Required(message: 'Price is required.')]
    #[Numeric]
    #[Min(0)]
    public float $price;

    // ── Total ──────────────────────────────────────────────────────────────
    #[Column(type: 'decimal', precision: 14, scale: 2, nullable: false)]
    #[Fillable]
    #[Numeric]
    #[Min(0)]
    public float $total;

    // ── Status ─────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 20, default: 'pending', index: true)]
    #[Fillable]
    #[In('pending', 'paid', 'cancelled', message: 'Invalid status.')]
    public string $status = 'pending';

    // ── Notes ──────────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    #[Max(500)]
    public ?string $notes = null;
}
