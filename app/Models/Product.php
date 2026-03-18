<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ProductSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsesSchema(ProductSchema::class)]
class Product extends Model
{
    use HasFactory, HasSchema, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'sku',
        'is_active',
    ];
}
