<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\SettingSchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(SettingSchema::class)]
class Setting extends Model
{
    use HasFactory, HasSchema;

    protected $table = 'settings';

    protected $keyType = 'string';

    public $incrementing = false;
}
