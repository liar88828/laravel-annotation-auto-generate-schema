<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Schema\RoleSchema;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Article;
use App\Models\History;
use App\Models\Team;
#[UsesSchema(RoleSchema::class)]
class Role extends Model
{
    use HasFactory, HasSchema, SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'public_id',
        'name',
        'status',
        'age',
        'born_at',
        'department_id',
    ];

    protected $casts = [
        'born_at' => 'date:Y-m-d',
    ];
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Profile::class, 'role_id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Article::class, 'role_id');
    }

    public function history(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\History::class, 'history_role', 'role_id', 'history_id')
            ->withTimestamps();
    }

    public function teams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Team::class, 'team_role', 'role_id', 'team_id')
            ->withPivot('joined_at');
    }
}
