<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\DepartmentSchema;

#[UsesSchema(DepartmentSchema::class)]
class Department extends Model
{
    use HasSchema,HasFactory;
}