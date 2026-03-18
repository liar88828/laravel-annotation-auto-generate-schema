<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\RoleSchema;

#[UsesSchema(RoleSchema::class)]
class Role extends Model
{
    use HasFactory, HasSchema, SoftDeletes;
}