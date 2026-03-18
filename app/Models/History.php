<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\HistorySchema;
use App\Models\Role;
#[UsesSchema(HistorySchema::class)]
class History extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'action',
        'description',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class, 'history_role', 'history_id', 'role_id')
            ->withTimestamps();
    }
}
