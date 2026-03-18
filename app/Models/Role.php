<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\RoleSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'role_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'role_id');
    }

    public function history(): BelongsToMany
    {
        return $this->belongsToMany(History::class, 'history_role', 'role_id', 'history_id')
            ->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_role', 'role_id', 'team_id')
            ->withPivot('joined_at');
    }
}
