<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Attributes\Model\UsesSchema;
use App\Traits\HasSchema;
use App\Schema\HistorySchema;

#[UsesSchema(HistorySchema::class)]
class History extends Model
{
    use HasFactory, HasSchema;
}