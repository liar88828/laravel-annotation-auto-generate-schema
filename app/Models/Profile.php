<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\ProfileSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(ProfileSchema::class)]
class Profile extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'profiles';
}
