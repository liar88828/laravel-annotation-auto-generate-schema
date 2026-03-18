<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\FavoriteSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(FavoriteSchema::class)]
class Favorite extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'favorites';
}
