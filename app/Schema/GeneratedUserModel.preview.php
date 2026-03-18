<?php

/**
 * This file simulates what ModelGenerator::generate(UserSchema::class) produces.
 * It is the expected output written to app/Models/User.php.
 */

// ── GENERATED OUTPUT PREVIEW ───────────────────────────────────────────────
//
// Command used:
//   php artisan schema:model "App\Example\UserSchema"
//
// ──────────────────────────────────────────────────────────────────────────

$expected = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Department;
use App\Models\Profile;
use App\Models\Post;
use App\Models\Role;
use App\Models\Team;use Illuminate\Support\Collection;
// Generated from: App\Example\UserSchema
class User extends Model
{
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
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
        'password'    => 'hashed',
        'is_verified' => 'boolean',
        'born_at'     => 'date:Y-m-d',
        'settings'    => 'array',
    ];

    public function department(): Collection<\Illuminate\Database\Eloquent\Relations\BelongsTo>
    {
        return $this->belongsTo(Department::class);
    }

    public function profile(): Collection<int,\Illuminate\Database\Eloquent\Relations\HasOne>
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function teams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id')
            ->withPivot('joined_at');
    }

    // ── Accessor (write this yourself) ────────────────────────────────────
    // 'full_name' is listed in $appends — implement the accessor:
    //
    // use Illuminate\Database\Eloquent\Casts\Attribute;
    //
    // protected function fullName(): Attribute
    // {
    //     return Attribute::get(fn () => "{$this->name}");
    // }
}
PHP;

echo $expected.PHP_EOL;
