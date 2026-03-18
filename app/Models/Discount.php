<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\DiscountSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(DiscountSchema::class)]
class Discount extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'discounts';
}
