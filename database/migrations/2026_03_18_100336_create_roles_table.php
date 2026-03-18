<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_id')->unique();
            $table->string('name', 100)->comment('Full name');
            $table->string('status', 20)->nullable()->default('active')->index();
            $table->unsignedTinyInteger('age')->nullable();
            $table->date('born_at')->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null')->onUpdate('restrict');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};