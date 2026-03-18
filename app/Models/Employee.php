<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\EmployeeSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(EmployeeSchema::class)]
class Employee extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'employees';
}
