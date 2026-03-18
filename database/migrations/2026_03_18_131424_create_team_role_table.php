<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_role', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->uuid('team_id');
            $table->primary(['role_id', 'team_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->date('joined_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_role');
    }
};