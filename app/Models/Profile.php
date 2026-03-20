<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Liar88828\LaravelSchemaAttributes\Attributes\Model\UsesSchema;
use Liar88828\LaravelSchemaAttributes\Traits\HasSchema;
use App\Schema\ProfileSchema;

#[UsesSchema(ProfileSchema::class)]
class Profile extends Model
{
    use HasFactory, HasSchema;
}