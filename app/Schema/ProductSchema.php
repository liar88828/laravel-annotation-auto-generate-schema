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
use App\Attributes\Validation\Min;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Numeric;
use App\Attributes\Validation\Required;
use App\Models\Product;

#[EloquentModel(model: Product::class)]
#[Table(name: 'products', timestamps: true, softDeletes: true)]
class ProductSchema
{
    // ── Primary Key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── Name ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 150, nullable: false)]
    #[Fillable]
    #[Required(message: 'Product name is required.')]
    #[Min(2, message: 'Name must be at least 2 characters.')]
    #[Max(150, message: 'Name must not exceed 150 characters.')]
    public string $name;

    // ── Description ────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $description = null;

    // ── Price ──────────────────────────────────────────────────────────────
    #[Column(type: 'decimal', precision: 12, scale: 2, nullable: false)]
    #[Fillable]
    #[Required(message: 'Price is required.')]
    #[Numeric(message: 'Price must be a number.')]
    #[Min(0, message: 'Price must be at least 0.')]
    public float $price;

    // ── Stock ──────────────────────────────────────────────────────────────
    #[Column(type: 'integer', nullable: false, default: 0)]
    #[Fillable]
    #[Required(message: 'Stock is required.')]
    #[Numeric(message: 'Stock must be a number.')]
    #[Min(0, message: 'Stock cannot be negative.')]
    public int $stock;

    // ── SKU ────────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 100, nullable: true, unique: true)]
    #[Fillable]
    #[Max(100, message: 'SKU must not exceed 100 characters.')]
    public ?string $sku = null;

    // ── Status ─────────────────────────────────────────────────────────────
    #[Column(type: 'boolean', default: true)]
    #[Fillable]
    public bool $is_active = true;
}
