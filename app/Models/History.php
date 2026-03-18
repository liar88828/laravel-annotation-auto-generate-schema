<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\HistorySchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'history_role', 'history_id', 'role_id')
            ->withTimestamps();
    }
}
