<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Schema\ArticleSchema;
use App\Models\User;
#[UsesSchema(ArticleSchema::class)]
class Article extends Model
{
    use HasFactory, HasSchema, SoftDeletes;

    protected $table = 'articles';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views' => 'integer',
    ];
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
