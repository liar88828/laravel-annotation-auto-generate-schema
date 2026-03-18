<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\RoleSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(RoleSchema::class)]
class Role extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'roles';
}
