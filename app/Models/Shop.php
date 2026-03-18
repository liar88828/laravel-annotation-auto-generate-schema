<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Schema\ShopSchema;
use App\Models\Product;
#[UsesSchema(ShopSchema::class)]
class Shop extends Model
{
    use HasFactory, HasSchema, SoftDeletes;

    protected $table = 'shops';

    protected $fillable = [
        'name',
        'address',
        'is_active',
    ];
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Product::class, 'shop_id');
    }
}
