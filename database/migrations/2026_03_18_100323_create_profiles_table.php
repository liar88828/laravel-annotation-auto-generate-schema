<?php

use Illuminate\Database\Migrations\Migration;
use App\Traits\RunsSchemaMigration;

return new class extends Migration
{
    use RunsSchemaMigration;

    protected function schema(): string
    {
        return \App\Schema\ProfileSchema::class;
    }
};