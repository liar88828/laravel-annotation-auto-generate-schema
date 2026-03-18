<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\WarehouseSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(WarehouseSchema::class)]
class Warehouse extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'warehouses';
}
