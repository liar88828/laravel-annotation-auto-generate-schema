<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\DepartmentSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(DepartmentSchema::class)]
class Department extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'departments';
}
