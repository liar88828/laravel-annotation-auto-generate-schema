<?php

namespace App\Schema;

// ── Migration ──────────────────────────────────────────────────────────────
use App\Attributes\Migration\Column;
use App\Attributes\Migration\HasMany;
use App\Attributes\Migration\PrimaryKey;
use App\Attributes\Migration\Table;
// ── Validation ─────────────────────────────────────────────────────────────
use App\Attributes\Model\EloquentModel;
use App\Attributes\Model\Fillable;
use App\Attributes\Validation\Max;
// ── Model ──────────────────────────────────────────────────────────────────
use App\Attributes\Validation\Min;
use App\Attributes\Validation\Required;
use App\Models\Shop;

#[EloquentModel(model: Shop::class)]
#[Table(name: 'shops', timestamps: true, softDeletes: true)]
class ShopSchema
{
    // ── Primary Key ────────────────────────────────────────────────────────
    #[PrimaryKey(type: 'bigIncrements')]
    public int $id;

    // ── Name ───────────────────────────────────────────────────────────────
    #[Column(type: 'string', length: 150, nullable: false)]
    #[Fillable]
    #[Required(message: 'Shop name is required.')]
    #[Min(2)]
    #[Max(150)]
    public string $name;

    // ── Address ────────────────────────────────────────────────────────────
    #[Column(type: 'text', nullable: true)]
    #[Fillable]
    public ?string $address = null;

    // ── Status ─────────────────────────────────────────────────────────────
    #[Column(type: 'boolean', default: true)]
    #[Fillable]
    public bool $is_active = true;

    // ── HasMany → Products ─────────────────────────────────────────────────
    #[HasMany(related: ProductSchema::class, foreignKey: 'shop_id')]
    public array $products;
}
