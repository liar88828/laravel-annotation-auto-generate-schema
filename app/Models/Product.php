<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Schema\ProductSchema;
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
