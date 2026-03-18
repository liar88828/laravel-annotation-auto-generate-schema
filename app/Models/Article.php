<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ArticleSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsesSchema(ArticleSchema::class)]
class Article extends Model
{
    use HasFactory, HasSchema, SoftDeletes;
}
