<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['lead', 'member'])->default('member');
            $table->timestamps();
            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};