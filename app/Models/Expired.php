<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ExpiredSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(ExpiredSchema::class)]
class Expired extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'expireds';
}
