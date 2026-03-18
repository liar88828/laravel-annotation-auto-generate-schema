<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\DepartmentSchema;
use App\Models\Role;
#[UsesSchema(DepartmentSchema::class)]
class Department extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'code',
        'slug',
        'description',
        'status',
        'budget',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];
    public function roles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Role::class, 'department_id');
    }
}
