<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\ProfileSchema;
#[UsesSchema(ProfileSchema::class)]
class Profile extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'profiles';

    protected $keyType = 'string';

    public $incrementing = false;

}
