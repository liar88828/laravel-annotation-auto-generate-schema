<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\ArticleSchema;

#[UsesSchema(ArticleSchema::class)]
class Article extends Model
{
    use HasFactory, HasSchema, SoftDeletes;
}