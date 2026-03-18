<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\DepartmentSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'department_id');
    }
}
