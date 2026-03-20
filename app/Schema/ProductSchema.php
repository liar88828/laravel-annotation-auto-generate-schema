<?php

namespace App\Schema;

use App\Models\Product;
use Liar88828\LaravelSchemaAttributes\Attributes\CMigration;
use Liar88828\LaravelSchemaAttributes\Attributes\CModel;
use Liar88828\LaravelSchemaAttributes\Attributes\CValidation;
use Liar88828\LaravelSchemaAttributes\Attributes\Migration\Table;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\EloquentModel;
use Liar88828\LaravelSchemaAttributes\Attributes\Validation\Uuid;

#[EloquentModel(model: Product::class)]
#[Table(name: 'products', timestamps: true, softDeletes: false)]
class ProductSchema
{
    // ── Primary Key (UUID) ───────────────────────────────────────
    #[CMigration(primaryKey: 'uuid',)]
    #[Uuid]
    // is bug that must be add #[Uuid]
        // maybe mustbe fix it ???
        // Note: uuid  validation → use standalone #[Uuid] or #[Uuid(4)] attribute
    public string $id;

    // ── Name ─────────────────────────────────────────────────────
    #[CMigration(type: 'string', length: 150, nullable: false)]
    #[CModel(fillable: true)]
    #[CValidation(required: true, max: 150)]
    public string $name;

    // ── Description ──────────────────────────────────────────────
    #[CMigration(type: 'text', nullable: true)]
    #[CModel(fillable: true)]
    #[CValidation(required: false)]
    public ?string $description = null;

    // ── Price ────────────────────────────────────────────────────
    #[CMigration(type: 'decimal', nullable: false, precision: 12, scale: 2)]
    #[CModel(fillable: true, cast: 'decimal:2')]
    #[CValidation(required: true, max: 12)]
    public float $price;

    // ── Stock ────────────────────────────────────────────────────
    #[CMigration(type: 'integer', nullable: false, default: 0)]
    #[CModel(fillable: true)]
    #[CValidation(required: false)]
    public int $stock = 0;

    // ── Sku ──────────────────────────────────────────────────────
    #[CMigration(type: 'string', length: 100, nullable: true)]
    #[CModel(fillable: true)]
    #[CValidation(max: 100)]
    public ?string $sku = null;

    // ── Status ───────────────────────────────────────────────────
    #[CMigration(type: 'string', length: 20, nullable: false, default: 'active')]
    #[CModel(fillable: true)]
    #[CValidation(max: 20)]
    public string $status = 'active';
}
