<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ProfileSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsesSchema(ProfileSchema::class)]
class Profile extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'role_id',
        'bio',
        'avatar',
        'phone',
        'address',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
