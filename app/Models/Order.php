<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\OrderSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(OrderSchema::class)]
class Order extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'orders';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
    ];
}
