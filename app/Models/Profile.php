<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\ProfileSchema;
use App\Models\Role;
#[UsesSchema(ProfileSchema::class)]
class Profile extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'profiles';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'bio',
        'avatar',
        'phone',
        'address',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
    ];
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class);
    }
}
