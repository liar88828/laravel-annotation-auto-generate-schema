<?php

namespace App\Models;

use App\Attributes\Model\UsesSchema;
use App\Schema\HistorySchema;
use App\Traits\HasSchema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[UsesSchema(HistorySchema::class)]
class History extends Model
{
    use HasFactory, HasSchema;
}
