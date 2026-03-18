<?php

use App\Schema\DepartmentSchema;
use App\Traits\RunsSchemaMigration;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    use RunsSchemaMigration;

    protected function schema(): string
    {
        return DepartmentSchema::class;
    }
};
