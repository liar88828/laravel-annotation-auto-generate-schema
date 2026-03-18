<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\UserExampleSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsesSchema(UserExampleSchema::class)]
class UserExample extends Model
{
    use HasFactory, HasSchema, SoftDeletes;

    protected $table = 'usersExample';

    protected $fillable = [
        'public_id',
        'name',
        'email',
        'password',
        'status',
        'age',
        'born_at',
        'department_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'full_name',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'born_at' => 'date:Y-m-d',
        'settings' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id')
            ->withPivot('joined_at');
    }
}
