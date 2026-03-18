<?php

use App\Schema\UserExampleSchema;
use App\Traits\RunsSchemaMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use RunsSchemaMigration;

    protected function schema(): string
    {
        return UserExampleSchema::class;
    }
};
