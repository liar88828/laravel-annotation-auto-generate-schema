<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ShopSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'shop_id');
    }
}
