<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\UsesSchema;
use Liar88828\LaravelSchemaAttributes\Traits\HasSchema;
use App\Schema\ArticleSchema;

#[UsesSchema(ArticleSchema::class)]
class Article extends Model
{
    use HasFactory, HasSchema, SoftDeletes;
}