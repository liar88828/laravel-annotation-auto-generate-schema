<?php

use Illuminate\Database\Migrations\Migration;
use Liar88828\LaravelSchemaAttributes\Traits\RunsSchemaMigration;

return new class extends Migration
{
    use RunsSchemaMigration;

    protected function schema(): string
    {
        return \App\Schema\DepartmentSchema::class;
    }
};